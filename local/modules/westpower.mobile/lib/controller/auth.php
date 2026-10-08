<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Auth\AuthService;
use Westpower\Mobile\Auth\PhoneAuthService;

/**
 * App sessions (TZ 8.5) and phone verification through the standard Bitrix phone auth.
 */
final class Auth extends Base
{
	/** POST /api/v1/auth/guest */
	public function guestAction()
	{
		return $this->respond(fn () => (new AuthService())->guest());
	}

	/** POST /api/v1/auth/verifications */
	public function createVerificationAction()
	{
		return $this->respond(function () {
			$body = $this->body();

			return (new PhoneAuthService())->start((string)($body['phone'] ?? ''), (string)($body['channel'] ?? ''));
		});
	}

	/** GET /api/v1/auth/verifications/{id} */
	public function verificationAction(string $id)
	{
		return $this->respond(fn () => (new PhoneAuthService())->status($id));
	}

	/** POST /api/v1/auth/login */
	public function loginAction()
	{
		return $this->respond(function () {
			$body = $this->body();

			return (new AuthService())->login(
				$this->context(),
				(string)($body['verificationId'] ?? ''),
				isset($body['code']) ? (string)$body['code'] : null,
				($body['personalDataConsent'] ?? false) === true
			);
		});
	}

	/** POST /api/v1/auth/refresh */
	public function refreshAction()
	{
		return $this->respond(fn () => (new AuthService())->refresh((string)($this->body()['refreshToken'] ?? '')));
	}

	/** POST /api/v1/auth/logout */
	public function logoutAction()
	{
		return $this->respond(function () {
			(new AuthService())->logout($this->context());

			return null;
		});
	}
}
