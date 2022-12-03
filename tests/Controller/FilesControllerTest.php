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

namespace App\Controller;

use App\Entity\File;
use App\Entity\Issue;
use App\LoginTrait;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\FilesController
 */
final class FilesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private IssueRepositoryInterface $repository;
    private UploadedFile $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);

        $path = getcwd().'/var/_test.txt';
        file_put_contents($path, 'Lorem ipsum');
        $this->file = new UploadedFile($path, 'test.txt', 'text/plain', null, true);
    }

    protected function tearDown(): void
    {
        $path = getcwd().'/var/_test.txt';

        if (file_exists($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    /**
     * @covers ::listFiles
     */
    public function testListFiles200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files', $issue->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listFiles
     */
    public function testListFiles401(): void
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files', $issue->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listFiles
     */
    public function testListFiles403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files', $issue->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listFiles
     */
    public function testListFiles404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::attachFile
     */
    public function testAttachFile200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $files = [
            'file' => $this->file,
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/api/issues/%s/files', $issue->getId()), [], $files, ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::attachFile
     */
    public function testAttachFile400(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $files = [];

        $this->client->request(Request::METHOD_POST, sprintf('/api/issues/%s/files', $issue->getId()), [], $files, ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::attachFile
     */
    public function testAttachFile401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $files = [
            'file' => $this->file,
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/api/issues/%s/files', $issue->getId()), [], $files, ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::attachFile
     */
    public function testAttachFile403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $files = [
            'file' => $this->file,
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/api/issues/%s/files', $issue->getId()), [], $files, ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::attachFile
     */
    public function testAttachFile404(): void
    {
        $this->loginUser('ldoyle@example.com');

        $files = [
            'file' => $this->file,
        ];

        $this->client->request(Request::METHOD_POST, sprintf('/api/issues/%s/files', self::UNKNOWN_ENTITY_ID), [], $files, ['CONTENT_TYPE' => 'application/json']);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Nesciunt nulla sint amet.xslx'], ['id' => 'ASC']);

        $path = getcwd().'/var/'.$file->getUid();
        file_put_contents($path, 'Lorem ipsum');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        unlink($path);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame('text/plain; charset=UTF-8', $this->client->getResponse()->headers->get('CONTENT_TYPE'));
        self::assertSame('inline; filename="Nesciunt nulla sint amet.xslx"', $this->client->getResponse()->headers->get('CONTENT_DISPOSITION'));
        self::assertSame(11, (int) $this->client->getResponse()->headers->get('CONTENT_LENGTH'));
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Nesciunt nulla sint amet.xslx'], ['id' => 'ASC']);

        $path = getcwd().'/var/'.$file->getUid();
        file_put_contents($path, 'Lorem ipsum');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        unlink($path);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Nesciunt nulla sint amet.xslx'], ['id' => 'ASC']);

        $path = getcwd().'/var/'.$file->getUid();
        file_put_contents($path, 'Lorem ipsum');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        unlink($path);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile404WrongIssue(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $path = getcwd().'/var/'.$file->getUid();
        file_put_contents($path, 'Lorem ipsum');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        unlink($path);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile404Removed(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $path = getcwd().'/var/'.$file->getUid();
        file_put_contents($path, 'Lorem ipsum');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        unlink($path);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::downloadFile
     */
    public function testDownloadFile404Missing(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Nesciunt nulla sint amet.xslx'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteFile
     */
    public function testDeleteFile200(): void
    {
        $this->loginUser('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteFile
     */
    public function testDeleteFile401(): void
    {
        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteFile
     */
    public function testDeleteFile403(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Issue $issue */
        [/* skipping */ , /* skipping */ , $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var File $file */
        [/* skipping */ , /* skipping */ , $file] = $this->doctrine->getRepository(File::class)->findBy(['fileName' => 'Inventore.pdf'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/issues/%s/files/%s', $issue->getId(), $file->getUid()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
