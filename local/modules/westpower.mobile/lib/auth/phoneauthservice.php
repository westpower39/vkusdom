<?php

namespace Westpower\Mobile\Auth;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Sms\Event as SmsEvent;
use Bitrix\Main\UserPhoneAuthTable;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\ErrorCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Formatter;

/**
 * Phone verification through the standard Bitrix phone auth (CUser::SendPhoneCode / CUser::VerifyPhoneCode,
 * b_user_phone_auth). The SMS/call providers are set up on the site (messageservice) — not in this module.
 *
 * While the site providers are not ready, test mode is a stub: no SMS is sent, the code is fixed,
 * a call verification is confirmed immediately. Real call verification is awaiting the site implementation.
 *
 * The verification ID is a signed token {phone, channel, time[, userId for a phone change]}: no own storage.
 */
final class PhoneAuthService
{
	public const CHANNEL_SMS = 'sms';
	public const CHANNEL_CALL = 'call';

	private const SMS_EVENT = 'SMS_USER_CONFIRM_NUMBER';
	private const SIGN_SALT = 'westpower.mobile.verification';
	private const STANDARD_CODE_LENGTH = 6;

	public function start(string $phone, string $channel): array
	{
		$normalized = $this->validate($phone, $channel);

		if (!$this->isTestMode())
		{
			if ($channel === self::CHANNEL_CALL)
			{
				throw ApiException::unavailable('Вход по звонку пока недоступен, используйте SMS');
			}

			$userId = $this->ensureUser($normalized);
			$retryAfter = $this->resendAfter($userId);
			if ($retryAfter > 0)
			{
				throw new ApiException(
					ErrorCode::TOO_MANY_REQUESTS,
					sprintf('Повторно запросить код можно через %d сек.', $retryAfter),
					['retryAfter' => $retryAfter]
				);
			}

			$result = \CUser::SendPhoneCode($normalized, self::SMS_EVENT, Config::siteId());
			if (!$result->isSuccess())
			{
				AddMessage2Log('SendPhoneCode failed: ' . implode('; ', $result->getErrorMessages()), Config::MODULE_ID);
				throw ApiException::unavailable('Не удалось отправить SMS, попробуйте позже');
			}
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
		if (isset($payload['userId']))
		{
			throw ApiException::notFound('Подтверждение не найдено');
		}
		$this->checkExpired($payload);

		$phone = $payload['phone'];
		if ($payload['channel'] === self::CHANNEL_SMS)
		{
			$code = trim((string)$code);
			if ($code === '')
			{
				throw ApiException::validation('Введите код из SMS', 'code');
			}

			if ($this->isTestMode())
			{
				if ($code !== Config::get('auth_test_code'))
				{
					throw new ApiException(ErrorCode::AUTH_CODE_INVALID, 'Неверный код');
				}
				$userId = $this->ensureUser($phone);
			}
			else
			{
				$userId = (int)\CUser::VerifyPhoneCode($phone, $code);
				if ($userId <= 0)
				{
					throw new ApiException(ErrorCode::AUTH_CODE_INVALID, 'Неверный код');
				}
			}
		}
		elseif ($this->isTestMode())
		{
			$userId = $this->ensureUser($phone);
		}
		else
		{
			throw ApiException::unavailable('Вход по звонку пока недоступен, используйте SMS');
		}

		$this->activate($userId);

		return $userId;
	}

	/**
	 * Starts confirmation of a new phone number for a signed-in user.
	 * The code is generated from the user's own phone auth secret and sent to the new number —
	 * the same as CUser::SendPhoneCode does for the current one, so it is checked with CUser::VerifyPhoneCode.
	 */
	public function startPhoneChange(int $userId, string $phone, string $channel): array
	{
		$normalized = $this->validate($phone, $channel);
		if ($normalized === $this->phone($userId))
		{
			throw ApiException::validation('Это ваш текущий номер', 'phone');
		}
		$this->checkPhoneFree($normalized, $userId);

		if (!$this->isTestMode())
		{
			if ($channel === self::CHANNEL_CALL)
			{
				throw ApiException::unavailable('Подтверждение звонком пока недоступно, используйте SMS');
			}

			$retryAfter = $this->resendAfter($userId);
			if ($retryAfter > 0)
			{
				throw new ApiException(
					ErrorCode::TOO_MANY_REQUESTS,
					sprintf('Повторно запросить код можно через %d сек.', $retryAfter),
					['retryAfter' => $retryAfter]
				);
			}

			$code = \CUser::GeneratePhoneCode($userId);
			if (!$code)
			{
				throw ApiException::unavailable('Не удалось отправить SMS, попробуйте позже');
			}
			$sms = new SmsEvent(self::SMS_EVENT, ['USER_PHONE' => $normalized, 'CODE' => $code]);
			$sms->setSite(Config::siteId());
			$result = $sms->send(true);
			if (!$result->isSuccess())
			{
				AddMessage2Log('Phone change SMS failed: ' . implode('; ', $result->getErrorMessages()), Config::MODULE_ID);
				throw ApiException::unavailable('Не удалось отправить SMS, попробуйте позже');
			}
		}

		$payload = ['phone' => $normalized, 'channel' => $channel, 'time' => time(), 'userId' => $userId];

		return $this->toArray($this->sign($payload), $payload);
	}

	/**
	 * Checks the code (SMS) or the confirmation (call) of a new number and sets it as the user's login phone.
	 */
	public function confirmPhoneChange(int $userId, string $id, ?string $code): void
	{
		$payload = $this->unsign($id);
		if ((int)($payload['userId'] ?? 0) !== $userId)
		{
			throw ApiException::notFound('Подтверждение не найдено');
		}
		$this->checkExpired($payload);

		if ($payload['channel'] === self::CHANNEL_SMS)
		{
			$code = trim((string)$code);
			if ($code === '')
			{
				throw ApiException::validation('Введите код из SMS', 'code');
			}

			$isValid = $this->isTestMode()
				? $code === Config::get('auth_test_code')
				: (int)\CUser::VerifyPhoneCode((string)$this->phone($userId), $code) === $userId;
			if (!$isValid)
			{
				throw new ApiException(ErrorCode::AUTH_CODE_INVALID, 'Неверный код');
			}
		}
		elseif (!$this->isTestMode())
		{
			throw ApiException::unavailable('Подтверждение звонком пока недоступно, используйте SMS');
		}

		$phone = $payload['phone'];
		$this->checkPhoneFree($phone, $userId);

		// PERSONAL_PHONE too: the site handler OnAfterUserUpdate copies it into the login phone on every update.
		$fields = ['PHONE_NUMBER' => $phone, 'PERSONAL_PHONE' => $phone];
		$user = \CUser::GetByID($userId)->Fetch();
		$oldPhone = $this->phone($userId);
		if ($user && $oldPhone !== null && $user['LOGIN'] === ltrim($oldPhone, '+'))
		{
			// The login is the phone digits (site and API registration), keep it free for the old number.
			$fields['LOGIN'] = ltrim($phone, '+');
		}

		$cUser = new \CUser();
		if (!$cUser->Update($userId, $fields))
		{
			throw ApiException::validation(strip_tags((string)$cUser->LAST_ERROR), 'phone');
		}
		$this->activate($userId);
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
		$existingId = $this->userIdByPhone($phone);
		if ($existingId !== null)
		{
			return $existingId;
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

	private function validate(string $phone, string $channel): string
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

		return $normalized;
	}

	private function userIdByPhone(string $phone): ?int
	{
		$row = UserPhoneAuthTable::getList([
			'filter' => ['=PHONE_NUMBER' => UserPhoneAuthTable::normalizePhoneNumber($phone)],
			'select' => ['USER_ID'],
		])->fetch();

		return $row ? (int)$row['USER_ID'] : null;
	}

	private function checkPhoneFree(string $phone, int $userId): void
	{
		$ownerId = $this->userIdByPhone($phone);
		if ($ownerId !== null && $ownerId !== $userId)
		{
			throw ApiException::validation('Номер уже привязан к другому аккаунту', 'phone');
		}
	}

	private function checkExpired(array $payload): void
	{
		if ($this->isExpired($payload))
		{
			throw new ApiException(ErrorCode::VERIFICATION_EXPIRED, 'Время подтверждения истекло, запросите код заново');
		}
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

	private function resendAfter(int $userId): int
	{
		$interval = defined('CUser::PHONE_CODE_RESEND_INTERVAL') ? (int)\CUser::PHONE_CODE_RESEND_INTERVAL : 60;
		$row = UserPhoneAuthTable::getList(['filter' => ['=USER_ID' => $userId], 'select' => ['DATE_SENT']])->fetch();
		if (!$row || !$row['DATE_SENT'])
		{
			return 0;
		}

		return max(0, $row['DATE_SENT']->getTimestamp() + $interval - time());
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
			'codeLength' => $isCall ? null : ($this->isTestMode() ? strlen(Config::get('auth_test_code')) : self::STANDARD_CODE_LENGTH),
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
