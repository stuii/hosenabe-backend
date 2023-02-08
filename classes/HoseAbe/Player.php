<?php

namespace HoseAbe;

use Cassandra\Uuid;
use HoseAbe\Resources\RandomString;
use Ratchet\ConnectionInterface;

class Player
{
    public ?string $username = null;
    public string $uuid;
    public string $secret;

    public function __construct(
        public ConnectionInterface $connection
    )
    {
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->secret = RandomString::generate();
    }

    public function sendWelcomeMessage(): void
    {
        $this->connection->send(json_encode($this->getPlayerBoot()));
    }

    private function getPlayerBoot(): array
    {
        return [
            'uuid' => $this->uuid,
            'secret' => $this->secret
        ];
    }
}