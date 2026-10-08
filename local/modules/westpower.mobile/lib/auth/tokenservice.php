<?php

namespace Westpower\Mobile\Auth;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Westpower\Mobile\Config;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Model\SessionTable;

/**
 * Access/refresh tokens of app sessions (TZ 8.5: short access token, refresh token for 30 days).
 */
final class TokenService
{
	private const LAST_USED_UPDATE_INTERVAL = 60;

	private static ?AuthContext $current = null;

	/**
	 * Identity of the current request. A present but invalid token is an error (the app must refresh it).
	 */
	public static function current(): AuthContext
	{
		if (self::$current === null)
		{
			self::$current = self::resolve(self::readToken());
		}

		return self::$current;
	}

	public static function setCurrent(AuthContext $context): void
	{
		self::$current = $context;
	}

	public static function resolve(?string $token): AuthContext
	{
		if ($token === null || $token === '')
		{
			return AuthContext::anonymous();
		}

		$row = SessionTable::getList([
			'filter' => ['=ACCESS_HASH' => self::hash($token)],
			'select' => ['ID', 'USER_ID', 'FUSER_ID', 'ACCESS_EXPIRES_AT', 'LAST_USED_AT'],
		])->fetch();
		if (!$row || $row['ACCESS_EXPIRES_AT']->getTimestamp() < time())
		{
			throw ApiException::unauthorized('Токен недействителен или истёк');
		}

		if (time() - $row['LAST_USED_AT']->getTimestamp() > self::LAST_USED_UPDATE_INTERVAL)
		{
			SessionTable::update($row['ID'], ['LAST_USED_AT' => new DateTime()]);
		}

		return new AuthContext(
			(int)$row['ID'],
			$row['USER_ID'] !== null ? (int)$row['USER_ID'] : null,
			(int)$row['FUSER_ID']
		);
	}

	/**
	 * Creates a new session or rotates tokens of an existing one. Returns the Tokens contract object.
	 */
	public static function issue(?int $userId, int $fuserId, ?int $sessionId = null): array
	{
		$access = self::generate();
		$refresh = self::generate();
		$now = time();
		$accessExpires = $now + max(60, Config::getInt('access_token_ttl'));
		$refreshExpires = $now + max(3600, Config::getInt('refresh_token_ttl'));

		$fields = [
			'USER_ID' => $userId,
			'FUSER_ID' => $fuserId,
			'ACCESS_HASH' => self::hash($access),
			'ACCESS_EXPIRES_AT' => DateTime::createFromTimestamp($accessExpires),
			'REFRESH_HASH' => self::hash($refresh),
			'REFRESH_EXPIRES_AT' => DateTime::createFromTimestamp($refreshExpires),
			'LAST_USED_AT' => new DateTime(),
		];

		if ($sessionId !== null)
		{
			$result = SessionTable::update($sessionId, $fields);
		}
		else
		{
			$fields['CREATED_AT'] = new DateTime();
			$fields['USER_AGENT'] = mb_substr((string)Application::getInstance()->getContext()->getServer()->get('HTTP_USER_AGENT'), 0, 255);
			$result = SessionTable::add($fields);
			$sessionId = (int)$result->getId();
		}
		if (!$result->isSuccess())
		{
			throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
		}

		self::$current = new AuthContext($sessionId, $userId, $fuserId);

		return [
			'accessToken' => $access,
			'accessTokenExpiresAt' => date(DATE_ATOM, $accessExpires),
			'refreshToken' => $refresh,
			'refreshTokenExpiresAt' => date(DATE_ATOM, $refreshExpires),
		];
	}

	public static function refresh(string $refreshToken): array
	{
		$row = SessionTable::getList([
			'filter' => ['=REFRESH_HASH' => self::hash($refreshToken)],
			'select' => ['ID', 'USER_ID', 'FUSER_ID', 'REFRESH_EXPIRES_AT'],
		])->fetch();
		if (!$row || $row['REFRESH_EXPIRES_AT']->getTimestamp() < time())
		{
			throw ApiException::unauthorized('Сессия истекла, войдите заново');
		}

		return self::issue(
			$row['USER_ID'] !== null ? (int)$row['USER_ID'] : null,
			(int)$row['FUSER_ID'],
			(int)$row['ID']
		);
	}

	public static function revoke(int $sessionId): void
	{
		SessionTable::delete($sessionId);
		self::$current = AuthContext::anonymous();
	}

	public static function getCoupons(int $sessionId): array
	{
		$row = SessionTable::getList(['filter' => ['=ID' => $sessionId], 'select' => ['COUPONS']])->fetch();
		$coupons = $row && $row['COUPONS'] ? json_decode($row['COUPONS'], true) : [];

		return is_array($coupons) ? array_values($coupons) : [];
	}

	public static function setCoupons(int $sessionId, array $coupons): void
	{
		SessionTable::update($sessionId, ['COUPONS' => json_encode(array_values(array_unique($coupons)), JSON_UNESCAPED_UNICODE)]);
	}

	private static function readToken(): ?string
	{
		$server = Application::getInstance()->getContext()->getServer();
		$header = (string)($server->get('HTTP_AUTHORIZATION')
			?: $server->get('REDIRECT_HTTP_AUTHORIZATION')
			?: $server->get('REDIRECT_REMOTE_USER')
			?: $server->get('REMOTE_USER'));
		if ($header === '' && function_exists('getallheaders'))
		{
			foreach (getallheaders() as $name => $value)
			{
				if (strcasecmp($name, 'Authorization') === 0)
				{
					$header = (string)$value;
				}
			}
		}

		return preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches) ? $matches[1] : null;
	}

	private static function generate(): string
	{
		return bin2hex(random_bytes(32));
	}

	private static function hash(string $token): string
	{
		return hash('sha256', $token);
	}
}
