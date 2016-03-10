<?php

/**
 * Created by Arhitector.
 * Date: 07.03.2016
 * Time: 21:51
 */

namespace Mackey\Yandex;


use League\Event\Emitter;
use League\Event\EmitterTrait;
use Mackey\Yandex\Client\Exception\UnsupportedException;
use Mackey\Yandex\Client\Http\StreamFactory;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Request;
use Mackey\Yandex\Client\ContainerTrait;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

class Disk extends Client\AccessToken implements \ArrayAccess, \IteratorAggregate, \Countable
{
	use ContainerTrait, EmitterTrait {
		toArray as protected _toArray;
	}

	/**
	 * @const   адрес API
	 */
	const API_BASEPATH = 'https://cloud-api.yandex.net/v1/disk/';

	/**
	 * @var string  формат обмена данными
	 */
	protected $contentType = 'application/json; charset=utf-8';

	/**
	 * @var    array   соответствие кодов ответа к типу исключения
	 */
	protected $exceptions = [

		/**
		 * Некорректные данные (Bad Request).
		 */
		400 => 'Mackey\Yandex\Client\Exception\UnsupportedException',

		/**
		 * Не авторизован (Unauthorized).
		 */
		401 => 'Mackey\Yandex\Client\Exception\UnauthorizedException',

		/**
		 * Доступ запрещён (Forbidden).
		 * Возможно, у приложения недостаточно прав для данного действия.
		 */
		403 => 'Mackey\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Не удалось найти запрошенный ресурс (Not Found).
		 */
		404 => 'Mackey\Yandex\Client\Exception\NotFoundException',

		/**
		 * Ресурс не может быть представлен в запрошенном формате (Not Acceptable).
		 */
		406 => 'Mackey\Yandex\Disk\Exception\UnsupportedException',

		/**
		 * Ресурс уже существует (Conflict).
		 */
		409 => 'Mackey\Yandex\Disk\Exception\AlreadyExistsException',

		/**
		 * Ресурс не может быть представлен в запрошенном формате (Unsupported Media Type).
		 */
		415 => 'Mackey\Yandex\Client\Exception\UnsupportedException',

		/**
		 * Ресурс заблокирован (Locked).
		 * Возможно, над ним выполняется другая операция.
		 */
		423 => 'Mackey\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Слишком много запросов(Too Many Requests).
		 */
		429 => 'Mackey\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Сервис временно недоступен(Service Unavailable).
		 */
		503 => 'Mackey\Yandex\Client\Exception\ServiceException',

		/**
		 * Недостаточно свободного места (Insufficient Storage).
		 */
		507 => 'Mackey\Yandex\Disk\Exception\OutOfSpaceException'
	];

	/**
	 * @var    array    идентификаторы операций за сессию
	 */
	private $operations = [];


	/**
	 * Конструктор
	 *
	 * @param    mixed $token маркер доступа
	 *
	 * @throws    \InvalidArgumentException
	 *
	 * @example
	 *
	 * new Disk('token')
	 * new Disk() -> setAccessTokn('token')
	 * new Disk( new Client('token') )
	 */
	public function __construct($token = null)
	{
		$this->setEmitter(new Emitter);

		if ($token instanceof Client)
		{
			$token = $token->getAccessToken();
		}

		parent::__construct($token);
	}

	/**
	 * Получает информацию о диске
	 *
	 * @return    array
	 *
	 * @throws UnsupportedException
	 *
	 * @example
	 *
	 * array (size=5)
	 * 'trash_size' => int 9449304
	 * 'total_space' => float 33822867456
	 * 'used_space' => float 25863284099
	 * 'free_space' => float 7959583357
	 * 'system_folders' => array (size=2)
	 *      'applications' => string 'disk:/Приложения' (length=26)
	 *      'downloads' => string 'disk:/Загрузки/' (length=23)
	 */
	public function toArray(array $allowed = null)
	{
		if ( ! $this->_toArray())
		{
			$response = $this->send(new Request($this->uri, 'GET'));

			if ($response->getStatusCode() == 200)
			{
				$response = json_decode($response->getBody(), true);

				if ( ! is_array($response))
				{
					throw new UnsupportedException('Получен не поддерживаемый формат ответа от API Диска.');
				}

				$this->setContents($response += [
					'free_space' => $response['total_space'] - $response['used_space']
				]);
			}
		}

		return $this->_toArray($allowed);
	}

	/**
	 * Возвращает свободное место на диске
	 *
	 * @return float
	 */
	public function count()
	{
		return (float) $this->get('free_space', 0);
	}

