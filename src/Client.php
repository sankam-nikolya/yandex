<?php

/**
 * Created by Arhitector.
 * Date: 25.02.2016
 * Time: 14:34
 */

namespace Mackey\Yandex;

use Http\Client\Plugin\HeaderDefaultsPlugin;
use Http\Client\Plugin\HeaderSetPlugin;
use Http\Client\Plugin\PluginClient;
use Http\Client\Plugin\RedirectPlugin;
use Http\Promise\Promise;
use Mackey\Yandex\Client\Exception\ServiceException;
use Http\Client\Curl\Client as HttpClient;
//use Mackey\Yandex\Client\Http\Client as HttpClient;
use Mackey\Yandex\Client\Http\MessageFactory;
use Mackey\Yandex\Client\Http\StreamFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Uri;

/**
 * Базовый клиент, реализует способы аунтифиации
 * @package Mackey\Yandex
 */
abstract class Client
{

	/**
	 * @const   адрес API
	 */
	const API_BASEPATH = 'https://oauth.yandex.ru/';

	/**
	 * @var \Psr\Http\Message\UriInterface
	 */
	protected $uri;

	/**
	 * @var \HTTP\Client\HttpClient клиент
	 */
	protected $client;

	/**
	 * @var string  формат обмена данными
	 */
	protected $contentType = 'application/json';

	/**
	 * @var	array   соответствие кодов ответа к типу исключения
	 */
	protected $exceptions = [

		/**
		 * Не авторизован.
		 */
		401 => 'Mackey\Yandex\Client\Exception\UnauthorizedException',

		/**
		 * Доступ запрещён. Возможно, у приложения недостаточно прав для данного действия.
		 */
		403 => 'Mackey\Yandex\Client\Exception\ForbiddenException',

		/**
		 * Не удалось найти запрошенный ресурс.
		 */
		404 => 'Mackey\Yandex\Client\Exception\NotFoundException'
	];

	/**
	 * @var    string    для обращения к API требуется маркер доступа
	 */
	protected $tokenRequired = true;



	/**
	 * @var string  поток
	 */
	protected $stream;



	/**
	 * @var int количество попыток если запрос не удачен
	 */
	protected $retries = 0;


	/**
	 * Конструктор
	 */
	public function __construct()
	{
		$this->uri = new Uri(static::API_BASEPATH);
		$this->client = new PluginClient(new HttpClient(new MessageFactory, new StreamFactory, [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false
		]), [
			new HeaderDefaultsPlugin([
				'Accept'       => $this->getContentType(),
				'Content-Type' => $this->getContentType()
			]),
			new HeaderSetPlugin([
				'Expect' => ''
			]),
			new RedirectPlugin()
		]);
	}

	/**
	 * Провести аунтификацию в соостветствии с типом сервиса
	 *
	 * @param   RequestInterface $request
	 *
	 * @return RequestInterface
	 */
	abstract protected function authentication(RequestInterface $request);

	/**
	 * Текущий Uri
	 *
	 * @return \Psr\Http\Message\UriInterface|Uri
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Формат обмена данными
	 *
	 * @return    string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Отправляет запрос
	 *
	 * @param RequestInterface         $request
	 *
	 * @param StreamInterface $filterStream
	 *
	 * @return Promise
	 */
	public function send(RequestInterface $request)
	{
		$request = $this->authentication($request);
		$response = $this->client->sendAsyncRequest($request)
			->then(function (ResponseInterface $response) use ($request) {
				return $this->transformResponseToException($request, $response);
			});

		return $response;
	}

	/**
	 * Устаналивает необходимость OAuth-токена при запросе
	 *
	 * @param $tokenRequired
	 *
	 * @return boolean  возвращает предыдущее состояние
	 */
	protected function setAccessTokenRequired($tokenRequired)
	{
		$previous = $this->tokenRequired;
		$this->tokenRequired = (bool) $tokenRequired;

		return $previous;
	}

	/**
	 * Трансформирует ответ в исключения
	 *
	 * @param RequestInterface  $request
	 * @param ResponseInterface $response
	 *
	 * @return ResponseInterface если статус код не является ошибочным, то вернуть объект ответа
	 */
	protected function transformResponseToException(RequestInterface $request, ResponseInterface $response)
	{
		if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500)
		{
			throw new \RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
		}

		if ($response->getStatusCode() >= 500 && $response->getStatusCode() < 600)
		{
			throw new ServiceException($response->getReasonPhrase(), $response->getStatusCode());
		}

		return $response;
	}

}