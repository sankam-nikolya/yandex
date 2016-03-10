<?php

/**
 * Created by Arhitector.
 * Date: 07.03.2016
 * Time: 22:10
 */

namespace Mackey\Yandex\Client\Http;


use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Stream;

class StreamFactory implements \Http\Message\StreamFactory
{

	/**
	 * @var StreamInterface
	 */
	protected static $stream;


	/**
	 * Устанавливает Stream для обработки ответа
	 *
	 * @param StreamInterface $stream
	 *
	 * @return $this
	 */
	public static function useStream(StreamInterface $stream = null)
	{
		self::$stream = $stream;
	}

	/**
	 * {@inheritdoc}
	 */
	public function createStream($body = null)
	{
		if ( ! $body instanceof StreamInterface)
		{
			if ( ! self::$stream)
			{
				self::$stream = new Stream('php://temp', 'rw');
			}

			if (is_resource($body))
			{
				self::$stream->__construct($body);
			}
			else if ($body !== null)
			{
				self::$stream->write((string) $body);
			}

			$body = self::$stream;
		}

		$body->rewind();

		return $body;
	}

}