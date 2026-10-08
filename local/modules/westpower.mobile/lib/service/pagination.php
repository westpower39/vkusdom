<?php

namespace Westpower\Mobile\Service;

use Westpower\Mobile\Exception\ApiException;

/**
 * Pagination parameters and the Pagination contract object.
 */
final class Pagination
{
	public const MAX_LIMIT = 50;

	/**
	 * Validates page/limit query values.
	 *
	 * @return array{0: int, 1: int}
	 */
	public static function parse($page, $limit, int $defaultLimit = 20): array
	{
		$page = $page === null || $page === '' ? 1 : $page;
		$limit = $limit === null || $limit === '' ? $defaultLimit : $limit;

		if (!is_numeric($page) || (int)$page < 1 || (int)$page != $page)
		{
			throw ApiException::validation('Параметр page должен быть целым числом от 1', 'page');
		}
		if (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > self::MAX_LIMIT || (int)$limit != $limit)
		{
			throw ApiException::validation(sprintf('Параметр limit должен быть от 1 до %d', self::MAX_LIMIT), 'limit');
		}

		return [(int)$page, (int)$limit];
	}

	public static function make(int $page, int $limit, int $total): array
	{
		return [
			'page' => $page,
			'limit' => $limit,
			'total' => $total,
			'pages' => (int)ceil($total / max(1, $limit)),
		];
	}
}
