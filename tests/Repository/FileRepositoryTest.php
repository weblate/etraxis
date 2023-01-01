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

use App\Entity\File;
use App\Entity\Issue;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Repository\FileRepository
 */
final class FileRepositoryTest extends TransactionalTestCase
{
    private Contracts\FileRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(File::class);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssue(): void
    {
        $expected = [
            'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
            'Nesciunt nulla sint amet.xslx',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $files  = $this->repository->findAllByIssue($issue);
        $actual = array_map(fn (File $file) => $file->getFileName(), $files);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::findOneByUid
     */
    public function testFindOneByUid(): void
    {
        /** @var File $expected */
        [$expected] = $this->repository->findBy(['fileName' => 'Nesciunt nulla sint amet.xslx'], ['id' => 'ASC']);

        $file = $this->repository->findOneByUid($expected->getUid());

        self::assertSame($expected, $file);
    }

    /**
     * @covers ::findOneByUid
     */
    public function testFindOneByUidRemoved(): void
    {
        /** @var File $removed */
        [$removed] = $this->repository->findBy(['fileName' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        self::assertInstanceOf(File::class, $removed);

        $file = $this->repository->findOneByUid($removed->getUid());

        self::assertNull($file);
    }

    /**
     * @covers ::getFullPath
     */
    public function testGetFullPath(): void
    {
        /** @var File $file */
        [$file] = $this->repository->findAll();

        $expected = sprintf('%s%svar%s%s', getcwd(), \DIRECTORY_SEPARATOR, \DIRECTORY_SEPARATOR, $file->getUid());

        self::assertSame($expected, $this->repository->getFullPath($file));
    }
}
