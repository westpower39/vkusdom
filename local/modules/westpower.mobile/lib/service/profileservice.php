<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\UserTable;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Auth\PhoneAuthService;
use Westpower\Mobile\Exception\ApiException;

/**
 * User profile (TZ 4.7): name, e-mail; phone is changed only through phone verification (not yet supported).
 * Notification settings are awaiting the site storage — returned as null, saving is not available yet.
 */
final class ProfileService
{
	public function get(AuthContext $context): array
	{
		$user = UserTable::getList([
			'filter' => ['=ID' => $context->userId],
			'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'],
		])->fetch();
		if (!$user)
		{
			throw ApiException::unauthorized();
		}

		return [
			'id' => (int)$user['ID'],
			'phone' => (new PhoneAuthService())->phone((int)$user['ID']),
			'name' => (string)$user['NAME'] !== '' ? (string)$user['NAME'] : null,
			'lastName' => (string)$user['LAST_NAME'] !== '' ? (string)$user['LAST_NAME'] : null,
			'email' => (string)$user['EMAIL'] !== '' ? (string)$user['EMAIL'] : null,
			'notifications' => null,
		];
	}

	public function update(AuthContext $context, array $input): array
	{
		$fields = [];
		foreach (['name' => 'NAME', 'lastName' => 'LAST_NAME'] as $key => $field)
		{
			if (array_key_exists($key, $input))
			{
				$fields[$field] = trim((string)$input[$key]);
			}
		}
		if (array_key_exists('email', $input))
		{
			$email = trim((string)$input['email']);
			if ($email !== '' && !check_email($email))
			{
				throw ApiException::validation('Неверный формат e-mail', 'email');
			}
			$fields['EMAIL'] = $email;
		}
		if (array_key_exists('phone', $input))
		{
			throw ApiException::validation('Смена телефона пока не поддерживается', 'phone');
		}

		if (!empty($fields))
		{
			$user = new \CUser();
			if (!$user->Update((int)$context->userId, $fields))
			{
				throw ApiException::validation(strip_tags((string)$user->LAST_ERROR), 'profile');
			}
		}

		return $this->get($context);
	}
}
