<?php

namespace HoseAbe\Messages;

use Ratchet\ConnectionInterface;

class Error implements MessageInterface
{
    public static function send(ConnectionInterface $connection, array $data): void
    {
        $connection->send(
            json_encode(
                ['error' => $data]
            )
        );
    }
}