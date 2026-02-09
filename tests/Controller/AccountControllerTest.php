<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    private function getAuthenticatedClient(string &$email = null)
    {
        $client = static::createClient();
        $email = 'account_test_' . uniqid() . '@example.com';
        $password = 'secret';

        // Register
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));

        // Login
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $email,
            'password' => $password
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'];

        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        return $client;
    }

    public function testCreateAccountSuccess(): void
    {
        $email = null;
        $client = $this->getAuthenticatedClient($email);

        $client->request('POST', '/api/account', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'balance' => 1000.00
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('accountId', $response);
        $this->assertEquals($email, $response['owner']);
        $this->assertEquals(1000.00, $response['balance']);
    }

    public function testCreateAccountInvalidPayload(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('POST', '/api/account', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'foo' => 'bar' // Missing balance
        ]));

        $this->assertResponseStatusCodeSame(400); // Should be 400 now
    }

    public function testCreateAccountNegativeBalance(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('POST', '/api/account', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'balance' => -50
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Balance cannot be negative', $response['error']); // Verify message
    }
}
