<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Service\FavoriteService;
use Westpower\Mobile\Service\Pagination;

/**
 * Favorites of the logged-in user (TZ 4.12), stored in the site's "Favorit" HL block.
 */
final class Favorite extends Base
{
	/** GET /api/v1/favorites */
	public function favoritesAction()
	{
		return $this->respond(function () {
			[$page, $limit] = Pagination::parse($this->query('page'), $this->query('limit'));

			return (new FavoriteService())->list($this->userContext(), $page, $limit);
		});
	}

	/** PUT /api/v1/favorites/{productId} */
	public function addAction(int $productId)
	{
		return $this->respond(fn () => (new FavoriteService())->add($this->userContext(), $productId));
	}

	/** DELETE /api/v1/favorites/{productId} */
	public function deleteAction(int $productId)
	{
		return $this->respond(fn () => (new FavoriteService())->remove($this->userContext(), $productId));
	}
}
