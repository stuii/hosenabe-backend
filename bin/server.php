<?php

use HoseAbe\HoseAbe;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require '../vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new HoseAbe()
        )
    ),
    8080
);

$server->run();