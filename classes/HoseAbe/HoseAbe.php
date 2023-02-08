<?php

namespace HoseAbe;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class HoseAbe implements MessageComponentInterface
{
    protected static ?HoseAbe $instance = null;
    public array $clients = [];
    public array $lobbies = [];
    public array $userLobbies = [];

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
        echo "New connection ({$conn->resourceId})\n";
        $player = new Player($conn);
        $this->clients[$conn->resourceId] = $player;

        $player->sendWelcomeMessage();
        echo "Welcome message sent to {$conn->resourceId}\n";
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received...\n";
        MessageHandler::handle($from, $msg);
    }

    function onClose(ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }




    public function addLobby(Lobby $lobby)
    {
        while (isset($this->lobbies[$lobby->uuid])) {
            $lobby->regenerateNewUuid();
        }
        $this->lobbies[$lobby->uuid] = $lobby;
    }
}