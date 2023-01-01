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

namespace App\MessageHandler\Security;

use App\Entity\User;
use App\Message\Security\ForgotPasswordCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\ReflectionTrait;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;

/**
 * @internal
 *
 * @covers \App\MessageHandler\Security\ForgotPasswordCommandHandler::__invoke
 */
final class ForgotPasswordCommandHandlerTest extends TransactionalTestCase
{
    use ReflectionTrait;

    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess(): void
    {
        $command = new ForgotPasswordCommand('artem@example.com');

        $this->commandBus->handle($command);

        $user  = $this->repository->findOneByEmail('artem@example.com');
        $token = $this->getProperty($user, 'resetToken');

        self::assertMatchesRegularExpression('/^([[:xdigit:]]{32}$)/', $token);
        self::assertTrue($user->isResetTokenValid($token));
    }

    public function testExternal(): void
    {
        $user = $this->repository->findOneByEmail('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        $command = new ForgotPasswordCommand('einstein@ldap.forumsys.com');

        $this->commandBus->handle($command);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }

    public function testUnknown(): void
    {
        $user = $this->repository->findOneByEmail('404@example.com');
        self::assertNull($user);

        $command = new ForgotPasswordCommand('404@example.com');

        $this->commandBus->handle($command);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }
}