	/**
	 * Работа с ресурсами на диске
	 *
	 * @param    string  $path Путь к новому либо уже существующему ресурсу, NULL Список всех файлов
	 * @param    integer $limit
	 * @param    integer $offset
	 *
	 * @return   Disk\Resource\Collection|Disk\Resource\Closed
	 *
	 * @example
	 *
	 * $disk->resource(null, 100, 0) // Mackey\Yandex\Disk\Resource\Collection
	 *      ->toArray();
	 *
	 * array (size=2)
	 * 0 => object(Mackey\Yandex\Disk\Resource\Closed)[30]
	 * .....
	 *
	 * @example
	 *
	 * $disk->resource('any_file.ext') -> upload( __DIR__.'/file_to_upload');
	 * $disk->resource('any_file.ext') // Mackey\Yandex\Disk\Resource\Closed
	 *      ->toArray(); // если ресурса еще нет, то исключение NotFoundException
	 *
	 * array (size=11)
	 * 'public_key' => string 'wICbu9SPnY3uT4tFA6P99YXJwuAr2TU7oGYu1fTq68Y=' (length=44)
	 * 'name' => string 'Gameface - Gangsigns_trapsound.ru.mp3' (length=37)
	 * 'created' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'public_url' => string 'https://yadi.sk/d/g0N4hNtXcrq22' (length=31)
	 * 'modified' => string '2014-10-08T22:13:49+00:00' (length=25)
	 * 'media_type' => string 'audio' (length=5)
	 * 'path' => string 'disk:/applications_swagga/1/Gameface - Gangsigns_trapsound.ru.mp3' (length=65)
	 * 'md5' => string '8c2559f3ce1ece12e749f9e5dfbda59f' (length=32)
	 * 'type' => string 'file' (length=4)
	 * 'mime_type' => string 'audio/mpeg' (length=10)
	 * 'size' => int 8099883
	 */
	public function resource($path = null, $limit = 20, $offset = 0)
	{
		/**
		 * Список всех файлов
		 */
		if ($path === null)
		{
			return (new Disk\Resource\Collection(function($parameters) {
				$response = $this->send((new Request($this->uri->withPath($this->uri->getPath().'resources/files')
				                                               ->withQuery(http_build_query($parameters, null, '&')), 'GET')));

				if ($response->getStatusCode() == 200)
				{
					$response = json_decode($response->getBody(), true);

					if (isset($response['items']))
					{
						return array_map(function($item) {
							return new Disk\Resource\Closed($item, $this, $this->uri);
						}, $response['items']);
					}
				}

				return [];
			}))
				->setLimit($limit, $offset);
		}

		if ( ! is_string($path))
		{
			throw new \InvalidArgumentException('Ресурс, должен быть строкового типа - путь к файлу/папке, либо NULL');
		}

		if (stripos($path, 'app:/') !== 0 && stripos($path, 'disk:/') !== 0)
		{
			$path = 'disk:/'.ltrim($path, ' /');
		}

		return (new Disk\Resource\Closed($path, $this, $this->uri))
			->setLimit($limit, $offset);
	}

	/**
	 * Получить статус операции либо
	 *
	 * @param    string $identifier идентификатор операции или NULL
	 *
	 * @return    string|false    текстовое описание статуса, FALSE либо массив идентификаторов операции
	 *
	 * @example
	 *
	 * $disk->getOperation('identifier operation')
	 * string 'success' (length=7)
	 */
	public function getOperation($identifier)
	{
		$response = $this->send(new Request($this->uri->withPath($this->uri->getPath().'operations/'.$identifier),
			'GET'));

		if ($response->getStatusCode() == 200)
		{
			$response = json_decode($response->getBody(), true);

			if (isset($response['status']))
			{
				return $response['status'];
			}
		}

		return false;
	}

	/**
	 * Получить все операции, полученные во время выполнения сценария
	 *
	 * @return array
	 *
	 * @example
	 *
	 * $disk->getOperations()
	 *
	 * array (size=124)
	 *  0 => 'identifier_1',
	 *  1 => 'identifier_2',
	 *  2 => 'identifier_3',
	 */
	public function getOperations()
	{
		return $this->operations;
	}

	/**
	 * Отправляет запрос
	 *
	 * @param Request $request
	 * @param mixed   $processor если нужно входящий поток обработать поток
	 *
	 * @return mixed|\Psr\Http\Message\ResponseInterface
	 */
	public function send(Request $request, StreamInterface $stream = null)
	{
		if ($stream !== null)
		{
			StreamFactory::useStream($stream);
		}

		$response = parent::send($request)->wait();

		// из-за плохо написанного плагина, делегируем потоки сами
		StreamFactory::useStream();

		if ($response->getStatusCode() == 202)
		{
			if (($responseBody = json_decode($response->getBody(), true)) && isset($responseBody['href']))
			{
				$operation = new Uri($responseBody['href']);

				if ( ! $operation->getQuery())
				{

					$this->emit('disk.operation', $this);

					$this->operations[] = $responseBody['operation'] = substr(strrchr($operation->getPath(), '/'), 1);
					$stream = new Stream('php://temp', 'w');
					$stream->write(json_encode($responseBody));

					return $response->withBody($stream);
				}
			}
		}

		return $response;
	}

}