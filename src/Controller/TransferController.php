<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TransferService;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/api', name: 'api_')]
class TransferController extends AbstractController
{
    public function __construct(
        private TransferService $transferService,
        private LoggerInterface $transferLogger
    ) {
    }

    #[Route('/transfer', name: 'transfer', methods: ['POST'])]
    public function transfer(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Basic validation
        if (!isset($data['fromAccountId'], $data['toAccountId'], $data['amount'])) {
            $this->transferLogger->warning('Invalid transfer payload', ['payload' => $data]);
            return $this->json(['error' => 'Invalid request payload'], 400);
        }

        try {
            $transactionId = $this->transferService->enqueueTransfer(
                $user,
                $data['fromAccountId'],
                $data['toAccountId'],
                (float) $data['amount'],
                null // idempotency key if needed
            );

            $this->transferLogger->info('Transfer request queued successfully', [
                'transactionId' => $transactionId,
                'fromAccountId' => $data['fromAccountId'],
                'toAccountId' => $data['toAccountId'],
                'amount' => $data['amount']
            ]);

            return $this->json([
                'transactionId' => $transactionId,
                'status' => 'QUEUED'
            ], 200);

        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();

            if (stripos($message, 'insufficient funds') !== false) {
                $this->transferLogger->warning('Transfer failed: Insufficient funds', [
                    'fromAccountId' => $data['fromAccountId'],
                    'toAccountId' => $data['toAccountId'],
                    'amount' => $data['amount']
                ]);
            } else {
                $this->transferLogger->error('Transfer validation failed: ' . $message, ['exception' => $e]);
            }

            return $this->json(['error' => $message], 400);

        } catch (\Doctrine\DBAL\Exception $e) {
            $this->transferLogger->critical('Database error during transfer', ['exception' => $e]);
            return $this->json(['error' => 'Database error occurred'], 500);

        } catch (\Throwable $e) {
            $this->transferLogger->critical('Unexpected error during transfer', ['exception' => $e]);
            return $this->json(['error' => 'Unexpected error occurred'], 500);
        }
    }
}
