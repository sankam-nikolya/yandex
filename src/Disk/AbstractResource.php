<?php

/**
 * Created by Arhitector.
 * Date: 08.03.2016
 * Time: 14:26
 */

namespace Mackey\Yandex\Disk;


use League\Event\EmitterTrait;
use Mackey\Yandex\Client\ContainerTrait;

abstract class AbstractResource implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use ContainerTrait, FilterTrait, EmitterTrait {
		toArray as protected _toArray;
		has as hasProperty;
	}

	/**
	 * @var string  путь к ресурсу
	 */
	protected $path;

	/**
	 * @var \Psr\Http\Message\UriInterface
	 */
	protected $uri;

	/**
	 * @var \Mackey\Yandex\Disk объект, диска породивший ресурс
	 */
	protected $parent;

	/**
	 * @var array   допустимые фильтры
	 */
	protected $parametersAllowed = ['limit', 'offset', 'preview_crop', 'preview_size', 'sort'];


	/**
	 * Есть такой файл/папка на диске или свойство
	 *
	 * @return    boolean
	 */
	public function has($index = null)
	{
		try
		{
			if ($this->toArray())
			{
				if ($index === null)
				{
					return true;
				}

				return $this->hasProperty($index);
			}
		}
		catch (\Exception $exc)
		{
		}

		return false;
	}

	/**
	 * Проверяет, является ли ресурс файлом
	 *
	 * @return bool
	 */
	public function isFile()
	{
		return $this->get('type', false) === 'file';
	}

	/**
	 * Проверяет, является ли ресурс папкой
	 *
	 * @return bool
	 */
	public function isDir()
	{
		return $this->get('type', false) === 'dir';
	}

	/**
	 * Проверяет, этот ресурс с открытым доступом или нет
	 *
	 * @return boolean
	 */
	public function isPublish()
	{
		return $this->has('public_key');
	}

	/**
	 * Получить путь к ресурсу
	 *
	 * @return    string
	 */
	public function getPath()
	{
		return $this->path;
	}
}