<?php

/**
 * Created by Arhitector.
 * Date: 12.03.2016
 * Time: 14:06
 */

namespace Mackey\Yandex\Disk\Stream;


use Zend\Diactoros\Stream;

class Decryption extends Stream
{

	public function __construct($stream, $mode = 'r')
	{
		$key = '7f25ad7df8a9843a5b47b2a7f3d58764string'; // md5(openssl_random_pseudo_bytes(32));
		$iv  = md5('1', true); // openssl_random_pseudo_bytes(16); // 128bit AES Block Length

		if ( ! extension_loaded('openssl'))
		{
			throw new \RuntimeException('The openssl extension must be enabled to use this stream');
		}

		parent::__construct($stream, $mode);

		if ( ! in_array('openssl.*', stream_get_filters()))
		{
			stream_filter_register('openssl.*', 'Mackey\Yandex\Disk\Stream\Filter\OpenSSL');
		}

		stream_filter_append($this->resource, 'openssl.encrypt', STREAM_FILTER_READ, [
			'method' => 'AES-256-CFB',
			'password' => $key,
			'iv' => $iv
		]);

		stream_filter_append($this->resource, 'openssl.decrypt', STREAM_FILTER_WRITE, [
			'method' => 'AES-256-CFB',
			'password' => $key,
			'iv' => $iv
		]);

	}

}