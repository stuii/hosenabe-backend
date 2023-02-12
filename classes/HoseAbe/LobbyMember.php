<?php

namespace HoseAbe;

use DateTime;
use HoseAbe\Enums\MemberRole;
use JetBrains\PhpStorm\ArrayShape;

class LobbyMember
{
    private DateTime $joinTime;

    public function __construct(
        public Player $player,
        public MemberRole $role = MemberRole::MEMBER
    ) {
        $this->joinTime = new DateTime();
    }

    #[ArrayShape(['username' => "null|string", 'role' => "\HoseAbe\Enums\MemberRole"])]
    public function render(): array
    {
        return [
            'username' => $this->player->username,
            'role' => $this->role
        ];
    }
}