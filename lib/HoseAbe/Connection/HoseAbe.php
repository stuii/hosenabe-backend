<?php /** @noinspection ALL */

namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Messages\Error;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class HoseAbe implements MessageComponentInterface
{
    protected static ?HoseAbe $instance = null;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public static function getInstance(): HoseAbe
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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
        // TODO: Implement onError() method.
    }

    public function addLobby(Lobby $lobby)
    {
        Logger::log('LOBBY', 'Adding Lobby ('.$lobby->name.') to storage');
        $this->lobbies[$lobby->uuid] = $lobby;
        $this->inviteCodes[$lobby->inviteCode] = $lobby->uuid;
    }
}