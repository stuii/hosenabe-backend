<?php

namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Enums\Context;
use HoseAbe\Messages\Error;
use JsonException;
use Ratchet\ConnectionInterface;
use stdClass;

class MessageHandler
{

    public static function handle(ConnectionInterface $connection, string $message): void
    {
        try {
            $message = self::check($message);
        } catch (Exception $e) {
            Logger::log('MESSAGE','Invalid Message');
            Error::send($connection, $e->getCode(), $e->getMessage());
            return;
        }

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

    /**
     * @throws Exception
     */
    public static function check(string $message): stdClass
    {
        // check if message is valid json
        try {
            $decodedMessage = json_decode(
                json: $message,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new Exception('Invalid message', 400);
        }

        // check if required message parts are there
        if (
            !isset($decodedMessage->cx) ||
            !isset($decodedMessage->action) ||
            !isset($decodedMessage->data)
        ) {
            throw new Exception('Invalid message', 400);
        }

        return $decodedMessage;
    }
}