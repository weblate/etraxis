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

namespace App\Entity\FieldStrategy;

use App\Entity\Field;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generic field interface.
 */
interface FieldStrategyInterface
{
    /**
     * Returns context field.
     */
    public function getContext(): Field;

    /**
     * Returns specified parameter.
     * If parameter is not applicable, returns NULL.
     */
    public function getParameter(string $parameter): null|bool|int|string;

    /**
     * Sets specified parameter.
     * If parameter is not applicable, the call is ignored.
     */
    public function setParameter(string $parameter, null|bool|int|string $value): self;

    /**
     * Returns list of constraints for field parameters validation.
     */
    public function getParametersValidationConstraints(TranslatorInterface $translator): array;

    /**
     * Returns list of constraints for field value validation.
     */
    public function getValueValidationConstraints(TranslatorInterface $translator, array $context = []): array;
}
