<?php

namespace HoseAbe\Messages;

use Ratchet\ConnectionInterface;

interface MessageInterface
{
    public static function send(ConnectionInterface $connection, array $data): void;
}