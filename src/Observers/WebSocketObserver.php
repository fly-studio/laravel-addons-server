<?php

namespace Addons\Server\Observers;

use Addons\Server\Observers\HttpObserver;
use Addons\Server\Contracts\Listeners\AbstractWebsocketListener;

class WebSocketObserver extends HttpObserver {

	public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
	{
		$this->trigger('onOpen', $request);
	}

	public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
	{
		$this->trigger('onMessage', $frame);
	}

	public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
	{
		$result = $this->handShake($request, $response);

		$this->trigger('onHandShake', $request, $response, $result);
		if ($result)
			$this->onOpen($this->server->nativeServer(), $request);

		return $result;
	}

	protected function handShake(\swoole_http_request $request, \swoole_http_response $response)
	{
		// websocket握手连接算法验证
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			$response->end();
			return false;
		}
		//echo $request->header['sec-websocket-key'];
		$key = base64_encode(sha1(
			$request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
			true
		));

		$headers = [
			'Upgrade' => 'websocket',
			'Connection' => 'Upgrade',
			'Sec-WebSocket-Accept' => $key,
			'Sec-WebSocket-Version' => '13',
		];

		// WebSocket connection to 'ws://127.0.0.1:9502/'
		// failed: Error during WebSocket handshake:
		// Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
		if (isset($request->header['sec-websocket-protocol']))
			$headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];

		foreach ($headers as $key => $val) {
			$response->header($key, $val);
		}

		$response->status(101);
		$response->end();
		//echo "connected!" . PHP_EOL;
		return true;
	}
}
