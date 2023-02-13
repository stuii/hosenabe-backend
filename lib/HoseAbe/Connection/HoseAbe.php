<?php /** @noinspection ALL */

namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Messages\Error;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class HoseAbe implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $conn)
    {
        Logger::log('CONNECT', 'New Connection');
        $player = new Player($conn);
        ConnectionHandler::addPlayer($player);
        $player->sendWelcomeMessage();
        Logger::log('CONNECT', 'Welcome message sent');
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::log('MESSAGE', 'Received message');
        MessageHandler::handle($from, $msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        Logger::log('CLOSE', 'Client disconnected');

        try {
            $player = ConnectionHandler::getPlayer($conn);
            $lobby = ConnectionHandler::getPlayerLobby($player);
            $player->disconnect();
        } catch(Exception $e) {
            Error::send($conn, $e->getCode(), $e->getMessage());
            return;
        }

        if (!is_null($lobby)) {
            $lobby->sendLobbyUpdate('Player disconnected');
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $lobby->sendLobbyUpdate('An error occurred');
        Error::send($conn, $e->getCode(), $e->getMessage());
    }
}