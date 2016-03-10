<?php

/**
 * Часть библиотеки по работе с Yandex REST API
 *
 * @package    Mackey\Yandex\Exception
 * @version    1.0
 * @author     Arhitector
 * @license    MIT License
 * @copyright  2015 Arhitector
 * @link       http://pruffick.ru
 */
namespace Mackey\Yandex\Client\Exception;

use Http\Client\Exception;

/**
 * Исключение сервис недоступен.
 *
 * @package    Mackey\Yandex\Exception
 */
class ServiceException extends \RuntimeException implements Exception
{
	
}