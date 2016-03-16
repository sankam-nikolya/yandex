<?php

/**
 * Created by Arhitector.
 * Date: 26.02.2016
 * Time: 8:20
 */

namespace Mackey\Yandex\Client;


trait ContainerTrait
{

	/**
	 * @var    array  контейнер данных
	 */
	protected $store = [];


	/**
	 * Проверить есть такой ключ в контейнере
	 *
	 * @param   string $key
	 *
	 * @return  bool
	 */
	public function has($key)
	{
		return $this->hasByIndex($key);
	}

	/**
	 * Получить значение из контейнера по ключу
	 *
	 * @param   string $index
	 * @param   mixed  $default может быть функцией
	 *
	 * @return  mixed
	 */
	public function get($index, $default = null)
	{
		if ($this->hasByIndex($index))
		{
			return $this->store[$index];
		}

		if ($default instanceof \Closure)
		{
			return $default($this);
		}

		return $default;
	}

	/**
	 * Countable
	 *
	 * @return    integer    размер контейнера
	 */
	public function count()
	{
		return count($this->toArray());
	}

	/**
	 * Получить данные контейнера в виде массива
	 *
	 * @param   array $allowed получить только эти ключи
	 *
	 * @return  array  контейнер
	 */
	public function toArray(array $allowed = null)
	{
		$contents = $this->store;

		if ($allowed !== null)
		{
			$contents = array_intersect_key($this->store, array_flip($allowed));
		}

		/*foreach ($contents as $index => $value)
		{
			if ($value instanceof Container || $value instanceof AbstractResource)
			{
				$contents[$index] = $value->toArray();
			}
		}*/

		return $contents;
	}

	/**
	 * Получить данные контейнера в виде объекта
	 *
	 * @param   array  $allowed получить только эти ключи
	 *
	 * @return  \stdClass  контейнер
	 */
	public function toObject(array $allowed = null)
	{
		return (object) $this->toArray($allowed);
	}

	/**
	 * IteratorAggregate
	 *
	 * @return \IteratorAggregate    итератор
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->toArray());
	}

	/**
	 * Магический метод isset
	 *
	 * @return boolean
	 */
	public function __isset($key)
	{
		return $this->has($key);
	}

	/**
	 * Магический метод get
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Разрешает использование isset()
	 *
	 * @param    string $key
	 *
	 * @return    bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Разрешает доступ к ключам как к массиву
	 *
	 * @param    string $key
	 *
	 * @return    mixed
	 * @throws    \OutOfBoundsException
	 */
	public function offsetGet($key)
	{
		return $this->get($key, function() use ($key) {
			throw new \OutOfBoundsException('Индекс не существует '.$key);
		});
	}

	/**
	 * Разрешает использование unset()
	 *
	 * @param    string $key
	 *
	 * @throws    \RuntimeException
	 */
	public function offsetUnset($key)
	{
		return null;
	}

	/**
	 * Разрешает обновление свойств объекта как массива
	 *
	 * @param    string $key
	 * @param    mixed  $value
	 */
	public function offsetSet($key, $value)
	{
		return null;
	}

	/**
	 * Заменить все данные контейнера другими.
	 *
	 * @param   array $content новые данные
	 *
	 * @return  $this
	 */
	protected function setContents(array $content)
	{
		$this->store = $content;

		return $this;
	}

	/**
	 * Поиск по индексу
	 *
	 * @param $index
	 *
	 * @return bool
	 */
	protected function hasByIndex($index)
	{
		return array_key_exists($index, $this->toArray());
	}

}