<?php

namespace HoseAbe;

use DateTime;
use HoseAbe\Enums\MemberRole;

class LobbyMember
{
    private DateTime $joinTime;

    public function __construct(
        public Player $player,
        public MemberRole $role = MemberRole::MEMBER
    ) {
        $this->joinTime = new DateTime();
    }
}