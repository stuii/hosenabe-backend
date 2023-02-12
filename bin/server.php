<?php

use HoseAbe\HoseAbe;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require '../vendor/autoload.php';

$wsServer = new WsServer(HoseAbe::getInstance());

$server = IoServer::factory(
    new HttpServer(
        $wsServer
    ),
    33363
);

$wsServer->enableKeepAlive($server->loop, 30);

$server->run();