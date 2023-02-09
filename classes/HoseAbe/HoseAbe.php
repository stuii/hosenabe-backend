<?php

namespace HoseAbe;

use HoseAbe\Debug\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class HoseAbe implements MessageComponentInterface
{
    protected static ?HoseAbe $instance = null;

    /** @var array<int, Player> $clients
     * resourceId => player-object
     */
    public array $clients = [];

    /** @var array<string, Lobby> $lobbies
     * lobby-uuid => lobby-obj
     */
    public array $lobbies = [];

    /** @var array<string, string> $inviteCodes
     * invitecode => lobby-uuid
     */
    public array $inviteCodes = [];

    /** @var array<> $userLobbies
     * resourceId => lobby-uuid
     */
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
        Logger::log('CONNECT', 'New Connection with ID >' . $conn->resourceId . '<');
        $player = new Player($conn);
        $this->clients[$conn->resourceId] = $player;

        Logger::log('CONNECT', 'Sending Welcome message to ID >' . $conn->resourceId . '<');
        $player->sendWelcomeMessage();
        Logger::log('CONNECT', 'Welcome message sent to ID >' . $conn->resourceId . '<');
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::log('MESSAGE', 'Received message from ID >' . $from->resourceId . '<');
        MessageHandler::handle($from, $msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        Logger::log('CLOSE', 'Client with ID >' . $conn->resourceId . '< disconnected');
        $player = $this->clients[$conn->resourceId];
        unset($this->clients[$conn->resourceId]);
        unset($this->clients[$conn->resourceId]);
        // TODO: Implement onClose() method.
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function addLobby(Lobby &$lobby)
    {
        Logger::log('LOBBY', 'Adding Lobby ('.$lobby->name.') to storage');
        while (isset($this->lobbies[$lobby->uuid])) {
            $lobby->regenerateNewUuid();
        }
        while (isset($this->inviteCodes[$lobby->inviteCode])) {
            $lobby->regenerateNewInviteCode();
        }
        $this->lobbies[$lobby->uuid] = $lobby;
        $this->inviteCodes[$lobby->inviteCode] = $lobby->uuid;
    }
}