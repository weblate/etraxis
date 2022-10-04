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

use App\Entity\User;
use App\LoginTrait;
use App\Message\UserSettings\UpdateProfileCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\UserSettings\UpdateProfileCommandHandler::__invoke
 */
final class UpdateProfileCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private ?CommandBusInterface                     $commandBus;
    private ObjectRepository|UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('nhills@example.com');

        $user = $this->repository->findOneByEmail('nhills@example.com');

        self::assertSame('nhills@example.com', $user->getEmail());
        self::assertSame('Nikko Hills', $user->getFullname());

        $command = new UpdateProfileCommand('chaim.willms@example.com', 'Chaim Willms');

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->getEmail());
        self::assertSame('Chaim Willms', $user->getFullname());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('User must be logged in.');

        $command = new UpdateProfileCommand('chaim.willms@example.com', 'Chaim Willms');

        $this->commandBus->handle($command);
    }

    public function testExternalAccount(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Not applicable for external accounts.');

        $this->loginUser('einstein@ldap.forumsys.com');

        $command = new UpdateProfileCommand('chaim.willms@example.com', 'Chaim Willms');

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginUser('nhills@example.com');

        $command = new UpdateProfileCommand('vparker@example.com', 'Chaim Willms');

        $this->commandBus->handle($command);
    }
}
