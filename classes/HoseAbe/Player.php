<?php

namespace HoseAbe;

use HoseAbe\Resources\RandomString;
use Ramsey\Uuid\Uuid;
use Ratchet\ConnectionInterface;

class Player
{
    public ?string $username = null;
    public string $uuid;
    public string $secret;

    public ?string $currentLobby = null;

    public function __construct(
        public ConnectionInterface $connection
    )
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->secret = RandomString::generate();
    }

    public static function find($resourceId): Player
    {
        $hoseAbe = HoseAbe::getInstance();
        return $hoseAbe->clients[$resourceId];
    }

    public static function findLobby($resourceId): Lobby
    {
        $hoseAbe = HoseAbe::getInstance();
        $lobbyId = $hoseAbe->userLobbies[$resourceId];
        return $hoseAbe->lobbies[$lobbyId];
    }

    public function sendWelcomeMessage(): void
    {
        $this->connection->send(json_encode($this->getPlayerBoot()));
    }

    private function getPlayerBoot(): array
    {
        return [
            'uuid' => $this->uuid,
            'secret' => $this->secret
        ];
    }

    public function joinLobby(Lobby $lobby)
    {
        //todo: leave current lobby

        $this->currentLobby = $lobby->uuid;
        $lobby->addMember($this);
    }
}