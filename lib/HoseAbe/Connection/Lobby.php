<?php

namespace HoseAbe\Connection;

use Exception;
use HoseAbe\Debug\Logger;
use HoseAbe\Enums\Context;
use HoseAbe\Enums\LobbyStatus;
use HoseAbe\Enums\MemberRole;
use HoseAbe\Messages\Error;
use HoseAbe\Messages\Message;
use JetBrains\PhpStorm\ArrayShape;
use Ramsey\Uuid\Uuid;
use Ratchet\ConnectionInterface;
use stdClass;

class Lobby
{
    public const INVITE_CODE_LENGTH = [2,2];

    public ?string $uuid = null;
    /** @var array<LobbyMember> $members  */
    public array $members = [];
    public string $name;
    //public ?string $password;
    public int $maxMembers = 4;
    public ?int $inviteCode = null;

    public LobbyStatus $status;

    /** @noinspection PhpUnused */
    public function __construct()
    {
        $this->regenerateNewUuid();
        $this->regenerateNewInviteCode();
        $this->status = LobbyStatus::OPEN;
    }

    /**
     * @throws Exception
     */
    public function hydrateFromMessage(stdClass $data, Player $owner): void
    {
        if (is_null($owner->username)) {
            throw new Exception('Cannot create lobby without username', 403);
        }
        $this->name = $data->name;
        $this->maxMembers = $data->members;
        $this->addMember($owner, MemberRole::OWNER);
    }

    public static function handleMessage(ConnectionInterface $connection, stdClass $message): void
    {
        Logger::log('LOBBY', 'Handling lobby message');
        switch ($message->action) {
            case 'create':
                Logger::log('LOBBY', 'Creating new lobby');
                $lobby = new Lobby();
                try {
                    $lobby->hydrateFromMessage(
                        $message->data,
                        ConnectionHandler::getPlayer($connection)
                    );
                } catch(Exception $e) {
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }
                ConnectionHandler::addLobby($lobby);

                Message::send($connection, Context::LOBBY, 'Successfully created Lobby', ['lobby' => $lobby->render()]);
                //todo: return lobby data
                break;
            case 'join':
                Logger::log('LOBBY', 'Player wants to join lobby by invite code ('.$message->data->invite.')');
                try {
                    $player = ConnectionHandler::getPlayer($connection);
                    $lobby = ConnectionHandler::getLobbyByCode($message->data->invite);
                    $lobbyMember = $player->joinLobby($lobby);
                } catch(Exception $e){
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }

                $lobby->sendLobbyUpdate('New Player joined', $lobbyMember);

                Message::send($connection, Context::LOBBY, 'Successfully created Lobby', ['lobby' => $lobby->render()]);
                break;
            case 'leave':
                Logger::log('LOBBY', 'Player left lobby');
                try {
                    $player = ConnectionHandler::getPlayer($connection);
                    $player->leaveLobby();
                } catch (Exception $e){
                    Error::send($connection, $e->getCode(), $e->getMessage());
                }
                break;
            default:
                //todo: log
                Error::send($connection, 404, 'Action does not exist');
        }
    }

    public function regenerateNewUuid(): void
    {
        while(is_null($this->uuid) || ConnectionHandler::checkLobbyUuidIsTaken($this->uuid)) {
            $this->uuid = Uuid::uuid4();
        }
    }

    public function addMember(Player $player, MemberRole $role = MemberRole::MEMBER): LobbyMember
    {
        $resourceId = $player->getResourceId();
        ConnectionHandler::setPlayerLobby($player, $this);
        $lobbyMember = new LobbyMember($player, $role);
        $this->members[$resourceId] = $lobbyMember;
        $player->currentLobby = $this->uuid;

        return $lobbyMember;
    }

    /**
     * @throws Exception
     */
    public function removeMember(Player $player): void
    {
        $resourceId = $player->getResourceId();
        Logger::log('LOBBY', 'Removing Player from lobby.');
        $lobby = ConnectionHandler::getPlayerLobby($player);
        ConnectionHandler::removePlayerLobby($player);
        if(isset($this->members[$resourceId])) {
            unset($this->members[$resourceId]);
        }

        if (count($this->members) <= 0) {
            Logger::log('LOBBY', 'Lobby empty, removing lobby.');
            ConnectionHandler::removeLobby($this);
        } else {
            $lobby->promoteNewOwner();
            $this->sendLobbyUpdate('Player disconnected');
        }
        Message::send($player->connection, Context::LOBBY, 'Left lobby');
    }

    public function promoteNewOwner(): void
    {
        $newOwner = array_keys($this->members)[0];
        $this->members[$newOwner]->role = MemberRole::OWNER;
    }

    public function regenerateNewInviteCode(): void
    {
        $length = array_sum(self::INVITE_CODE_LENGTH);

        while(is_null($this->inviteCode) || ConnectionHandler::checkInviteCodeIsTaken($this->inviteCode)) {
            $this->inviteCode = rand(
                ((int)str_repeat('9', $length -1) + 1),
                ((int)str_repeat('9', $length))
            );
        }
    }

    #[ArrayShape(['uuid' => "null|string", 'name' => "string", 'inviteCode' => "string", 'members' => "array"])]
    public function render(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'inviteCode' => $this->formatInviteCode(),
            'members' => $this->renderMembers()
        ];
    }
    private function renderMembers(): array
    {
        $members = [];
        foreach ($this->members as $member) {
            $members[] = $member->render();
        }
        return $members;
    }

    private function formatInviteCode(): string
    {
        $formattedCode = '';
        $code = $this->inviteCode;
        foreach (self::INVITE_CODE_LENGTH as $length) {
            $formattedCode .= substr($code, 0, $length) . ' ';
            $code = substr($code,$length);
        }
        return substr($formattedCode, 0, -1);
    }

    public function sendLobbyUpdate(string $message, ?LobbyMember $excludeMember = null): void
    {
        foreach($this->members as $member) {
            if (
                !is_null($excludeMember) &&
                $excludeMember->player->getResourceId() === $member->player->getResourceId()
            ) { continue; }
            Message::send(
                $member->player->connection,
                Context::LOBBY,
                $message,
                ['lobby' => $this->render()]
            );
        }
    }
}