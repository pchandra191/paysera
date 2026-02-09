<?php

namespace App\Service;

use Predis\Client;

class RedisService
{
    private Client $client;

    public function __construct(string $redisUrl)
    {
        $this->client = new Client($redisUrl);
    }

    public function push(string $queue, array $payload): void
    {
        $this->client->lpush($queue, [json_encode($payload)]);
    }

    public function pop(string $queue): ?array
    {
        $data = $this->client->rpop($queue);
        return $data ? json_decode($data, true) : null;
    }
}
