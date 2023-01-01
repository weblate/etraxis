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

namespace App\Repository;

use App\Entity\Field;
use App\Entity\State;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\FieldRepository
 */
final class FieldRepositoryTest extends TransactionalTestCase
{
    private Contracts\FieldRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    /**
     * @covers ::findOneByName
     */
    public function testFindOneByName(): void
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New']);

        $field = $this->repository->findOneByName($state->getId(), 'Priority');

        self::assertInstanceOf(Field::class, $field);
        self::assertSame('Priority', $field->getName());

        $field = $this->repository->findOneByName($state->getId(), 'Unknown');

        self::assertNull($field);
    }
}
