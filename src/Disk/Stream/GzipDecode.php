<?php

/**
 * Created by Arhitector.
 * Date: 08.03.2016
 * Time: 22:07
 */

namespace Mackey\Yandex\Disk\Stream;


use Zend\Diactoros\Stream;

class GzipDecode extends Stream
{

	public function __construct($stream, $mode = 'r')
	{
		if ( ! extension_loaded('zlib'))
		{
			throw new \RuntimeException('The zlib extension must be enabled to use this stream');
		}

		parent::__construct($stream, $mode);

		//  zlib.inflate (декомпрессия)
		//stream_filter_append($this->resource, 'zlib.inflate', STREAM_FILTER_READ);

		// zlib.deflate (компрессия)
		stream_filter_append($this->resource, 'zlib.deflate', STREAM_FILTER_WRITE);

	}

}