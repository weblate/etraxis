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

use App\Entity\State;
use App\Security\Voter\StateVoter;
use App\Utils\OpenApi\StateExtended;
use App\Utils\OpenApiInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'State' entity normalizer.
 */
class StateEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly AuthorizationCheckerInterface $security)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        // Setting this to flag that the normalizer has been already called before.
        $context[self::class] = true;

        /** @var State $object */
        $json = $this->normalizer->normalize($object, $format, array_merge($context, [OpenApiInterface::ACTIONS => false]));

        if ($context[OpenApiInterface::ACTIONS] ?? false) {
            $json[OpenApiInterface::ACTIONS] = [
                StateExtended::ACTION_UPDATE      => $this->security->isGranted(StateVoter::UPDATE_STATE, $object),
                StateExtended::ACTION_DELETE      => $this->security->isGranted(StateVoter::DELETE_STATE, $object),
                StateExtended::ACTION_INITIAL     => $this->security->isGranted(StateVoter::SET_INITIAL_STATE, $object),
                StateExtended::ACTION_TRANSITIONS => $this->security->isGranted(StateVoter::SET_STATE_TRANSITIONS, $object),
                StateExtended::ACTION_GROUPS      => $this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $object),
            ];
        }

        return $json;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof State && !($context[self::class] ?? false);
    }
}
