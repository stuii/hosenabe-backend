<?php

namespace HoseAbe\Enums;

enum LobbyStatus: string
{
    case OPEN = 'OPEN';
    case INGAME = 'INGAME';
    case HALTED = 'HALTED';
}