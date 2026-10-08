<?php

namespace Westpower\Mobile\Auth;

use Bitrix\Main\UserTable;
use Westpower\Mobile\Exception\ApiException;

/**
 * App sessions: guest session, login by a verified phone, token refresh, logout.
 */
final class AuthService
{
	public function guest(): array
	{
		return TokenService::issue(null, FuserService::createGuest());
	}

	public function login(AuthContext $context, string $verificationId, ?string $code, bool $personalDataConsent): array
	{
		if (!$personalDataConsent)
		{
			throw ApiException::validation('Подтвердите согласие на обработку персональных данных', 'personalDataConsent');
		}

		$phoneAuth = new PhoneAuthService();
		$userId = $phoneAuth->verify($verificationId, $code);
		$userFuserId = FuserService::forUser($userId);

		$guestSessionId = null;
		if ($context->hasSession() && !$context->isUser())
		{
			FuserService::transferCart((int)$context->fuserId, $userFuserId);
			$guestSessionId = $context->sessionId;
		}

		$tokens = TokenService::issue($userId, $userFuserId, $guestSessionId);

		return $tokens + ['user' => $this->user($userId, $phoneAuth)];
	}

	public function refresh(string $refreshToken): array
	{
		if ($refreshToken === '')
		{
			throw ApiException::validation('Передайте refreshToken', 'refreshToken');
		}

		return TokenService::refresh($refreshToken);
	}

	public function logout(AuthContext $context): void
	{
		if ($context->hasSession())
		{
			TokenService::revoke((int)$context->sessionId);
		}
	}

	private function user(int $userId, PhoneAuthService $phoneAuth): array
	{
		$row = UserTable::getList(['filter' => ['=ID' => $userId], 'select' => ['ID', 'NAME']])->fetch();

		return [
			'id' => $userId,
			'phone' => $phoneAuth->phone($userId),
			'name' => $row && (string)$row['NAME'] !== '' ? $row['NAME'] : null,
		];
	}
}
