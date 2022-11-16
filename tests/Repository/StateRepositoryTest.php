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

namespace App\Repository;

use App\Entity\State;
use App\Entity\Template;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\StateRepository
 */
final class StateRepositoryTest extends TransactionalTestCase
{
    private Contracts\StateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::findOneByName
     */
    public function testFindOneByName(): void
    {
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development']);

        $state = $this->repository->findOneByName($template->getId(), 'New');

        self::assertInstanceOf(State::class, $state);
        self::assertSame('New', $state->getName());

        $state = $this->repository->findOneByName($template->getId(), 'Unknown');

        self::assertNull($state);
    }
}
