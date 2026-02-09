<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Account;
use Psr\Log\LoggerInterface;

#[Route('/api', name: 'api_')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/account', name: 'create_account', methods: ['POST'])]
    public function createAccount(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Basic validation
        if (!isset($data['balance'])) {
            return $this->json(['error' => 'Invalid request payload, balance required'], 400);
        }

        try {
            if ($data['balance'] < 0) {
                throw new \InvalidArgumentException("Balance cannot be negative");
            }

            $account = new Account();
            $account->setUser($user);
            $account->setOwner($user->getUserIdentifier());
            $account->setBalance((string) $data['balance']);

            $this->em->persist($account);
            $this->em->flush();

            $this->logger->info('Account created successfully', [
                'accountId' => $account->getId(),
                'owner' => $account->getOwner(),
                'balance' => $account->getBalance()
            ]);

            return $this->json([
                'accountId' => $account->getId(),
                'owner' => $account->getOwner(),
                'balance' => $account->getBalance(),
                'status' => 'CREATED'
            ], 201);

        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Account creation failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->json(['error' => $e->getMessage()], 400);

        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->critical('Database error during account creation', ['exception' => $e]);
            return $this->json(['error' => 'Database error occurred'], 500);

        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error during account creation', ['exception' => $e]);
            return $this->json(['error' => 'Unexpected error occurred'], 500);
        }
    }
}
