<?php

namespace HoseAbe;

use DateTime;
use HoseAbe\Enums\MemberRole;

class LobbyMember
{
    private DateTime $joinTime;

    public function __construct(
        private Player $player,
        private MemberRole $role = MemberRole::MEMBER
    ) {
        $this->joinTime = new DateTime();
    }
}