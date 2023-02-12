<?php

namespace HoseAbe;

use HoseAbe\Enums\Context;
use HoseAbe\Messages\Error;
use Ratchet\ConnectionInterface;

class MessageHandler
{

    public static function handle(ConnectionInterface $connection, string $message): void
    {
        // todo: check message schema
        $message = json_decode($message);
        switch ($message->cx) {
            case Context::PLAYER->value:
                Player::handleMessage($connection, $message);
                break;
            case Context::LOBBY->value:
                Lobby::handleMessage($connection, $message);
                break;
            default:
                Error::send($connection, 404, 'Context does not exist');
                break;
        }
    }
}