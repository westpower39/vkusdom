<?php

namespace Westpower\Mobile\Dictionary;

/**
 * API error codes and their HTTP statuses.
 */
final class ErrorCode
{
	public const VALIDATION_ERROR = 'VALIDATION_ERROR';
	public const UNAUTHORIZED = 'UNAUTHORIZED';
	public const NOT_FOUND = 'NOT_FOUND';
	public const INTERNAL_ERROR = 'INTERNAL_ERROR';
	public const PROMO_CODE_INVALID = 'PROMO_CODE_INVALID';
	public const AUTH_CODE_INVALID = 'AUTH_CODE_INVALID';
	public const VERIFICATION_EXPIRED = 'VERIFICATION_EXPIRED';
	public const VERIFICATION_PENDING = 'VERIFICATION_PENDING';
	public const TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
	public const FORBIDDEN = 'FORBIDDEN';
	public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

	private const HTTP_STATUSES = [
		self::VALIDATION_ERROR => 400,
		self::UNAUTHORIZED => 401,
		self::NOT_FOUND => 404,
		self::INTERNAL_ERROR => 500,
		self::PROMO_CODE_INVALID => 400,
		self::AUTH_CODE_INVALID => 400,
		self::VERIFICATION_EXPIRED => 400,
		self::VERIFICATION_PENDING => 409,
		self::TOO_MANY_REQUESTS => 429,
		self::FORBIDDEN => 403,
		self::SERVICE_UNAVAILABLE => 503,
	];

	/**
	 * Unknown string codes come from Bitrix filters (bad request), numeric ones from exceptions.
	 */
	public static function getHttpStatus($code): int
	{
		if (isset(self::HTTP_STATUSES[$code]))
		{
			return self::HTTP_STATUSES[$code];
		}

		return is_string($code) && $code !== '' && !is_numeric($code) ? 400 : 500;
	}
}
