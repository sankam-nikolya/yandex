<?php

/**
 * Created by Arhitector.
 * Date: 08.03.2016
 * Time: 14:22
 */

namespace Mackey\Yandex\Disk;


class Operation
{
	/**
	 * Загрузка по удалённым ссылкам
	 */
	const TYPE_UPLOAD = 1;

	/**
	 * Удаление ресурса
	 */
	const TYPE_DELETE = 10;

	/**
	 * Восстановление ресурса
	 */
	const TYPE_RESTORE = 20;


}