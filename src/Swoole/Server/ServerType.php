<?php


namespace Swift\Framework\Swoole\Server;


class ServerType
{

    /**
     * 协程HTTP Server
     */
    const COROUTINE_HTTP_SERVER = CoroutineHttpServer::class;

    /**
     * 协程Websocket Server
     */
    const COROUTINE_WEBSOCKET_SERVER = CoroutineWebsocketServer::class;
}
