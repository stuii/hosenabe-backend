<?php

namespace HoseAbe\Enums;

enum Action: string
{
    case CONNECT = 'connect';
    case LOGIN = 'login';
    case RECONNECT = 'reconnect';
    case CREATE_LOBBY = 'create';
    case JOIN_LOBBY = 'join';
    case LEAVE_LOBBY = 'leave';
    case LOBBY_UPDATE = 'update';
}