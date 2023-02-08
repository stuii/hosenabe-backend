<?php

namespace HoseAbe;

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

    public function hydrateFromMessage(\stdClass $data, $owner): void
    {
        $this->name = $data->name;
        $this->password = $data->password;
        $this->maxMembers = $data->members;
        $this->members[] = new LobbyMember($owner);
    }

    public static function handleMessage(ConnectionInterface $connection, \stdClass $message)
    {
        switch ($message->action) {
            case 'create':
                $lobby = new Lobby();
                $lobby->hydrateFromMessage(
                    $message->data,
                    $connection->resourceId
                );
                var_dump($lobby);
                $lobbies = [];
                $lobbies[$lobby->uuid] = $lobby;
                var_dump($lobbies);
        }
    }
}