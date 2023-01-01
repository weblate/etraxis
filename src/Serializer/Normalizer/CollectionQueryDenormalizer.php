<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Serializer\Normalizer;

use App\Message\AbstractCollectionQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizer of API request for items collection.
 */
class CollectionQueryDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Request $data
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): AbstractCollectionQuery
    {
        $offset = (int) $data->get('offset', 0);
        $limit  = (int) $data->get('limit', AbstractCollectionQuery::MAX_LIMIT);

        $search  = $data->get('search');
        $filters = json_decode($data->get('filters', ''), true) ?? [];
        $order   = json_decode($data->get('order', ''), true) ?? [];

        return new $type(
            $offset,
            $limit,
            $search,
            is_array($filters) ? $filters : [],
            is_array($order) ? $order : []
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_subclass_of($type, AbstractCollectionQuery::class) && $data instanceof Request;
    }
}
