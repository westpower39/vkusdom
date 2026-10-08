<?php

namespace Westpower\Mobile;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

/**
 * Module settings (admin page: Settings → Module settings → westpower.mobile).
 * Defaults are in default_option.php.
 */
final class Config
{
	public const MODULE_ID = 'westpower.mobile';

	private static ?string $siteId = null;

	public static function get(string $name): string
	{
		return (string)Option::get(self::MODULE_ID, $name);
	}

	public static function getInt(string $name): int
	{
		return (int)self::get($name);
	}

	public static function getBool(string $name): bool
	{
		return self::get($name) === 'Y';
	}

	public static function set(string $name, string $value): void
	{
		Option::set(self::MODULE_ID, $name, $value);
	}

	/**
	 * Comma/space/newline separated list of codes.
	 */
	public static function getCodes(string $name): array
	{
		$parts = preg_split('/[\s,;]+/', self::get($name), -1, PREG_SPLIT_NO_EMPTY);

		return array_values(array_unique(array_map('trim', $parts)));
	}

	/**
	 * Non-empty lines of a textarea option.
	 */
	public static function getLines(string $name): array
	{
		$lines = preg_split('/\r\n|\r|\n/', self::get($name));

		return array_values(array_filter(array_map('trim', $lines), 'strlen'));
	}

	public static function siteId(): string
	{
		if (self::$siteId === null)
		{
			self::$siteId = (string)(\CSite::GetDefSite() ?: 's1');
		}

		return self::$siteId;
	}

	/**
	 * Base URL for absolute links (images, callbacks): option or current host.
	 */
	public static function publicUrl(): string
	{
		$url = rtrim(self::get('public_url'), '/');
		if ($url !== '')
		{
			return $url;
		}

		$request = Application::getInstance()->getContext()->getRequest();

		return ($request->isHttps() ? 'https' : 'http') . '://' . $request->getHttpHost();
	}

	/**
	 * Bitrix order status ID => app status code.
	 */
	public static function orderStatusMap(): array
	{
		$map = [];
		foreach (self::getLines('order_status_map') as $line)
		{
			[$statusId, $appStatus] = array_pad(array_map('trim', explode('=', $line, 2)), 2, '');
			if ($statusId !== '' && $appStatus !== '')
			{
				$map[$statusId] = $appStatus;
			}
		}

		return $map;
	}
}
