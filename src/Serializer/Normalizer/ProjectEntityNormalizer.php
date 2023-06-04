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

use App\Entity\Project;
use App\Security\Voter\ProjectVoter;
use App\Utils\OpenApi\ProjectExtended;
use App\Utils\OpenApiInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'Project' entity normalizer.
 */
class ProjectEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly AuthorizationCheckerInterface $security)
    {
    }

    /**
     * @see NormalizerInterface::normalize
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        // Setting this to flag that the normalizer has been already called before.
        $context[self::class] = true;

        /** @var Project $object */
        $json = $this->normalizer->normalize($object, $format, array_merge($context, [OpenApiInterface::ACTIONS => false]));

        if ($context[OpenApiInterface::ACTIONS] ?? false) {
            $json[OpenApiInterface::ACTIONS] = [
                ProjectExtended::ACTION_UPDATE  => $this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $object),
                ProjectExtended::ACTION_DELETE  => $this->security->isGranted(ProjectVoter::DELETE_PROJECT, $object),
                ProjectExtended::ACTION_SUSPEND => $this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $object),
                ProjectExtended::ACTION_RESUME  => $this->security->isGranted(ProjectVoter::RESUME_PROJECT, $object),
            ];
        }

        return $json;
    }

    /**
     * @see NormalizerInterface::supportsNormalization
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Project && !($context[self::class] ?? false);
    }
}
