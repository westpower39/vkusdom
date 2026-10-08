<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Service\CheckoutService;
use Westpower\Mobile\Service\OrderService;
use Westpower\Mobile\Service\Pagination;

/**
 * Checkout and orders (TZ 4.3–4.7).
 */
final class Checkout extends Base
{
	/** GET /api/v1/checkout/options */
	public function optionsAction()
	{
		return $this->respond(fn () => (new CheckoutService())->options($this->context()));
	}

	/** GET /api/v1/checkout/slots?method= */
	public function slotsAction()
	{
		return $this->respond(fn () => (new CheckoutService())->slots((string)$this->query('method')));
	}

	/** POST /api/v1/checkout/calculation */
	public function calculationAction()
	{
		return $this->respond(fn () => (new CheckoutService())->calculation($this->sessionContext(), $this->body()));
	}

	/** POST /api/v1/orders */
	public function createOrderAction()
	{
		return $this->respond(fn () => (new CheckoutService())->create($this->userContext(), $this->body()));
	}

	/** GET /api/v1/orders */
	public function ordersAction()
	{
		return $this->respond(function () {
			[$page, $limit] = Pagination::parse($this->query('page'), $this->query('limit'));

			return (new OrderService())->list($this->userContext(), $page, $limit);
		});
	}

	/** GET /api/v1/orders/{id} */
	public function orderAction(int $id)
	{
		return $this->respond(fn () => (new OrderService())->get($this->userContext(), $id));
	}

	/** POST /api/v1/orders/{id}/repeat */
	public function repeatOrderAction(int $id)
	{
		return $this->respond(fn () => (new OrderService())->repeat($this->userContext(), $id));
	}

	/** POST /api/v1/orders/{id}/payment */
	public function orderPaymentAction(int $id)
	{
		return $this->respond(fn () => (new OrderService())->payment($this->userContext(), $id));
	}
}
