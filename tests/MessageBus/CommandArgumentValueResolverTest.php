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

namespace App\MessageBus;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Message\Templates\SetRolesPermissionCommand;
use App\Message\Users\GetUsersQuery;
use App\Message\UserSettings\UpdateProfileCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\CommandArgumentValueResolver
 */
final class CommandArgumentValueResolverTest extends WebTestCase
{
    private CommandArgumentValueResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $serializer = self::getContainer()->get('serializer');

        $this->resolver = new CommandArgumentValueResolver($serializer);
    }

    /**
     * @covers ::supports
     */
    public function testSupports(): void
    {
        $request = new Request();

        self::assertTrue($this->resolver->supports($request, new ArgumentMetadata('command', UpdateProfileCommand::class, false, false, null)));
        self::assertFalse($this->resolver->supports($request, new ArgumentMetadata('command', GetUsersQuery::class, false, false, null)));
    }

    /**
     * @covers ::resolve
     */
    public function testResolve(): void
    {
        $request = new Request(content: json_encode([
            'email'    => 'artem@example.com',
            'fullname' => 'Artem Rodygin',
        ]));

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('command', UpdateProfileCommand::class, false, false, null));
        $command   = $generator->current();

        self::assertInstanceOf(UpdateProfileCommand::class, $command);
        self::assertSame('artem@example.com', $command->getEmail());
        self::assertSame('Artem Rodygin', $command->getFullname());
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithRoles(): void
    {
        $expected = [
            SystemRoleEnum::Author,
            SystemRoleEnum::Responsible,
        ];

        $request = new Request(content: json_encode([
            'template'   => 1,
            'permission' => TemplatePermissionEnum::PrivateComments,
            'roles'      => ['author', 'responsible'],
        ]));

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('command', SetRolesPermissionCommand::class, false, false, null));
        $command   = $generator->current();

        self::assertInstanceOf(SetRolesPermissionCommand::class, $command);
        self::assertSame($expected, $command->getRoles());
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotEncodableValueException(): void
    {
        $this->expectException(NotEncodableValueException::class);

        $request = new Request(content: 'test');

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('command', UpdateProfileCommand::class, false, false, null));
        $generator->current();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveMissingConstructorArgumentsException(): void
    {
        $this->expectException(MissingConstructorArgumentsException::class);

        $request = new Request(content: json_encode([
            'fullname' => 'Artem Rodygin',
        ]));

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('command', UpdateProfileCommand::class, false, false, null));
        $generator->current();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotNormalizableValueException(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $request = new Request(content: json_encode([
            'email'    => false,
            'fullname' => 'Artem Rodygin',
        ]));

        /** @var \Generator $generator */
        $generator = $this->resolver->resolve($request, new ArgumentMetadata('command', UpdateProfileCommand::class, false, false, null));
        $generator->current();
    }
}
