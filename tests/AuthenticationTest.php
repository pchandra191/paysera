<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticationTest extends WebTestCase
{
    public function testRegistration(): void
    {
        $client = static::createClient();
        $email = 'register_' . uniqid() . '@example.com';

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => 'password123'
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testLogin(): void
    {
        $client = static::createClient();
        $email = 'login_' . uniqid() . '@example.com';

        // Register first
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => 'secret'
        ]));

        // Login
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $email,
            'password' => 'secret'
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }
}
