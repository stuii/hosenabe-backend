<?php /** @noinspection ALL */

namespace HoseAbe;

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
        //$this->password = $data->password;
        $this->maxMembers = $data->members;
        $this->addMember($owner, MemberRole::OWNER);
    }

    public static function handleMessage(ConnectionInterface $connection, stdClass $message): void
    {
        $resourceId = $connection->resourceId;
        Logger::log('LOBBY', 'Handling lobby message');
        switch ($message->action) {
            case 'create':
                Logger::log('LOBBY', 'Creating new lobby');
                $lobby = new Lobby();
                try {
                    $lobby->hydrateFromMessage(
                        $message->data,
                        Player::find($connection)
                    );
                } catch(Exception $e) {
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }
                $hoseAbe = HoseAbe::getInstance();
                $hoseAbe->addLobby($lobby);

                Message::send($connection, Context::LOBBY, 'Successfully created Lobby', ['lobby' => $lobby->render()]);
                //todo: return lobby data
                break;
            case 'join':
                Logger::log('LOBBY', 'Player with ID >'.$resourceId.'< wants to join lobby by invite code ('.$message->data->invite.')');
                try {
                    $player = Player::find($connection);
                    $lobby = Lobby::findByInviteCode($message->data->invite);
                    $lobbyMember = $player->joinLobby($lobby);
                } catch(Exception $e){
                    Error::send($connection, $e->getCode(), $e->getMessage());
                    return;
                }

                $lobby->sendLobbyUpdate('New Player joined', $lobbyMember);

                Message::send($connection, Context::LOBBY, 'Successfully created Lobby', ['lobby' => $lobby->render()]);
                break;
            case 'leave':
                Logger::log('LOBBY', 'Player with ID >'.$resourceId.'< left lobby');
                try {
                    $player = Player::find($connection);
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
        $hoseAbe = HoseAbe::getInstance();
        while(is_null($this->uuid) || isset($hoseAbe->lobbies[$this->uuid])) {
            $this->uuid = Uuid::uuid4();
        }
    }

    public function addMember(Player $player, MemberRole $role = MemberRole::MEMBER): LobbyMember
    {
        $resourceId = $player->getResourceId();
        $hoseAbe = HoseAbe::getInstance();
        $hoseAbe->userLobbies[$resourceId] = $this->uuid;
        $lobbyMember = new LobbyMember($player, $role);
        $this->members[$resourceId] = $lobbyMember;
        $player->currentLobby = $this->uuid;

        return $lobbyMember;
    }

    public function removeMember(Player $player): void
    {
        $resourceId = $player->getResourceId();
        Logger::log('LOBBY', 'Removing Player from lobby.');
        $hoseAbe = HoseAbe::getInstance();
        $lobby = $hoseAbe->lobbies[$player->currentLobby];
        if(isset($hoseAbe->userLobbies[$resourceId])) {
            unset($hoseAbe->userLobbies[$resourceId]);
        }
        if(isset($this->members[$resourceId])) {
            unset($this->members[$resourceId]);
        }

        if (count($this->members) <= 0) {
            Logger::log('LOBBY', 'Lobby empty, removing lobby.');
            $this->removeLobby();
        } else {
            $this->members = array_values($this->members);
            $lobby->promoteNewOwner();
            $this->sendLobbyUpdate('Player disconnected');
        }
        Message::send($player->connection, Context::LOBBY, 'Left lobby', []);
    }

    private function promoteNewOwner(): void
    {
        $this->members[0]->role = MemberRole::OWNER;
    }

    public static function find($lobbyId): ?Lobby
    {
        $hoseAbe = HoseAbe::getInstance();
        return $hoseAbe->lobbies[$lobbyId];
    }

    /**
     * @throws Exception
     */
    public static function findByInviteCode(string $inviteCode): ?Lobby
    {
        $inviteCode = preg_replace('/[A-Za-z-_\s]/', '', $inviteCode);
        $hoseAbe = HoseAbe::getInstance();

        if (!isset($hoseAbe->inviteCodes[$inviteCode])) {
            throw new Exception('Could not find lobby', 404);
        }
        $lobbyId = $hoseAbe->inviteCodes[$inviteCode];

        if (!isset($hoseAbe->lobbies[$lobbyId])) {
            throw new Exception('Could not find lobby', 404);
        }
        return $hoseAbe->lobbies[$lobbyId];
    }

    public function regenerateNewInviteCode(): void
    {
        $length = array_sum(self::INVITE_CODE_LENGTH);

        $hoseAbe = HoseAbe::getInstance();
        while(is_null($this->inviteCode) || isset($hoseAbe->inviteCodes[$this->inviteCode])) {
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

    private function removeLobby(): void
    {
        $hoseAbe = HoseAbe::getInstance();
        if(isset($hoseAbe->lobbies[$this->uuid])) {
            unset($hoseAbe->lobbies[$this->uuid]);
        }
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