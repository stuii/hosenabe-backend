<?php

namespace HoseAbe\Messages;

use Ratchet\ConnectionInterface;

class Error
{
    public static function send(ConnectionInterface $connection, int $code, string $message): void
    {
        $connection->send(
            json_encode(
                [
                    'error' => [
                        'message' => $message,
                        'code' => $code,
                    ]
                ]
            )
        );
    }
}