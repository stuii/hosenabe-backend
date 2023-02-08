<?php

namespace HoseAbe;

use Ratchet\ConnectionInterface;

class MessageHandler
{

    public static function handle(ConnectionInterface $connection, string $message)
    {
        $message = json_decode($message);
        switch ($message->ns) {
            case 'lob':
                Lobby::handleMessage($connection, $message);
        }
    }
}