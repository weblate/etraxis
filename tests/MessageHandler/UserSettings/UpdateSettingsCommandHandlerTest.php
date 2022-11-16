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

use App\Entity\Enums\LocaleEnum;
use App\Entity\Enums\ThemeEnum;
use App\Entity\User;
use App\LoginTrait;
use App\Message\UserSettings\UpdateSettingsCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\UserSettings\UpdateSettingsCommandHandler::__invoke
 */
final class UpdateSettingsCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface     $commandBus;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(User::class);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);
    }

    public function testSuccess(): void
    {
        $this->loginUser('artem@example.com');

        $user = $this->repository->findOneByEmail('artem@example.com');

        self::assertSame(LocaleEnum::FALLBACK, $user->getLocale());
        self::assertSame(ThemeEnum::FALLBACK, $user->getTheme());
        self::assertSame('UTC', $user->getTimezone());

        $command = new UpdateSettingsCommand(LocaleEnum::Russian, ThemeEnum::Emerald, 'Pacific/Auckland');

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame(LocaleEnum::Russian, $user->getLocale());
        self::assertSame(ThemeEnum::Emerald, $user->getTheme());
        self::assertSame('Pacific/Auckland', $user->getTimezone());
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('User must be logged in.');

        $command = new UpdateSettingsCommand(LocaleEnum::Russian, ThemeEnum::Emerald, 'Pacific/Auckland');

        $this->commandBus->handle($command);
    }
}
