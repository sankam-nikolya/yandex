<?php

/**
 * Created by Arhitector.
 * Date: 02.03.2016
 * Time: 7:17
 */

namespace Mackey\Yandex\Disk\Resource;


use Mackey\Yandex\Client\Container;
use Mackey\Yandex\Disk\AbstractResource;
use Mackey\Yandex\Client\Exception\NotFoundException;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

class Opened extends AbstractResource
{

	/**
	 * @var    string    ресурс
	 */
	protected $publicKey;


	/**
	 * Конструктор.
	 *
	 * @param string|array                   $path путь к публичному ресурсу
	 * @param \Mackey\Yandex\Disk            $parent
	 * @param \Psr\Http\Message\UriInterface $uri
	 */
	public function __construct($public_key, \Mackey\Yandex\Disk $parent, \Psr\Http\Message\UriInterface $uri)
	{
		if (is_array($public_key))
		{
			if (empty($public_key['public_key']))
			{
				throw new \InvalidArgumentException('Параметр "public_key" должен быть строкового типа.');
			}

			$this->setContents($public_key);
			$public_key = $public_key['public_key'];
		}

		if ( ! is_scalar($public_key))
		{
			throw new \InvalidArgumentException('Параметр "public_key" должен быть строкового типа.');
		}

		$this->publicKey = (string) $public_key;
		$this->parent = $parent;
		$this->uri = $uri;
	}

	/**
	 * Получить публичный ключ
	 *
	 * @return    string|null
	 */
	public function getPublicKey()
	{
		return $this->publicKey;
	}

	/**
	 * Получает информацию о ресурсе
	 *
	 * @return    mixed
	 */
	public function toArray(array $allowed = null)
	{
		if ( ! $this->_toArray() || $this->isModified())
		{
			$response = $this->parent->send((new Request($this->uri->withPath($this->uri->getPath().'public/resources')
				->withQuery(http_build_query(array_merge($this->getParameters($this->parametersAllowed), [
					'public_key' => $this->getPublicKey()
				]), null, '&')), 'GET')));

			if ($response->getStatusCode() == 200)
			{
				$response = json_decode($response->getBody(), true);

				if ( ! empty($response))
				{
					$this->isModified = false;

					if (isset($response['_embedded'], $response['_embedded']['items']))
					{
						$response['items'] = new Container(array_map(function ($item) {
							return new self($item, $this->parent, $this->uri);
						}, $response['_embedded']['items']));
					}

					unset($response['_links'], $response['_embedded']);

					$this->setContents($response);
				}
			}
		}

		return $this->_toArray($allowed);
	}

	/**
	 * Получает прямую ссылку
	 *
	 * @return    string
	 * @throws    mixed
	 */
	public function getLink()
	{
		if ( ! $this->has())
		{
			throw new NotFoundException('Не удалось найти запрошенный ресурс.');
		}

		$response = $this->parent->send(new Request($this->uri->withPath($this->uri->getPath().'public/resources/download')
			->withQuery(http_build_query([
				'public_key' => $this->getPublicKey(),
				'path'       => (string) $this->getPath()
			], null, '&')), 'GET'));

		if ($response->getStatusCode() == 200)
		{
			$response = json_decode($response->getBody(), true);

			if (isset($response['href']))
			{
				return $response['href'];
			}
		}

		throw new \UnexpectedValueException('Не удалось запросить разрешение на скачивание, повторите заново');
	}

	/**
	 * Скачивание публичного файла или папки
	 *
	 * @param string  $path Путь, по которому будет сохранён файл
	 * @param boolean $overwrite
	 *
	 * @return    boolean
	 *
	 * @throws    \OutOfBoundsException
	 * @throws    \RangeException
	 */
	public function download($path, $overwrite = false)
	{
		if (is_file($path) && ! $overwrite)
		{
			throw new \OutOfBoundsException('Такой файл существует, преедайте true Чтобы перезаписать его');
		}

		if ( ! is_writable(dirname($path)))
		{
			throw new \OutOfBoundsException('Запись в директорию где должен быть располоен файл не возможна.');
		}

		$resource = fopen($path, 'wb+');
		$response = $this->parent->send(new Request($this->getLink(), 'GET'), new Stream($resource, 'w'));

		fclose($resource);

		if ($this->isFile() && md5_file($path) !== $this->get('md5', null))
		{
			throw new \RangeException('Файл скачан, но контрольные суммы различаются.');
		}

		return $response->getStatusCode() == 200;
	}

	/**
	 * Этот файл или такой же находится на моём диске
	 * Метод требует Access Token
	 *
	 * @return    boolean
	 */
	public function hasEqual()
	{
		if ($this->has() && ($path = $this->get('name')))
		{
			try
			{
				return $this->parent->resource(((string) $this->get('path')).'/'.$path)
					->get('md5', false) === $this->get('md5');
			}
			catch (\Exception $exc)
			{

			}
		}

		return false;
	}

	/**
	 * Сохранение публичного файла в «Загрузки» или отдельный файл из публичной папки
	 *
	 * @param    string $name Имя, под которым файл следует сохранить в папку «Загрузки»
	 * @param    string $path Путь внутри публичной папки.
	 *
	 * @return    mixed
	 */
	public function save($name = null, $path = null)
	{
		$parameters = [];

		/**
		 * @var mixed   $name Имя, под которым файл следует сохранить в папку «Загрузки»
		 */
		if (is_string($name))
		{
			$parameters['name'] = $name;
		}
		else if ($name instanceof Closed)
		{
			$parameters['name'] = substr(strrchr($name->getPath(), '/'), 1);
		}

		/**
		 * @var string  $path (необязательный)
		 * Путь внутри публичной папки. Следует указать, если в значении параметра public_key передан
		 * ключ публичной папки, в которой находится нужный файл.
		 * Путь в значении параметра следует кодировать в URL-формате.
		 */
		if (is_string($path))
		{
			$parameters['path'] = $path;
		}
		else if ($this->getPath() !== null)
		{
			$parameters['path'] = $this->getPath();
		}

		/**
		 * Если к моменту ответа запрос удалось обработать без ошибок, API отвечает кодом 201 Created и возвращает
		 * ссылку на сохраненный файл в теле ответа (в объекте Link).
		 * Если операция сохранения была запущена, но еще не завершилась, Яндекс.Диск отвечает кодом 202 Accepted.
		 */
		$response = $this->parent->send((new Request($this->uri->withPath($this->uri->getPath()
			.'public/resources/save-to-disk')
			->withQuery(http_build_query([
					'public_key' => $this->getPublicKey()
				] + $parameters, null, '&')), 'POST')));

		if ($response->getStatusCode() == 202 || $response->getStatusCode() == 201)
		{
			$response = json_decode($response->getBody(), true);

			if (isset($response['operation']))
			{
				return $response['operation'];
			}

			if (isset($response['href']))
			{
				parse_str((new Uri($response['href']))->getQuery(), $path);

				if (isset($path['path']))
				{
					return $this->parent->resource($path['path']);
				}
			}
		}

		return false;
	}

	/**
	 * Устанавливает путь внутри публичной папки
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath($path)
	{
		if ( ! is_scalar($path))
		{
			throw new \InvalidArgumentException('Параметр "path" должен быть строкового типа.');
		}

		$this->path = (string) $path;

		return $this;
	}

}