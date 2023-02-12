<?php /** @noinspection ALL */

namespace HoseAbe;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Messages\Error;
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

    public array $usernames = [];

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
        $resourceId = $conn->resourceId;
        Logger::log('CONNECT', 'New Connection with ID >' . $resourceId . '<');
        $player = new Player($conn);
        $this->clients[$resourceId] = $player;

        Logger::log('CONNECT', 'Sending Welcome message to ID >' . $resourceId . '<');
        $player->sendWelcomeMessage();
        Logger::log('CONNECT', 'Welcome message sent to ID >' . $resourceId . '<');
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::log('MESSAGE', 'Received message from ID >' . $from->resourceId . '<');
        MessageHandler::handle($from, $msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        Logger::log('CLOSE', 'Client with ID >' . $conn->resourceId . '< disconnected');

        try {
            $lobby = Player::findLobby($conn);
            $player = Player::find($conn);
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