<?php

namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use Ratchet\ConnectionInterface;

class ConnectionHandler
{
    protected static ?ConnectionHandler $instance = null;

    /** @var array<int, Player> $players
     * resourceId => player-object
     */
    public array $players = [];

    /** @var array<string, Lobby> $lobbies
     * lobby-uuid => lobby-obj
     */
    public array $lobbies = [];

    /** @var array<string, string> $inviteCodes
     * invite-code => lobby-uuid
     */
    public array $inviteCodes = [];

    /** @var array<> $userLobbies
     * resourceId => lobby-uuid
     */
    public array $userLobbies = [];

    /** @var array<string, string> $usernames */
    public array $usernames = [];

    /** @noinspection PhpUnused */
    protected function __construct() {}

    /** @noinspection PhpUnused */
    protected function __clone() {}

    public static function getInstance(): ConnectionHandler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function addPlayer(Player $player): void
    {
        Logger::log('PLAYER', 'Adding Player ('.$player->getResourceId().') to storage');

        $handler = self::getInstance();
        $handler->players[$player->getResourceId()] = $player;
    }

    public static function removePlayer(Player $player): void
    {
        Logger::log('PLAYER', 'Removing Player ('.$player->getResourceId().') from storage');

        $handler = self::getInstance();
        if (isset($handler->clients[$player->getResourceId()])) {
            unset($handler->clients[$player->getResourceId()]);
        }
        if(!is_null($player->username) && isset($handler->usernames[$player->username])) {
            unset($handler->usernames[$player->username]);
        }
    }

    /**
     * @throws Exception
     */
    public static function getPlayer(ConnectionInterface $connection): Player
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return self::getPlayerByResourceId($connection->resourceId);
    }

    /**
     * @throws Exception
     */
    public static function getPlayerByResourceId($resourceId): Player
    {
        $handler = self::getInstance();
        if (!isset($handler->players[$resourceId])) {
            throw new Exception();
        }
        return $handler->players[$resourceId];
    }

    public static function setPlayerLobby(Player $player, Lobby $lobby): void
    {
        Logger::log('LOBBY', 'Adding PlayerLobby ('.$player->username.') to storage');

        $handler = self::getInstance();
        $handler->userLobbies[$player->getResourceId()] = $lobby->uuid;
    }

    /**
     * @throws Exception
     */
    public static function getPlayerLobby(Player $player): Lobby
    {
        $handler = self::getInstance();
        if (!isset($handler->userLobbies[$player->getResourceId()])) {
            throw new Exception();
        }

        $lobbyId = $handler->userLobbies[$player->getResourceId()];
        return self::getLobby($lobbyId);
    }

    public static function removePlayerLobby(Player $player): void
    {
        Logger::log('LOBBY', 'Removing PlayerLobby ('.$player->username.') from storage');

        $handler = self::getInstance();
        if(isset($handler->userLobbies[$player->getResourceId()])) {
            unset($handler->userLobbies[$player->getResourceId()]);
        }
    }

    public static function addUsername(Player $player): void
    {
        Logger::log('PLAYER', 'Adding Username ('.$player->username.') to storage');

        $handler = self::getInstance();
        $handler->usernames[$player->username] = $player->getResourceId();
    }

    public static function removeUsername(Player $player): void
    {
        Logger::log('PLAYER', 'Removing Username ('.$player->username.') from storage');

        $handler = self::getInstance();
        if (isset($handler->usernames[$player->username])) {
            unset($handler->usernames[$player->username]);
        }
    }

    public static function checkUsernameIsTaken(string $username): bool
    {
        $handler = self::getInstance();
        return isset($handler->usernames[$username]);
    }

    public static function addLobby(Lobby $lobby): void
    {
        Logger::log('LOBBY', 'Adding Lobby ('.$lobby->name.') to storage');

        $handler = self::getInstance();
        $handler->lobbies[$lobby->uuid] = $lobby;
        $handler->inviteCodes[$lobby->inviteCode] = $lobby->uuid;
    }

    public static function removeLobby(Lobby $lobby): void
    {
        Logger::log('LOBBY', 'Removing Lobby ('.$lobby->name.') from storage');

        $handler = self::getInstance();
        if (isset($handler->lobbies[$lobby->uuid])) {
            unset($handler->lobbies[$lobby->uuid]);
        }

        if (isset($handler->inviteCodes[$lobby->inviteCode])) {
            unset($handler->inviteCodes[$lobby->inviteCode]);
        }
    }

    /**
     * @throws Exception
     */
    public static function getLobby(string $lobbyId): Lobby
    {
        $handler = self::getInstance();
        if (!isset($handler->lobbies[$lobbyId])) {
            throw new Exception();
        }
        return $handler->lobbies[$lobbyId];
    }

    public static function checkLobbyUuidIsTaken(?string $uuid): bool
    {
        $handler = self::getInstance();
        if (is_null($uuid)) { return true; }
        return isset($handler->lobbies[$uuid]);
    }

    /**
     * @throws Exception
     */
    public static function getLobbyByCode(string $inviteCode): ?Lobby
    {
        $inviteCode = preg_replace('/[A-Za-z-_\s]/', '', $inviteCode);
        $handler = self::getInstance();

        if (!isset($handler->inviteCodes[$inviteCode])) {
            throw new Exception('Could not find lobby', 404);
        }
        $lobbyId = $handler->inviteCodes[$inviteCode];

        if (!isset($handler->lobbies[$lobbyId])) {
            throw new Exception('Could not find lobby', 404);
        }
        return $handler->lobbies[$lobbyId];
    }

    public static function checkInviteCodeIsTaken(?int $inviteCode): bool
    {
        $handler = self::getInstance();
        return isset($handler->inviteCodes[$inviteCode]);
    }
}