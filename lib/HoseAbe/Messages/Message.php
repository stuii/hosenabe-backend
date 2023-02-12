<?php

namespace HoseAbe\Messages;

use HoseAbe\Enums\Context;
use Ratchet\ConnectionInterface;

class Message
{
    public static function send(
        ConnectionInterface $connection,
        Context $context,
        ?string $message = null,
        array $data = []
    ): void
    {
        $connection->send(
            json_encode([
                'cx' => $context,
                'message' => $message,
                'data' => $data
            ])
        );
    }
}