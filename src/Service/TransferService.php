<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Account;
use App\Entity\User;
use App\Entity\Transaction;
use Psr\Log\LoggerInterface;

class TransferService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RedisService $redisService,
        private LoggerInterface $transferLogger
    ) {
    }

    public function enqueueTransfer(User $user, int $fromId, int $toId, float $amount, ?string $idempotencyKey = null): string
    {
        $transactionId = uniqid('txn_'); // Simplified ID generation

        // Optimistic check for quick feedback
        $from = $this->em->getRepository(Account::class)->find($fromId);
        $to = $this->em->getRepository(Account::class)->find($toId);

        if (!$from || !$to) {
            throw new \InvalidArgumentException('Account not found');
        }

        if ($from->getUser() !== $user) {
            throw new \InvalidArgumentException('Account does not belong to authenticated user');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if ($from->getBalance() < $amount) {
            $this->transferLogger->warning('Transfer failed: Insufficient funds (optimistic check)', [
                'fromAccountId' => $fromId,
                'balance' => $from->getBalance(),
                'attempted' => $amount
            ]);
            throw new \InvalidArgumentException('Insufficient funds');
        }

        // Push to Redis Queue
        $payload = [
            'transactionId' => $transactionId,
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => $amount,
            'idempotencyKey' => $idempotencyKey,
            'timestamp' => time()
        ];

        try {
            $this->redisService->push('transfers', $payload);

            $this->transferLogger->info('Transfer request queued successfully', $payload);

        } catch (\Exception $e) {
            $this->transferLogger->critical('Redis push failed', ['exception' => $e]);
            throw new \RuntimeException('System error: unable to queue transfer');
        }

        return $transactionId;
    }

    public function processTransfer(array $payload): void
    {
        $this->em->beginTransaction();
        try {
            // Use PESSIMISTIC_WRITE to lock rows for update
            $from = $this->em->find(Account::class, $payload['fromId'], \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            $to = $this->em->find(Account::class, $payload['toId'], \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

            if (!$from || !$to) {
                throw new \Exception("Account not found during processing");
            }

            // Re-check balance under lock
            if ($from->getBalance() < $payload['amount']) {
                throw new \Exception("Insufficient funds during processing");
            }

            // Update balances
            // Using basic math for now, but in production use bcmath
            $newFromBalance = (string) ($from->getBalance() - $payload['amount']);
            $newToBalance = (string) ($to->getBalance() + $payload['amount']);

            $from->setBalance($newFromBalance);
            $to->setBalance($newToBalance);

            // Create Transaction record
            $txn = new Transaction();
            $txn->setFromAccount($from);
            $txn->setToAccount($to);
            $txn->setAmount((float) $payload['amount']);
            $txn->setStatus('COMPLETED');

            $this->em->persist($txn);

            $this->em->flush();
            $this->em->commit();

            $this->transferLogger->info('Transfer processed successfully', ['transactionId' => $payload['transactionId']]);

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->transferLogger->error('Transfer processing failed: ' . $e->getMessage(), ['payload' => $payload, 'exception' => $e]);
            // Implementing retry logic or dead-letter queue would be next step
        }
    }
}
