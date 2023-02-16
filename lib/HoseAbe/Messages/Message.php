<?php

namespace HoseAbe\Messages;

use HoseAbe\Enums\Action;
use HoseAbe\Enums\Context;
use Ratchet\ConnectionInterface;

class Message
{
    public static function send(
        ConnectionInterface $connection,
        Context $context,
        Action $action,
        ?string $message = null,
        array $data = []
    ): void
    {
        $connection->send(
            json_encode([
                'status' => 'ok',
                'code' => 200, //TODO
                'cx' => $context,
                'action' => $action,
                'message' => $message,
                'data' => $data
            ])
        );
    }
}