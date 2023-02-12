<?php /** @noinspection PhpUndefinedFieldInspection */

namespace HoseAbe;

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
                $username = $message->data->username;
                $hoseAbe = HoseAbe::getInstance();
                if (isset($hoseAbe->usernames[$username])) {
                    Error::send($connection, 403, 'Username already exists');
                    return;
                }

                try {
                    $player = Player::find($connection);
                } catch (Exception $e) {
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }
                $player->username = $username;
                $hoseAbe->usernames[$username] = $username;
                Message::send($connection, Context::PLAYER, 'Username set', $player->render());
                break;
            default:
                Error::send($connection, 404, 'Action does not exist');
                break;
        }
    }

    /**
     * @throws Exception
     */
    public static function find(ConnectionInterface $connection): Player
    {
        $resourceId = $connection->resourceId;
        $hoseAbe = HoseAbe::getInstance();
        if (!isset($hoseAbe->clients[$resourceId])) {
            throw new Exception('Player not found', 404);
        }
        return $hoseAbe->clients[$resourceId];
    }

    /**
     * @throws Exception
     */
    public static function findLobby(ConnectionInterface $connection): ?Lobby
    {
        $resourceId = $connection->resourceId;
        $hoseAbe = HoseAbe::getInstance();
        if (!isset($hoseAbe->userLobbies[$resourceId])) {
            return null;
        }

        $lobbyId = $hoseAbe->userLobbies[$resourceId];
        if (!isset($hoseAbe->lobbies[$lobbyId])) {
            throw new Exception('Lobby not found', 404);
        }

        return $hoseAbe->lobbies[$lobbyId];
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

        $lobby = Lobby::find($this->currentLobby);
        $lobby->removeMember($this);
        $this->currentLobby = null;
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        $this->leaveLobby();
        $hoseAbe = HoseAbe::getInstance();
        if (isset($hoseAbe->clients[$this->getResourceId()])) {
            unset($hoseAbe->clients[$this->getResourceId()]);
        }
        if(isset($hoseAbe->usernames[$this->username])) {
            unset($hoseAbe->usernames[$this->username]);
        }
    }

    public function setLobby(Lobby $lobby): void
    {
        $this->currentLobby = $lobby->uuid;

        $hoseAbe = HoseAbe::getInstance();
        $hoseAbe->userLobbies[$this->getResourceId()] = $lobby->uuid;
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