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

use App\Entity\Group;
use App\Entity\User;
use App\LoginTrait;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\UsersController
 */
final class UsersControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::listUsers
     */
    public function testListUsers200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/users');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listUsers
     */
    public function testListUsers401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/users');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listUsers
     */
    public function testListUsers403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/users');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createUser
     */
    public function testCreateUser201(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createUser
     */
    public function testCreateUser400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createUser
     */
    public function testCreateUser401(): void
    {
        $content = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createUser
     */
    public function testCreateUser403(): void
    {
        $this->loginUser('artem@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createUser
     */
    public function testCreateUser409(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'email'       => 'artem@example.com',
            'password'    => 'secret',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableMultipleUsers
     */
    public function testDisableMultipleUsers200(): void
    {
        $this->loginUser('admin@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/disable', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableMultipleUsers
     */
    public function testDisableMultipleUsers400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/disable', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableMultipleUsers
     */
    public function testDisableMultipleUsers401(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/disable', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableMultipleUsers
     */
    public function testDisableMultipleUsers403(): void
    {
        $this->loginUser('artem@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/disable', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableMultipleUsers
     */
    public function testDisableMultipleUsers404(): void
    {
        $this->loginUser('admin@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/disable', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableMultipleUsers
     */
    public function testEnableMultipleUsers200(): void
    {
        $this->loginUser('admin@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/enable', $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableMultipleUsers
     */
    public function testEnableMultipleUsers400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/enable', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableMultipleUsers
     */
    public function testEnableMultipleUsers401(): void
    {
        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/enable', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableMultipleUsers
     */
    public function testEnableMultipleUsers403(): void
    {
        $this->loginUser('artem@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/enable', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableMultipleUsers
     */
    public function testEnableMultipleUsers404(): void
    {
        $this->loginUser('admin@example.com');

        $nhills = $this->repository->findOneByEmail('nhills@example.com');
        $tberge = $this->repository->findOneByEmail('tberge@example.com');

        $content = [
            'users' => [
                $nhills->getId(),
                $tberge->getId(),
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/users/enable', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::retrieveUser
     */
    public function testGetUser200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::retrieveUser
     */
    public function testGetUser401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::retrieveUser
     */
    public function testGetUser403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::retrieveUser
     */
    public function testGetUser404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', $user->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', $user->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', $user->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', $user->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'email'       => 'anna@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateUser
     */
    public function testUpdateUser409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'email'       => 'admin@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => 'Very lovely Daughter',
            'admin'       => true,
            'disabled'    => false,
            'locale'      => 'ru',
            'timezone'    => 'Pacific/Auckland',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s', $user->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteUser
     */
    public function testDeleteUser200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteUser
     */
    public function testDeleteUser401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteUser
     */
    public function testDeleteUser403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/users/%s', $user->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroups200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s/groups', $user->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroups401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s/groups', $user->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroups403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s/groups', $user->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getGroups
     */
    public function testGetGroups404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/users/%s/groups', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setGroups
     */
    public function testSetGroups200(): void
    {
        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);
        $devB = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers B']);
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $content = [
            'add'    => [
                $devB->getId(),
                $devC->getId(),
            ],
            'remove' => [
                $devA->getId(),
                $devC->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, sprintf('/api/users/%s/groups', $user->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setGroups
     */
    public function testSetGroups400(): void
    {
        $this->loginUser('admin@example.com');

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $content = [
            'add'    => [
                'Developers B',
                'Developers C',
            ],
            'remove' => [
                'Developers A',
                'Developers C',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, sprintf('/api/users/%s/groups', $user->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setGroups
     */
    public function testSetGroups401(): void
    {
        $user = $this->repository->findOneByEmail('labshire@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);
        $devB = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers B']);
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $content = [
            'add'    => [
                $devB->getId(),
                $devC->getId(),
            ],
            'remove' => [
                $devA->getId(),
                $devC->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, sprintf('/api/users/%s/groups', $user->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setGroups
     */
    public function testSetGroups403(): void
    {
        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('labshire@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);
        $devB = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers B']);
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $content = [
            'add'    => [
                $devB->getId(),
                $devC->getId(),
            ],
            'remove' => [
                $devA->getId(),
                $devC->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, sprintf('/api/users/%s/groups', $user->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setGroups
     */
    public function testSetGroups404(): void
    {
        $this->loginUser('admin@example.com');

        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);
        $devB = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers B']);
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $content = [
            'add'    => [
                $devB->getId(),
                $devC->getId(),
            ],
            'remove' => [
                $devA->getId(),
                $devC->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PATCH, sprintf('/api/users/%s/groups', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'password' => 'newone',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s/password', $user->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s/password', $user->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'password' => 'newone',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s/password', $user->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $content = [
            'password' => 'newone',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s/password', $user->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPassword
     */
    public function testSetPassword404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'password' => 'newone',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/users/%s/password', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableUser
     */
    public function testDisableUser200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/disable', $user->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableUser
     */
    public function testDisableUser401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/disable', $user->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableUser
     */
    public function testDisableUser403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/disable', $user->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::disableUser
     */
    public function testDisableUser404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/disable', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableUser
     */
    public function testEnableUser200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('tberge@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/enable', $user->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableUser
     */
    public function testEnableUser401(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneByEmail('tberge@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/enable', $user->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableUser
     */
    public function testEnableUser403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByEmail('tberge@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/enable', $user->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::enableUser
     */
    public function testEnableUser404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/users/%s/enable', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
