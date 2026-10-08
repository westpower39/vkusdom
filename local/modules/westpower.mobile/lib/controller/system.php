<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Dictionary\ErrorCode;

/**
 * Service endpoints: health check and JSON 404 for unknown API paths.
 */
final class System extends Base
{
	public const API_VERSION = 'v1';

	/**
	 * GET /api/v1/ping
	 */
	public function pingAction(): array
	{
		return [
			'apiVersion' => self::API_VERSION,
			'serverTime' => (new \DateTimeImmutable())->format(DATE_ATOM),
		];
	}

	/**
	 * Fallback for any unknown /api/v1/* path.
	 */
	public function notFoundAction()
	{
		return $this->addApiError(ErrorCode::NOT_FOUND, 'Метод API не найден');
	}
}
