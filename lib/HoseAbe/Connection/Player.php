<?php /** @noinspection PhpUndefinedFieldInspection */


namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Enums\Context;
use HoseAbe\Messages\Error;
use HoseAbe\Messages\Message;
use JetBrains\PhpStorm\ArrayShape;
use Ratchet\ConnectionInterface;
use stdClass;

class Player
{
    public ?string $username = null;
    public ?string $currentLobby = null;

    /** @noinspection PhpUnused */
    public function __construct(
        public ConnectionInterface $connection
    )
    {
    }
    public static function handleMessage(ConnectionInterface $connection, stdClass $message): void
    {
        Logger::log('PLAYER', 'Handling player message');
        switch ($message->action) {
            case 'login':
                try {
                    $player = ConnectionHandler::getPlayer($connection);
                } catch (Exception $e) {
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }

                $newUsername = $message->data->username;
                if (ConnectionHandler::checkUsernameIsTaken($newUsername)) {
                    Error::send($connection, 403, 'Username already exists');
                    return;
                }

                if (!is_null($player->username)) {
                    ConnectionHandler::removeUsername($player);
                }
                $player->username = $newUsername;
                ConnectionHandler::addPlayer($player);
                ConnectionHandler::addUsername($player);

                Message::send($connection, Context::PLAYER, 'Username set', $player->render());
                break;
            default:
                Error::send($connection, 404, 'Action does not exist');
                break;
        }
    }

    public function sendWelcomeMessage(): void
    {
        Message::send($this->connection, Context::PLAYER, 'Welcome', []);
    }

    /**
     * @throws Exception
     */
    public function joinLobby(Lobby $lobby): LobbyMember
    {
        if (is_null($this->username)) {
            throw new Exception('Cannot join lobby without username', 403);
        }
        if ($lobby->uuid === $this->currentLobby) {
            throw new Exception('User is already in party', 400);
        }

        if (!is_null($this->currentLobby)) {
            $this->leaveLobby();
        }
        $this->setLobby($lobby);
        $lobbyMember = $lobby->addMember($this);
        Logger::log('PLAYER', 'Player joined lobby.');

        return $lobbyMember;
    }

    /**
     * @throws Exception
     */
    public function leaveLobby(): void
    {
        Logger::log('PLAYER', 'Player is leaving lobby.');
        if (is_null($this->currentLobby)) {
            throw new Exception('Player has no lobby', 404);
        }

        $lobby = ConnectionHandler::getPlayerLobby($this);
        $lobby->removeMember($this);
        $this->currentLobby = null;
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        $this->leaveLobby();
        ConnectionHandler::removePlayer($this);
    }

    public function setLobby(Lobby $lobby): void
    {
        $this->currentLobby = $lobby->uuid;
        ConnectionHandler::setPlayerLobby($this, $lobby);
    }

    #[ArrayShape(['username' => "null|string"])]
    public function render(): array
    {
        return [
            'username' => $this->username
        ];
    }

    public function getResourceId()
    {
        return $this->connection->resourceId;
    }
}