<?php

namespace Addons\Server\Protocols\TagV2;

use Google\Protobuf\Internal\Message;
use Addons\Server\Responses\RawResponse;
use Addons\Server\Senders\WebSocketSender;
use Addons\Server\Contracts\AbstractRequest;

class Response extends RawResponse {

	protected $ack = null;
	protected $version = null;
	protected $protocol = null;

	public function __construct($ack, $version, $protocol, $content)
	{
		$this->ack = $ack;
		$this->version = $version;

		if (is_numeric($protocol))
			$this->protocol = pack('n', $protocol);
		else if (empty($protocol))
			$this->protocol = "\x0\x0";
		else
			$this->protocol = substr($protocol, 0, 2);

		$this->content = $content;
	}

	public function prepare(AbstractRequest $request)
	{
		$content = $this->getContent() instanceof Message ? $this->getContent()->serializeToString() : $this->getContent();

		$this->setContent(
		 	pack('n', $this->ack) .
		 	pack('n', $this->version) .
			$this->protocol .
		 	pack('N', strlen($content)) .
		 	$content
		 );

		return $this;
	}

}
