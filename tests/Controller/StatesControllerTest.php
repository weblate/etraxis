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

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Group;
use App\Entity\State;
use App\Entity\Template;
use App\LoginTrait;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\StatesController
 */
final class StatesControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private StateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::listStates
     */
    public function testListStates200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/states');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listStates
     */
    public function testListStates401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/states');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listStates
     */
    public function testListStates403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/states');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createState
     */
    public function testCreateState201(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'template'    => $template->getId(),
            'name'        => 'Started',
            'type'        => StateTypeEnum::Intermediate,
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createState
     */
    public function testCreateState400(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createState
     */
    public function testCreateState401(): void
    {
        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'template'    => $template->getId(),
            'name'        => 'Started',
            'type'        => StateTypeEnum::Intermediate,
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createState
     */
    public function testCreateState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'template'    => $template->getId(),
            'name'        => 'Started',
            'type'        => StateTypeEnum::Intermediate,
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createState
     */
    public function testCreateState404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'template'    => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Started',
            'type'        => StateTypeEnum::Intermediate,
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createState
     */
    public function testCreateState409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $content = [
            'template'    => $template->getId(),
            'name'        => 'Completed',
            'type'        => StateTypeEnum::Intermediate,
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/states', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getState
     */
    public function testGetState200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getState
     */
    public function testGetState401(): void
    {
        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getState
     */
    public function testGetState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getState
     */
    public function testGetState404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Forwarded',
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', $state->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', $state->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState401(): void
    {
        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Forwarded',
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', $state->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Forwarded',
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', $state->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Forwarded',
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Completed',
            'responsible' => StateResponsibleEnum::Keep,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s', $state->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteState
     */
    public function testDeleteState200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteState
     */
    public function testDeleteState401(): void
    {
        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteState
     */
    public function testDeleteState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/states/%s', $state->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setInitialState
     */
    public function testSetInitialState200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/states/%s/initial', $state->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setInitialState
     */
    public function testSetInitialState401(): void
    {
        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/states/%s/initial', $state->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setInitialState
     */
    public function testSetInitialState403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/states/%s/initial', $state->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setInitialState
     */
    public function testSetInitialState404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/states/%s/initial', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTransitions
     */
    public function testGetTransitions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/transitions', $state->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTransitions
     */
    public function testGetTransitions401(): void
    {
        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/transitions', $state->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTransitions
     */
    public function testGetTransitions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/transitions', $state->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getTransitions
     */
    public function testGetTransitions404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/transitions', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setTransitions
     */
    public function testSetTransitions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'toState' => $toState->getId(),
            'roles'   => [
                SystemRoleEnum::Author,
            ],
            'groups'  => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/transitions', $state->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setTransitions
     */
    public function testSetTransitions400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $content = [
            'toState' => $toState->getId(),
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/transitions', $state->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setTransitions
     */
    public function testSetTransitions401(): void
    {
        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'toState' => $toState->getId(),
            'roles'   => [
                SystemRoleEnum::Author,
            ],
            'groups'  => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/transitions', $state->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setTransitions
     */
    public function testSetTransitions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'toState' => $toState->getId(),
            'roles'   => [
                SystemRoleEnum::Author,
            ],
            'groups'  => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/transitions', $state->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setTransitions
     */
    public function testSetTransitions404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'toState' => $toState->getId(),
            'roles'   => [
                SystemRoleEnum::Author,
            ],
            'groups'  => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/transitions', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getResponsibleGroups
     */
    public function testGetResponsibleGroups200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/responsibles', $state->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getResponsibleGroups
     */
    public function testGetResponsibleGroups401(): void
    {
        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/responsibles', $state->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getResponsibleGroups
     */
    public function testGetResponsibleGroups403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/responsibles', $state->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getResponsibleGroups
     */
    public function testGetResponsibleGroups404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/states/%s/responsibles', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setResponsibleGroups
     */
    public function testSetResponsibleGroups200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'groups' => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/responsibles', $state->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setResponsibleGroups
     */
    public function testSetResponsibleGroups400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/responsibles', $state->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setResponsibleGroups
     */
    public function testSetResponsibleGroups401(): void
    {
        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'groups' => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/responsibles', $state->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setResponsibleGroups
     */
    public function testSetResponsibleGroups403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'groups' => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/responsibles', $state->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setResponsibleGroups
     */
    public function testSetResponsibleGroups404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $developers */
        [/* skipping */ , $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'groups' => [
                $developers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/states/%s/responsibles', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
