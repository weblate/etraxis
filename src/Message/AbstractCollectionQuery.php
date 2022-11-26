<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Message;

use App\MessageBus\Contracts\QueryInterface;

/**
 * Abstract query for a collection of items.
 */
abstract class AbstractCollectionQuery implements QueryInterface
{
    // Restrictions.
    public const MAX_LIMIT = 100;

    // Sorting directions.
    public const SORT_ASC  = 'ASC';
    public const SORT_DESC = 'DESC';

    /**
     * Initializes the query.
     */
    public function __construct(
        protected int $offset,
        protected int $limit,
        protected ?string $search = null,
        protected array $filters = [],
        protected array $order = []
    ) {
        // Sanitize offset and limit.
        $this->offset = max(0, $this->offset);
        $this->limit  = max(1, min($this->limit, self::MAX_LIMIT));
    }

    /**
     * @return int Zero-based index of the first item to return
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int Maximum number of items to return
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return null|string Optional search value
     */
    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * @return array Array of property filters (keys are property names, values are filtering values)
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array Sorting specification (keys are property names, values are "asc" or "desc")
     */
    public function getOrder(): array
    {
        return $this->order;
    }
}
