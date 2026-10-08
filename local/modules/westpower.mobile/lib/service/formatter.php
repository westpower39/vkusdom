<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Uri;
use Westpower\Mobile\Config;

/**
 * Shared formatting rules of the API contract: images, money, dates.
 */
final class Formatter
{
	private static array $files = [];

	/**
	 * Absolute URL of a file by its b_file ID, null when there is no file.
	 */
	public static function image($fileId): ?string
	{
		$fileId = (int)$fileId;
		if ($fileId <= 0)
		{
			return null;
		}

		if (!array_key_exists($fileId, self::$files))
		{
			$path = (string)\CFile::GetPath($fileId);
			self::$files[$fileId] = $path !== '' ? self::absoluteUrl($path) : null;
		}

		return self::$files[$fileId];
	}

	public static function absoluteUrl(string $path): string
	{
		if (preg_match('#^https?://#i', $path))
		{
			return $path;
		}

		return Config::publicUrl() . Uri::urnEncode($path);
	}

	/**
	 * Price object: {current, old, discountPercent, currency}. "old" is set only when it is higher than "current".
	 */
	public static function price(float $current, ?float $old = null, string $currency = 'RUB'): array
	{
		$current = round($current, 2);
		$old = $old !== null ? round($old, 2) : null;
		if ($old !== null && $old - $current < 0.01)
		{
			$old = null;
		}

		return [
			'current' => $current,
			'old' => $old,
			'discountPercent' => $old !== null && $old > 0 ? (int)round(($old - $current) / $old * 100) : null,
			'currency' => $currency,
		];
	}

	public static function money(float $value): float
	{
		return round($value, 2);
	}

	/**
	 * ISO 8601 date-time with time zone, null for empty values.
	 */
	public static function dateTime($value): ?string
	{
		if ($value instanceof Date)
		{
			return $value->format(DATE_ATOM);
		}
		if ($value instanceof \DateTimeInterface)
		{
			return $value->format(DATE_ATOM);
		}
		if (is_string($value) && $value !== '')
		{
			$timestamp = MakeTimeStamp($value);

			return $timestamp ? date(DATE_ATOM, $timestamp) : null;
		}

		return null;
	}

	/**
	 * Plain text from HTML/text property values ({TEXT, TYPE} arrays included).
	 */
	public static function text($value): ?string
	{
		if (is_array($value))
		{
			$value = $value['TEXT'] ?? '';
		}
		$value = trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		return $value !== '' ? $value : null;
	}

	/**
	 * Normalized phone: +7XXXXXXXXXX, or null when it is not a valid Russian mobile/landline number.
	 */
	public static function phone(string $phone): ?string
	{
		$digits = preg_replace('/\D+/', '', $phone);
		if (strlen($digits) === 10)
		{
			$digits = '7' . $digits;
		}
		if (strlen($digits) === 11 && $digits[0] === '8')
		{
			$digits = '7' . substr($digits, 1);
		}

		return (strlen($digits) === 11 && $digits[0] === '7') ? '+' . $digits : null;
	}
}
