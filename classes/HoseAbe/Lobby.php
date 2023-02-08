<?php

namespace HoseAbe;

use HoseAbe\Enums\MemberRole;
use Ramsey\Uuid\Uuid;
use Ratchet\ConnectionInterface;

class Lobby
{
    public string $uuid;
    public array $members = [];
    public string $name;
    public ?string $password;
    public int $maxMembers = 4;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function hydrateFromMessage(\stdClass $data, Player $owner): void
    {
        $this->name = $data->name;
        $this->password = $data->password;
        $this->maxMembers = $data->members;
        $this->members[] = new LobbyMember($owner, MemberRole::OWNER);
    }

    public static function handleMessage(ConnectionInterface $connection, \stdClass $message)
    {
        switch ($message->action) {
            case 'create':
                $lobby = new Lobby();
                $lobby->hydrateFromMessage(
                    $message->data,
                    Player::find($connection->resourceId)
                );
                $hoseAbe = HoseAbe::getInstance();
                $hoseAbe->addLobby($lobby);

                //todo: return lobby data
                break;
            case 'join':

                break;
            case 'leave':

                break;
            default:
                //todo: log
                $connection->send('Action does not exist');
        }
    }

    public function regenerateNewUuid()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function addMember()
    {
        $hoseAbe = HoseAbe::getInstance();
        // todo
    }

    public static function find($lobbyId): Lobby
    {
        $hoseAbe = HoseAbe::getInstance();
        return $hoseAbe->lobbies[$lobbyId];
    }
}