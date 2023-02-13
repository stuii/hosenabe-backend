<?php

namespace HoseAbe\Connection;

use DateTime;
use DateTimeInterface;
use HoseAbe\Enums\MemberRole;
use JetBrains\PhpStorm\ArrayShape;

class LobbyMember
{
    private DateTime $joinTime;

    /** @noinspection PhpUnused */
    public function __construct(
        public Player $player,
        public MemberRole $role = MemberRole::MEMBER
    ) {
        $this->joinTime = new DateTime();
    }

    #[ArrayShape(['username' => "null|string", 'role' => "\HoseAbe\Enums\MemberRole", 'joinTime' => "string"])]
    public function render(): array
    {
        return [
            'username' => $this->player->username,
            'role' => $this->role,
            'joinTime' => $this->joinTime->format(DateTimeInterface::ATOM)
        ];
    }
}