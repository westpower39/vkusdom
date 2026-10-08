<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Service\OrderProfileService;
use Westpower\Mobile\Service\Pagination;
use Westpower\Mobile\Service\ProfileService;
use Westpower\Mobile\Service\RatingService;

/**
 * Personal account (TZ 4.7, 4.9–4.11). Features whose storage is not implemented on the site yet
 * answer 503 SERVICE_UNAVAILABLE ("awaiting the site").
 */
final class Profile extends Base
{
	/** GET /api/v1/profile */
	public function profileAction()
	{
		return $this->respond(fn () => (new ProfileService())->get($this->userContext()));
	}

	/** PUT /api/v1/profile */
	public function updateProfileAction()
	{
		return $this->respond(fn () => (new ProfileService())->update($this->userContext(), $this->body()));
	}

	/** PUT /api/v1/profile/notifications — awaiting the site storage */
	public function updateNotificationsAction()
	{
		return $this->respond(function () {
			$this->userContext();

			return $this->notImplemented();
		});
	}

	/** GET /api/v1/order-profiles */
	public function orderProfilesAction()
	{
		return $this->respond(fn () => (new OrderProfileService())->list($this->userContext()));
	}

	/** GET /api/v1/order-profiles/fields?personType= */
	public function orderProfileFieldsAction()
	{
		return $this->respond(fn () => (new OrderProfileService())->fields((string)$this->query('personType')));
	}

	/** POST /api/v1/order-profiles */
	public function createOrderProfileAction()
	{
		return $this->respond(fn () => (new OrderProfileService())->save($this->userContext(), null, $this->body()));
	}

	/** PUT /api/v1/order-profiles/{id} */
	public function updateOrderProfileAction(int $id)
	{
		return $this->respond(fn () => (new OrderProfileService())->save($this->userContext(), $id, $this->body()));
	}

	/** DELETE /api/v1/order-profiles/{id} */
	public function deleteOrderProfileAction(int $id)
	{
		return $this->respond(function () use ($id) {
			(new OrderProfileService())->delete($this->userContext(), $id);

			return null;
		});
	}

	/** GET /api/v1/ratings/products */
	public function ratingsAction()
	{
		return $this->respond(function () {
			[$page, $limit] = Pagination::parse($this->query('page'), $this->query('limit'));

			return (new RatingService())->list($this->userContext(), $page, $limit);
		});
	}

	/** PUT /api/v1/ratings/products/{productId} — awaiting the site storage */
	public function setRatingAction(int $productId)
	{
		return $this->respond(function () {
			$this->userContext();

			return $this->notImplemented();
		});
	}

	/** POST /api/v1/feedback — awaiting the site storage */
	public function feedbackAction()
	{
		return $this->respond(fn () => $this->notImplemented());
	}

	/** PUT /api/v1/devices/{token} — awaiting the site push tokens storage */
	public function registerDeviceAction(string $token)
	{
		return $this->respond(function () {
			$this->userContext();

			return $this->notImplemented();
		});
	}

	/** DELETE /api/v1/devices/{token} — awaiting the site push tokens storage */
	public function unregisterDeviceAction(string $token)
	{
		return $this->respond(fn () => $this->notImplemented());
	}
}
