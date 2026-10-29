<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\UserPropsTable;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Auth\PhoneAuthService;
use Westpower\Mobile\Auth\TokenService;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * User profile (TZ 4.7): full name, birth date, e-mail; the phone is changed only through
 * phone verification (PhoneAuthService). Notification settings are awaiting the site storage — returned as null.
 *
 * Full name is one field, as on the site: it is stored in NAME (decision 29.10.2026).
 * Users with LAST_NAME/SECOND_NAME filled (admin, imported) get them joined as "Last First Middle".
 */
final class ProfileService
{
	private const BIRTH_DATE_FORMAT = 'Y-m-d';

	public function get(AuthContext $context): array
	{
		$user = UserTable::getList([
			'filter' => ['=ID' => $context->userId],
			'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PERSONAL_BIRTHDAY'],
		])->fetch();
		if (!$user)
		{
			throw ApiException::unauthorized();
		}

		return [
			'id' => (int)$user['ID'],
			'phone' => (new PhoneAuthService())->phone((int)$user['ID']),
			'fullName' => self::fullName($user),
			'birthDate' => $user['PERSONAL_BIRTHDAY'] instanceof Date ? $user['PERSONAL_BIRTHDAY']->format(self::BIRTH_DATE_FORMAT) : null,
			'email' => (string)$user['EMAIL'] !== '' ? (string)$user['EMAIL'] : null,
			'notifications' => null,
		];
	}

	public function update(AuthContext $context, array $input): array
	{
		$fields = [];
		if (array_key_exists('fullName', $input))
		{
			$fields['NAME'] = trim((string)preg_replace('/\s+/u', ' ', (string)$input['fullName']));
			$fields['LAST_NAME'] = '';
			$fields['SECOND_NAME'] = '';
		}
		if (array_key_exists('birthDate', $input))
		{
			$fields['PERSONAL_BIRTHDAY'] = $this->birthDate($input['birthDate']);
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
			throw ApiException::validation('Номер меняется с подтверждением: POST /profile/phone/verifications', 'phone');
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

	public function changePhone(AuthContext $context, string $verificationId, ?string $code): array
	{
		if ($verificationId === '')
		{
			throw ApiException::validation('Передайте verificationId', 'verificationId');
		}
		(new PhoneAuthService())->confirmPhoneChange((int)$context->userId, $verificationId, $code);

		return $this->get($context);
	}

	/**
	 * Account deletion (App Store requirement). Bitrix does not delete a user with orders, and orders are kept
	 * for accounting, so the account is deactivated and anonymized: personal fields are cleared, the phone is
	 * released for a new registration, buyer profiles and favorites are deleted, all app sessions are revoked.
	 */
	public function delete(AuthContext $context): void
	{
		$userId = (int)$context->userId;

		$fields = [
			'ACTIVE' => 'N',
			'LOGIN' => 'deleted_' . $userId,
			'NAME' => '',
			'LAST_NAME' => '',
			'SECOND_NAME' => '',
			'EMAIL' => '',
			'PERSONAL_PHONE' => '',
			'PERSONAL_BIRTHDAY' => '',
		];
		foreach (PropertyCode::USER_PERSONAL_FIELDS as $field)
		{
			$fields[$field] = '';
		}
		$user = new \CUser();
		if (!$user->Update($userId, $fields))
		{
			throw new \RuntimeException('User anonymization failed: ' . strip_tags((string)$user->LAST_ERROR));
		}

		if (UserPhoneAuthTable::getByPrimary($userId)->fetch())
		{
			UserPhoneAuthTable::delete($userId);
		}

		Loader::includeModule('sale');
		$profiles = UserPropsTable::getList(['filter' => ['=USER_ID' => $userId], 'select' => ['ID']]);
		while ($profile = $profiles->fetch())
		{
			\CSaleOrderUserProps::Delete((int)$profile['ID']);
		}

		$favorites = EntityResolver::hlDataClass(EntityCode::HL_FAVORITES);
		$rows = $favorites::getList(['filter' => ['=UF_USER_ID' => $userId], 'select' => ['ID']]);
		while ($row = $rows->fetch())
		{
			$favorites::delete($row['ID']);
		}

		TokenService::revokeUser($userId);
	}

	/**
	 * @param array $user row with NAME, LAST_NAME, SECOND_NAME
	 */
	public static function fullName(array $user): ?string
	{
		$parts = array_filter(
			[$user['LAST_NAME'] ?? '', $user['NAME'] ?? '', $user['SECOND_NAME'] ?? ''],
			fn ($part) => trim((string)$part) !== ''
		);

		return $parts ? implode(' ', array_map('trim', $parts)) : null;
	}

	/**
	 * YYYY-MM-DD from the app → site date format for CUser::Update; empty value clears the field.
	 */
	private function birthDate($value): string
	{
		$value = trim((string)$value);
		if ($value === '')
		{
			return '';
		}

		$date = \DateTime::createFromFormat('!' . self::BIRTH_DATE_FORMAT, $value);
		if (!$date || $date->format(self::BIRTH_DATE_FORMAT) !== $value || $date > new \DateTime())
		{
			throw ApiException::validation('Дата рождения в формате ГГГГ-ММ-ДД, не позже сегодняшней', 'birthDate');
		}

		return (new Date($value, self::BIRTH_DATE_FORMAT))->toString();
	}
}
