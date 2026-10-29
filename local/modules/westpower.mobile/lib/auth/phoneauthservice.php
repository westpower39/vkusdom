<?php

namespace Westpower\Mobile\Auth;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\UserPhoneAuthTable;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\ErrorCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Formatter;

/**
 * Phone verification. In production the login is by call only (no SMS codes, decision 29.10.2026);
 * the New-Tel call verification is awaiting the site service. Users are found and registered
 * in the standard phone auth table (b_user_phone_auth).
 *
 * Test mode is a stub for the app development: the "sms" channel accepts a fixed code,
 * a call verification is confirmed immediately.
 *
 * The verification ID is a signed token {phone, channel, time}: no own storage.
 */
final class PhoneAuthService
{
	public const CHANNEL_SMS = 'sms';
	public const CHANNEL_CALL = 'call';

	private const SIGN_SALT = 'westpower.mobile.verification';

	public function start(string $phone, string $channel): array
	{
		$normalized = Formatter::phone($phone);
		if ($normalized === null)
		{
			throw ApiException::validation('Неверный формат номера телефона', 'phone');
		}
		if (!in_array($channel, [self::CHANNEL_SMS, self::CHANNEL_CALL], true))
		{
			throw ApiException::validation('Способ подтверждения должен быть sms или call', 'channel');
		}

		if (!$this->isTestMode())
		{
			$this->checkProductionChannel($channel);
		}

		$payload = ['phone' => $normalized, 'channel' => $channel, 'time' => time()];

		return $this->toArray($this->sign($payload), $payload);
	}

	public function status(string $id): array
	{
		return $this->toArray($id, $this->unsign($id));
	}

	/**
	 * Checks the code (SMS) or the confirmation (call) and returns the user ID (registered if new).
	 */
	public function verify(string $id, ?string $code): int
	{
		$payload = $this->unsign($id);
		if ($this->isExpired($payload))
		{
			throw new ApiException(ErrorCode::VERIFICATION_EXPIRED, 'Время подтверждения истекло, запросите код заново');
		}

		$phone = $payload['phone'];
		if ($payload['channel'] === self::CHANNEL_SMS)
		{
			$code = trim((string)$code);
			if ($code === '')
			{
				throw ApiException::validation('Введите код из SMS', 'code');
			}

			if (!$this->isTestMode())
			{
				$this->checkProductionChannel(self::CHANNEL_SMS);
			}
			if ($code !== Config::get('auth_test_code'))
			{
				throw new ApiException(ErrorCode::AUTH_CODE_INVALID, 'Неверный код');
			}
			$userId = $this->ensureUser($phone);
		}
		elseif ($this->isTestMode())
		{
			$userId = $this->ensureUser($phone);
		}
		else
		{
			$this->checkProductionChannel(self::CHANNEL_CALL);
		}

		$this->activate($userId);

		return $userId;
	}

	public function phone(int $userId): ?string
	{
		$row = UserPhoneAuthTable::getList(['filter' => ['=USER_ID' => $userId], 'select' => ['PHONE_NUMBER']])->fetch();

		return $row ? (string)$row['PHONE_NUMBER'] : null;
	}

	/**
	 * Existing user by phone, or a new inactive user registered with the standard CUser::Add
	 * (same way the site registers users by phone). Activated after successful verification.
	 */
	private function ensureUser(string $phone): int
	{
		$row = UserPhoneAuthTable::getList([
			'filter' => ['=PHONE_NUMBER' => UserPhoneAuthTable::normalizePhoneNumber($phone)],
			'select' => ['USER_ID'],
		])->fetch();
		if ($row)
		{
			return (int)$row['USER_ID'];
		}

		$password = bin2hex(random_bytes(12)) . 'Aa1!';
		$user = new \CUser();
		$userId = (int)$user->Add([
			'LOGIN' => ltrim($phone, '+'),
			'PHONE_NUMBER' => $phone,
			'ACTIVE' => 'N',
			'PASSWORD' => $password,
			'CONFIRM_PASSWORD' => $password,
			'GROUP_ID' => array_filter(array_map('intval', explode(',', Option::get('main', 'new_user_registration_def_group', '')))),
		]);
		if ($userId <= 0)
		{
			throw new \RuntimeException('User registration failed: ' . strip_tags((string)$user->LAST_ERROR));
		}

		return $userId;
	}

	private function activate(int $userId): void
	{
		$user = \CUser::GetByID($userId)->Fetch();
		if ($user && $user['ACTIVE'] !== 'Y')
		{
			(new \CUser())->Update($userId, ['ACTIVE' => 'Y']);
		}

		$phoneRow = UserPhoneAuthTable::getList(['filter' => ['=USER_ID' => $userId], 'select' => ['CONFIRMED']])->fetch();
		if ($phoneRow && $phoneRow['CONFIRMED'] !== 'Y' && $phoneRow['CONFIRMED'] !== true)
		{
			UserPhoneAuthTable::update($userId, ['CONFIRMED' => 'Y']);
		}
	}

	/**
	 * Production login is by call only; the call itself is awaiting the site New-Tel service.
	 */
	private function checkProductionChannel(string $channel): never
	{
		if ($channel === self::CHANNEL_SMS)
		{
			throw ApiException::validation('Вход только по звонку', 'channel');
		}

		throw ApiException::unavailable('Вход по звонку пока недоступен');
	}

	private function toArray(string $id, array $payload): array
	{
		$expired = $this->isExpired($payload);
		$isCall = $payload['channel'] === self::CHANNEL_CALL;
		$status = $expired ? 'expired' : ($isCall && $this->isTestMode() ? 'confirmed' : 'pending');

		return [
			'id' => $id,
			'channel' => $payload['channel'],
			'status' => $status,
			'expiresAt' => date(DATE_ATOM, $payload['time'] + $this->ttl()),
			'resendAfter' => max(0, $payload['time'] + 60 - time()),
			'codeLength' => $isCall ? null : strlen(Config::get('auth_test_code')),
			'callPhone' => $isCall ? Config::get('auth_test_call_phone') : null,
		];
	}

	private function sign(array $payload): string
	{
		$value = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

		return (new Signer())->sign($value, self::SIGN_SALT);
	}

	private function unsign(string $id): array
	{
		try
		{
			$value = (new Signer())->unsign($id, self::SIGN_SALT);
		}
		catch (BadSignatureException $e)
		{
			throw ApiException::notFound('Подтверждение не найдено');
		}

		$payload = json_decode(base64_decode(strtr($value, '-_', '+/')), true);
		if (!is_array($payload) || !isset($payload['phone'], $payload['channel'], $payload['time']))
		{
			throw ApiException::notFound('Подтверждение не найдено');
		}

		return $payload;
	}

	private function isExpired(array $payload): bool
	{
		return $payload['time'] + $this->ttl() < time();
	}

	private function ttl(): int
	{
		return max(60, Config::getInt('verification_ttl'));
	}

	private function isTestMode(): bool
	{
		return Config::getBool('auth_test_mode');
	}
}
