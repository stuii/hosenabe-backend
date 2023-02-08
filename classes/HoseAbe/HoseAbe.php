<?php

namespace HoseAbe;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class HoseAbe implements MessageComponentInterface
{
    protected array $clients = [];
    protected array $lobbies = [];

    public function __construct()
    {
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
}