<?php /** @noinspection PhpUndefinedFieldInspection */


namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Enums\Action;
use HoseAbe\Enums\Context;
use HoseAbe\Messages\Error;
use HoseAbe\Messages\Message;
use HoseAbe\Resources\RandomString;
use JetBrains\PhpStorm\ArrayShape;
use Ratchet\ConnectionInterface;
use stdClass;

class Player
{
    public ?string $username = null;
    public ?string $currentLobby = null;
    public string $reconnectionToken;

    /** @noinspection PhpUnused */
    public function __construct(
        public ConnectionInterface $connection
    )
    {
        $this->reconnectionToken = RandomString::generate(100);
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
                ConnectionHandler::addReconnectionToken($player);

                Message::send($connection, Context::PLAYER, Action::LOGIN, 'Username set', $player->render());
                break;
            case 'reconnect':
                try {
                    $player = ConnectionHandler::getPlayerByReconnectionToken($connection, $message->data->reconnectToken);
                } catch (Exception $e) {
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }

                Message::send($connection, Context::PLAYER, Action::RECONNECT, 'Reconnected successfully');
                try {
                    $lobby = ConnectionHandler::getPlayerLobby($player);
                    Message::send($connection, Context::LOBBY,Action::LOBBY_UPDATE,  'Lobby rejoined', ['lobby' => $lobby->render()]);
                } catch(Exception) {}

                break;
            default:
                Error::send($connection, 404, 'Action does not exist');
                break;
        }
    }

    public function sendWelcomeMessage(): void
    {
        Message::send($this->connection, Context::PLAYER, Action::CONNECT, 'Welcome', []);
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

    #[ArrayShape(['username' => "string", 'reconnectionToken' => "string"])]
    public function render(): array
    {
        return [
            'username' => $this->username,
            'reconnectionToken' => $this->reconnectionToken
        ];
    }

    public function getResourceId()
    {
        return $this->connection->resourceId;
    }
}