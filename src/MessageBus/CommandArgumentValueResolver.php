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

use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Converts request into command.
 */
class CommandArgumentValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly SerializerInterface $serializer)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return is_subclass_of($argument->getType(), CommandInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->serializer->deserialize($request->getContent() ?: '{}', $argument->getType(), 'json', [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
            ],
        ]);
    }
}
