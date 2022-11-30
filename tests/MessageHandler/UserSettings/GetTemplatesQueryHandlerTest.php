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

namespace App\MessageHandler\UserSettings;

use App\Entity\Template;
use App\Entity\User;
use App\Message\UserSettings\GetTemplatesQuery;
use App\MessageBus\Contracts\QueryBusInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageHandler\UserSettings\GetTemplatesQueryHandler
 */
final class GetTemplatesQueryHandlerTest extends TransactionalTestCase
{
    private QueryBusInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBus = self::getContainer()->get(QueryBusInterface::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testDefault(): void
    {
        /** @var \App\Repository\Contracts\UserRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByEmail('ldoyle@example.com');

        /** @var Template $taskC */
        [/* skipping */ , /* skipping */ , $taskC] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);
        /** @var Template $reqC */
        /** @var Template $reqD */
        [/* skipping */ , /* skipping */ , $reqC, $reqD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $expected = [
            $taskC,
            $reqC,
            $reqD,
        ];

        $query = new GetTemplatesQuery($user->getId());

        $collection = $this->queryBus->execute($query);

        self::assertCount(3, $collection);
        self::assertSame($expected, $collection);
    }

    /**
     * @covers ::__invoke
     */
    public function testNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $query = new GetTemplatesQuery(self::UNKNOWN_ENTITY_ID);

        $this->queryBus->execute($query);
    }
}
