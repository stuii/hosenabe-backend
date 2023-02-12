<?php

namespace HoseAbe\Debug;

use DateTime;

class Logger
{
    public static function log(string $type, string $message): void
    {
        $now = new DateTime();
        echo '[' . $now->format('Y-m-d H:i:s.u') . '] ';
        echo '[' . str_pad($type, 11, ' ', STR_PAD_BOTH) . '] ';
        echo $message;
        echo "\n";
    }
}