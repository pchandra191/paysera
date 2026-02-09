<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferControllerTest extends WebTestCase
{
    private function authenticateUser($client, $suffix)
    {
        $email = 'transfer_' . $suffix . '_' . uniqid() . '@example.com';
        $password = 'secret';

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $email,
            'password' => $password
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['token'];
    }

    private function createAccountWithToken($client, $token, $balance)
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
        $client->request('POST', '/api/account', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'balance' => $balance
        ]));
        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['accountId'];
    }

    public function testTransferSuccess(): void
    {
        $client = static::createClient();

        $token1 = $this->authenticateUser($client, 'u1');
        $acc1 = $this->createAccountWithToken($client, $token1, 1000.00);

        $token2 = $this->authenticateUser($client, 'u2');
        $acc2 = $this->createAccountWithToken($client, $token2, 500.00);

        // User 1 transfers to User 2
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token1);
        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'fromAccountId' => $acc1,
            'toAccountId' => $acc2,
            'amount' => 100.00
        ]));

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('transactionId', $response);
        $this->assertEquals('QUEUED', $response['status']);
    }

    public function testTransferFromWrongUser(): void
    {
        $client = static::createClient();

        $token1 = $this->authenticateUser($client, 'u1');
        $acc1 = $this->createAccountWithToken($client, $token1, 1000.00);

        $token2 = $this->authenticateUser($client, 'u2');

        // Client 2 tries to transfer from Client 1's account
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token2);
        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'fromAccountId' => $acc1,
            'toAccountId' => $acc1,
            'amount' => 10.00
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Account does not belong', $response['error']);
    }

    public function testTransferInvalidPayload(): void
    {
        $client = static::createClient();
        $token = $this->authenticateUser($client, 'imp');
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);

        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'fromAccountId' => 1
        ]));

        $this->assertResponseStatusCodeSame(400);
    }
}
