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

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Field;
use App\Entity\Group;
use App\Entity\ListItem;
use App\Entity\State;
use App\LoginTrait;
use App\Repository\Contracts\FieldRepositoryInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversDefaultClass \App\Controller\FieldsController
 */
final class FieldsControllerTest extends TransactionalTestCase
{
    use LoginTrait;

    private FieldRepositoryInterface $fieldRepository;
    private ListItemRepositoryInterface $itemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldRepository = $this->doctrine->getRepository(Field::class);
        $this->itemRepository  = $this->doctrine->getRepository(ListItem::class);
    }

    /**
     * @covers ::listFields
     */
    public function testListFields200(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/fields');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listFields
     */
    public function testListFields401(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/api/fields');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::listFields
     */
    public function testListFields403(): void
    {
        $this->loginUser('artem@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, '/api/fields');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField201NoParameters(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Task ID',
            'type'        => FieldTypeEnum::Issue,
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createField
     */
    public function testCreateField201WithParameters(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Phone number',
            'type'        => FieldTypeEnum::String,
            'description' => null,
            'required'    => true,
            'parameters'  => [
                'length'     => 14,
                'default'    => '(555) 123-4567',
                'pcre-check' => '\\(\\d{3}\\) \\d{3}-\\d{4}',
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createField
     */
    public function testCreateField400WrongType(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Task ID',
            'type'        => 'unknown',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField400EmptyContent(): void
    {
        $this->loginUser('admin@example.com');

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField401(): void
    {
        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Task ID',
            'type'        => FieldTypeEnum::Issue,
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Task ID',
            'type'        => FieldTypeEnum::Issue,
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'state'       => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Task ID',
            'type'        => FieldTypeEnum::Issue,
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createField
     */
    public function testCreateField409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var State $state */
        [/* skipping */ , $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $content = [
            'state'       => $state->getId(),
            'name'        => 'Issue ID',
            'type'        => FieldTypeEnum::Issue,
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, '/api/fields', $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getField
     */
    public function testGetField200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getField
     */
    public function testGetField401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getField
     */
    public function testGetField403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getField
     */
    public function testGetField404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', $field->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', $field->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', $field->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', $field->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateField
     */
    public function testUpdateField409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'name'        => 'Description',
            'description' => null,
            'required'    => true,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s', $field->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteField
     */
    public function testDeleteField200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteField
     */
    public function testDeleteField401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteField
     */
    public function testDeleteField403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s', $field->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setFieldPosition
     */
    public function testSetFieldPosition200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'position' => 2,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/position', $field->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setFieldPosition
     */
    public function testSetFieldPosition400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/position', $field->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setFieldPosition
     */
    public function testSetFieldPosition401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'position' => 2,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/position', $field->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setFieldPosition
     */
    public function testSetFieldPosition403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'position' => 2,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/position', $field->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setFieldPosition
     */
    public function testSetFieldPosition404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'position' => 2,
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/position', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/permissions', $field->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/permissions', $field->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/permissions', $field->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getPermissions
     */
    public function testGetPermissions404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/permissions', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [/* skipping */ , $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'permission' => FieldPermissionEnum::ReadOnly,
            'roles'      => [
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $managers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/permissions', $field->getId()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'permission' => FieldPermissionEnum::ReadOnly,
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/permissions', $field->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [/* skipping */ , $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'permission' => FieldPermissionEnum::ReadOnly,
            'roles'      => [
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $managers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/permissions', $field->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [/* skipping */ , $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'permission' => FieldPermissionEnum::ReadOnly,
            'roles'      => [
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $managers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/permissions', $field->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::setPermissions
     */
    public function testSetPermissions404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Group $managers */
        [/* skipping */ , $managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */ , $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $content = [
            'permission' => FieldPermissionEnum::ReadOnly,
            'roles'      => [
                SystemRoleEnum::Responsible,
            ],
            'groups'     => [
                $managers->getId(),
                $support->getId(),
            ],
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/permissions', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItems
     */
    public function testGetListItems200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems', $field->getId()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItems
     */
    public function testGetListItems204(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Description']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems', $field->getId()));

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItems
     */
    public function testGetListItems401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems', $field->getId()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItems
     */
    public function testGetListItems403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems', $field->getId()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItems
     */
    public function testGetListItems404(): void
    {
        $this->loginUser('admin@example.com');

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems', self::UNKNOWN_ENTITY_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem201(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', $field->getId()), $content);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', $field->getId()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', $field->getId()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', $field->getId()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem404(): void
    {
        $this->loginUser('admin@example.com');

        $content = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', self::UNKNOWN_ENTITY_ID), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::createListItem
     */
    public function testCreateListItem409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'value' => 4,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_POST, sprintf('/api/fields/%s/listitems', $field->getId()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItem
     */
    public function testGetListItem200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItem
     */
    public function testGetListItem401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItem
     */
    public function testGetListItem403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::getListItem
     */
    public function testGetListItem404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_GET, sprintf('/api/fields/%s/listitems/%s', $field->getId(), 4));

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $content = [
            'value' => 5,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()), $content);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem400(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $content = [];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()), $content);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $content = [
            'value' => 5,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()), $content);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $content = [
            'value' => 5,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()), $content);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem404(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $content = [
            'value' => 5,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), 4), $content);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::updateListItem
     */
    public function testUpdateListItem409(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $content = [
            'value' => 2,
            'text'  => 'low',
        ];

        $this->client->jsonRequest(Request::METHOD_PUT, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()), $content);

        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteListItem
     */
    public function testDeleteListItem200(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteListItem
     */
    public function testDeleteListItem401(): void
    {
        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @covers ::deleteListItem
     */
    public function testDeleteListItem403(): void
    {
        $this->loginUser('artem@example.com');

        /** @var Field $field */
        [/* skipping */ , $field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [/* skipping */ , $item] = $this->itemRepository->findBy(['value' => 3], ['id' => 'ASC']);

        $this->client->jsonRequest(Request::METHOD_DELETE, sprintf('/api/fields/%s/listitems/%s', $field->getId(), $item->getValue()));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
