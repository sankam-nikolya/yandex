<?php

/**
 * Created by Arhitector.
 * Date: 26.02.2016
 * Time: 8:25
 */

namespace Mackey\Yandex\Disk\Resource;


use Mackey\Yandex\Client\Container;
use Mackey\Yandex\Disk\FilterTrait;

/**
 * Класс Collection
 *
 * @package Mackey\Yandex\Disk\Resource
 */
class Collection extends Container
{
	use FilterTrait;

	/**
	 * @var    Callable
	 */
	protected $closure;

	/**
	 * @var array   какие параметры доступны для фильтра
	 */
	protected $parametersAllowed = ['limit', 'media_type', 'offset', 'preview_crop', 'preview_size', 'sort'];


	/**
	 *	Конструктор
	 */
	public function __construct(\Closure $data_closure = null)
	{
		$this->closure = $data_closure;
	}

	/**
	 *	Получает информацию
	 *
	 *	@return	array
	 */
	public function toArray()
	{
		if ( ! parent::toArray() || $this->isModified())
		{
			$this->setContents(call_user_func($this->closure, $this->getParameters($this->parametersAllowed)));
		}

		return parent::toArray();
	}

}