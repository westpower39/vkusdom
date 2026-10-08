<?php

namespace Westpower\Mobile\Auth;

/**
 * Identity of the current request: anonymous (no token), guest session or user session.
 */
final class AuthContext
{
	public ?int $sessionId;
	public ?int $userId;
	public ?int $fuserId;

	public function __construct(?int $sessionId = null, ?int $userId = null, ?int $fuserId = null)
	{
		$this->sessionId = $sessionId;
		$this->userId = $userId;
		$this->fuserId = $fuserId;
	}

	public static function anonymous(): self
	{
		return new self();
	}

	public function hasSession(): bool
	{
		return $this->sessionId !== null;
	}

	public function isUser(): bool
	{
		return $this->userId !== null;
	}

	/**
	 * User groups for price and discount calculation.
	 *
	 * @return int[]
	 */
	public function userGroups(): array
	{
		if ($this->userId === null)
		{
			return [2];
		}

		return array_map('intval', \CUser::GetUserGroup($this->userId));
	}
}
