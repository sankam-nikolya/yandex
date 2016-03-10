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
namespace Mackey\Yandex\Disk\Exception;

use Http\Client\Exception;

/**
 * Исключение недостаточно свободного места
 *
 * @package    Mackey\Yandex\Disk\Exception
 */
class OutOfSpaceException extends \RuntimeException implements Exception
{
	
}