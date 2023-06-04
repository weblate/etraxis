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

use App\Entity\Template;
use App\Security\Voter\TemplateVoter;
use App\Utils\OpenApi\TemplateExtended;
use App\Utils\OpenApiInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'Template' entity normalizer.
 */
class TemplateEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
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

        /** @var Template $object */
        $json = $this->normalizer->normalize($object, $format, array_merge($context, [OpenApiInterface::ACTIONS => false]));

        if ($context[OpenApiInterface::ACTIONS] ?? false) {
            $json[OpenApiInterface::ACTIONS] = [
                TemplateExtended::ACTION_UPDATE => $this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $object),
                TemplateExtended::ACTION_DELETE => $this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $object),
                TemplateExtended::ACTION_LOCK   => $this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $object),
                TemplateExtended::ACTION_UNLOCK => $this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $object),
            ];
        }

        return $json;
    }

    /**
     * @see NormalizerInterface::supportsNormalization
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Template && !($context[self::class] ?? false);
    }
}
