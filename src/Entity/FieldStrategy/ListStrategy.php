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

namespace App\Entity\FieldStrategy;

use App\Entity\Field;
use App\Entity\ListItem;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Utils\OpenApiInterface;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * List field strategy.
 */
final class ListStrategy extends AbstractFieldStrategy
{
    // Constraints.
    public const MIN_VALUE = 1;

    #[Groups('api')]
    #[API\Property(type: OpenApiInterface::TYPE_INTEGER, nullable: true, minimum: self::MIN_VALUE, description: 'Default value.')]
    public function getDefault(): ?int
    {
        return $this->getParameter(Field::DEFAULT);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(string $parameter): null|bool|int|string
    {
        if (Field::DEFAULT === $parameter) {
            return self::toInteger($this->field->getParameter($parameter), self::MIN_VALUE);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self
    {
        if (Field::DEFAULT === $parameter) {
            $this->field->setParameter($parameter, self::toInteger($value, self::MIN_VALUE));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array
    {
        /** @var ListItemRepositoryInterface $repository */
        $repository = $context['repository'] ?? null;

        if (!$repository instanceof ListItemRepositoryInterface) {
            throw new \LogicException('Context must contain the ListItem repository.');
        }

        $constraints = parent::getValueValidationConstraints($translator, $context);

        $constraints[] = new Assert\Regex([
            'pattern' => '/^\d+$/',
        ]);

        $constraints[] = new Assert\GreaterThan([
            'value' => 0,
        ]);

        $constraints[] = new Assert\Choice([
            'choices' => array_map(fn (ListItem $item) => $item->getValue(), $repository->findAllByField($this->field)),
        ]);

        return $constraints;
    }
}
