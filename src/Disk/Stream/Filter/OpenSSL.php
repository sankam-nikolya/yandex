<?php

/**
 * Created by Arhitector.
 * Date: 12.03.2016
 * Time: 14:04
 */

namespace Mackey\Yandex\Disk\Stream\Filter;


class OpenSSL extends \php_user_filter
{

	const MODE_DECRYPT = 0;

	const MODE_ENCRYPT = 1;

	const DEFAULT_METHOD = 'AES-256-CFB';


	protected $mode;

	protected $tail = '';


	public function onCreate()
	{
		if ( ! is_array($this->params))
		{
			$this->params = [
				'method' => self::DEFAULT_METHOD
			];
		}

		if ($this->filtername == 'openssl.encrypt')
		{
			$this->mode = self::MODE_ENCRYPT;
		}
		else if ($this->filtername == 'openssl.decrypt')
		{
			$this->mode = self::MODE_DECRYPT;
		}
		else
		{
			return false;
		}

		if (empty($this->params['method']))
		{
			$this->params['method'] = self::DEFAULT_METHOD;
		}

		if (stripos($this->params['method'], 'CFB') === false
			|| ! in_array($this->params['method'], openssl_get_cipher_methods()))
		{
			throw new OutOfBoundsException('Алгоритм не поддерживается.');
		}

		if ( ! isset($this->params['iv']))
		{
			$this->params['iv'] = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->params['method']));
		}

		if ( ! isset($this->params['password']))
		{
			$this->params['password'] = openssl_random_pseudo_bytes(32);
		}

		return true;
	}


	/**
	 * @param resource $in
	 * @param resource $out
	 * @param int      $consumed
	 * @param bool     $closing
	 *
	 * @return int|void
	 */
	public function filter($in, $out, &$consumed, $closing)
	{
		while ($bucket = stream_bucket_make_writeable($in))
		{
			if ( ! $bucket->datalen)
			{
				return PSFS_PASS_ON;
			}

			$data = $bucket->data;
			$tailLength = strlen($this->tail);

			if ($tailLength)
			{
				$data = $this->tail.$data;
			}

			if ($this->mode == self::MODE_ENCRYPT)
			{
				$crypt = openssl_encrypt($data, $this->params['method'], $this->params['password'], OPENSSL_RAW_DATA,
					$this->params['iv']);
			}
			else
			{
				$crypt = openssl_decrypt($data, $this->params['method'], $this->params['password'], OPENSSL_RAW_DATA,
					$this->params['iv']);
			}

			$result = substr($crypt, $tailLength);
			$dataLength = strlen($data);
			$mod16 = $dataLength % 16;

			if ($dataLength >= 16)
			{
				$iPos = -($mod16 + 16);

				if ($this->mode == self::MODE_DECRYPT)
				{
					$this->params['iv'] = substr($data, $iPos, 16);
				}
				else
				{
					$this->params['iv'] = substr($crypt, $iPos, 16);
				}
			}

			$this->tail = $mod16 != 0 ? substr($data, -$mod16) : '';

			$bucket->data = $result;
			$consumed += $bucket->datalen;

			stream_bucket_append($out, $bucket);
		}

		return PSFS_PASS_ON;
	}


	public function onClose()
	{
		return parent::onClose();
	}

}