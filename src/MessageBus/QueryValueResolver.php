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

namespace App\MessageBus;

use App\MessageBus\Contracts\QueryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts request into query.
 */
class QueryValueResolver implements ValueResolverInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly DenormalizerInterface $denormalizer)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (is_subclass_of($argument->getType(), QueryInterface::class)) {
            yield $this->denormalizer->denormalize($request, $argument->getType(), 'json', [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                ],
            ]);
        }
    }
}
