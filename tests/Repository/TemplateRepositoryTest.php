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

use App\Entity\Project;
use App\Entity\Template;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\TemplateRepository
 */
final class TemplateRepositoryTest extends TransactionalTestCase
{
    private ObjectRepository|Contracts\TemplateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    /**
     * @covers ::findOneByName
     */
    public function testFindOneByName(): void
    {
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $template = $this->repository->findOneByName($project->getId(), 'Development');

        self::assertInstanceOf(Template::class, $template);
        self::assertSame('Development', $template->getName());

        $template = $this->repository->findOneByName($project->getId(), 'Unknown');

        self::assertNull($template);
    }
}
