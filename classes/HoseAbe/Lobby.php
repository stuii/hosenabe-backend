<?php

namespace HoseAbe;

use HoseAbe\Debug\Logger;
use HoseAbe\Enums\MemberRole;
use HoseAbe\Messages\Message;
use Ramsey\Uuid\Uuid;
use Ratchet\ConnectionInterface;
use stdClass;

class Lobby
{
    public const INVITE_CODE_LENGTH = [3,3];

    public string $uuid;
    /** @var array<LobbyMember> $members  */
    public array $members = [];
    public string $name;
    //public ?string $password;
    public int $maxMembers = 4;
    public int $inviteCode;

    public function __construct()
    {
        $this->regenerateNewUuid();
        $this->regenerateNewInviteCode();
    }

    public function hydrateFromMessage(stdClass $data, Player $owner): void
    {
        $this->name = $data->name;
        //$this->password = $data->password;
        $this->maxMembers = $data->members;
        $this->members[] = new LobbyMember($owner, MemberRole::OWNER);
    }

    public static function handleMessage(ConnectionInterface $connection, stdClass $message): void
    {
        Logger::log('LOBBY', 'Handling lobby message');
        switch ($message->action) {
            case 'create':
                Logger::log('LOBBY', 'Creating new lobby');
                $lobby = new Lobby();
                $lobby->hydrateFromMessage(
                    $message->data,
                    Player::find($connection->resourceId)
                );
                $hoseAbe = HoseAbe::getInstance();
                $hoseAbe->addLobby($lobby);

                Message::send($connection, $lobby->render());
                //todo: return lobby data
                break;
            case 'join':
                Logger::log('LOBBY', 'Player with ID >'.$connection->resourceId.'< wants to join lobby by invite code ('.$message->data->invite.')');
                $player = Player::find($connection->resourceId);
                $lobby = Lobby::findByInviteCode($message->data->invite);
                $player->joinLobby($lobby);

                break;
            case 'leave':

                break;
            default:
                //todo: log
                $connection->send('Action does not exist');
        }
    }

    public function regenerateNewUuid(): void
    {
        $this->uuid = Uuid::uuid4();
    }

    public function addMember(Player $player): void
    {
        $hoseAbe = HoseAbe::getInstance();
        $hoseAbe->userLobbies[$player->connection->resourceId] = $this->uuid;
        $this->members[$player->uuid] = new LobbyMember($player, MemberRole::MEMBER);
    }

    public function removeMember(Player $player): void
    {
        Logger::log('LOBBY', 'Removing Player from lobby.');
        $hoseAbe = HoseAbe::getInstance();
        unset($hoseAbe->userLobbies[$player->connection->resourceId]);
        unset($this->members[$player->uuid]);
echo count($this->members);
        if (count($this->members) <= 0) {
            Logger::log('LOBBY', 'Lobby empty, removing lobby.');
            $this->removeLobby();
        }
    }

    public static function find($lobbyId): ?Lobby
    {
        $hoseAbe = HoseAbe::getInstance();
        return $hoseAbe->lobbies[$lobbyId];
    }

    public static function findByInviteCode(string $inviteCode): ?Lobby
    {
        $inviteCode = preg_replace('/[A-Za-z-_\s]/', '', $inviteCode);
        $hoseAbe = HoseAbe::getInstance();
        $lobbyId = $hoseAbe->inviteCodes[$inviteCode];
        return $hoseAbe->lobbies[$lobbyId];
    }

    public function regenerateNewInviteCode(): void
    {
        $length = array_sum(self::INVITE_CODE_LENGTH);
        $this->inviteCode = rand(
            ((int)str_repeat('9', $length -1) + 1),
            ((int)str_repeat('9', $length))
        );
    }

    public function render(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'inviteCode' => $this->formatInviteCode()
        ];
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

    public function lobbyMessage(string $string, ?Player $player = null): void
    {
        foreach ($this->members as $member) {
            if (is_null($player) || $member->player->uuid !== $player->uuid) {
                $member->player->connection->send($string);
            }
        }
    }

    private function removeLobby()
    {
        $hoseAbe = HoseAbe::getInstance();
        unset($hoseAbe->lobbies[$this->uuid]);
    }
}