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

namespace App\MessageHandler\States;

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\State;
use App\Entity\Template;
use App\LoginTrait;
use App\Message\States\CreateStateCommand;
use App\MessageBus\Contracts\CommandBusInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \App\MessageHandler\States\CreateStateCommandHandler::__invoke
 */
final class CreateStateCommandHandlerTest extends TransactionalTestCase
{
    use LoginTrait;

    private CommandBusInterface      $commandBus;
    private StateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Started']);
        self::assertNull($state);

        $command = new CreateStateCommand(
            $template->getId(),
            'Started',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        $result = $this->commandBus->handleWithResult($command);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Started']);
        self::assertInstanceOf(State::class, $state);
        self::assertSame($state, $result);

        self::assertSame($template, $state->getTemplate());
        self::assertSame('Started', $state->getName());
        self::assertSame(StateTypeEnum::Intermediate, $state->getType());
        self::assertSame(StateResponsibleEnum::Keep, $state->getResponsible());
    }

    public function testInitial(): void
    {
        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $initial */
        [/* skipping */ , $initial] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);
        self::assertSame(StateTypeEnum::Initial, $initial->getType());

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        self::assertNull($state);

        $command = new CreateStateCommand(
            $template->getId(),
            'Created',
            StateTypeEnum::Initial,
            StateResponsibleEnum::Keep
        );

        $result = $this->commandBus->handleWithResult($command);

        /** @var State $state */
        $state = $this->repository->findOneBy(['name' => 'Created']);
        self::assertInstanceOf(State::class, $state);
        self::assertSame($state, $result);

        self::assertSame($template, $state->getTemplate());
        self::assertSame('Created', $state->getName());
        self::assertSame(StateTypeEnum::Initial, $state->getType());
        self::assertSame(StateResponsibleEnum::Keep, $state->getResponsible());

        $this->doctrine->getManager()->refresh($initial);

        self::assertSame(StateTypeEnum::Intermediate, $initial->getType());
    }

    public function testValidationEmptyName(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand(
            $template->getId(),
            '',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value should not be blank.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testValidationNameLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand(
            $template->getId(),
            str_pad('', State::MAX_NAME + 1),
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        try {
            $this->commandBus->handle($command);
        } catch (ValidationFailedException $exception) {
            self::assertSame('This value is too long. It should have 50 characters or less.', $exception->getViolations()->get(0)->getMessage());

            throw $exception;
        }
    }

    public function testUnknownTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown template.');

        $this->loginUser('admin@example.com');

        $command = new CreateStateCommand(
            self::UNKNOWN_ENTITY_ID,
            'Started',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new state.');

        $this->loginUser('artem@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand(
            $template->getId(),
            'Started',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to create new state.');

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand(
            $template->getId(),
            'Started',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }

    public function testNameConflict(): void
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('State with specified name already exists.');

        $this->loginUser('admin@example.com');

        /** @var Template $template */
        [/* skipping */ , $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new CreateStateCommand(
            $template->getId(),
            'Completed',
            StateTypeEnum::Intermediate,
            StateResponsibleEnum::Keep
        );

        $this->commandBus->handle($command);
    }
}
