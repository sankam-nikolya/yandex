<?php

/**
 * Created by Arhitector.
 * Date: 02.03.2016
 * Time: 6:28
 */

namespace Mackey\Yandex\Client;



class Container implements \ArrayAccess, \IteratorAggregate, \Countable
{
	use ContainerTrait;

	/**
	 *	Конструктор
	 *
	 *	@param  array    $data      данные
	 *	@param  boolean  $readOnly  только для чтения
	 */
	public function __construct(array $data = array(), $readOnly = false)
	{
		$this->setContents($data);
	}

	/**
	 * Получает первый элемент в списке
	 *
	 * @return mixed
	 */
	public function getFirst()
	{
		return reset($this->toArray());
	}

	/**
	 * Получает последний элемент в списке
	 *
	 * @return mixed
	 */
	public function getLast()
	{
		return end($this->toArray());
	}
}