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

use App\Message\UserSettings\SetPasswordCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\UserVoter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SetPasswordCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TransportExceptionInterface
     */
    public function __invoke(SetPasswordCommand $command): void
    {
        /** @var null|\App\Entity\User $user */
        $user = $this->repository->find($command->getUser());

        if (!$user) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(UserVoter::SET_PASSWORD, $user)) {
            throw new AccessDeniedHttpException('You are not allowed to set new password.');
        }

        /** @var null|\App\Entity\User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();

        try {
            $user->setPassword($this->hasher->hashPassword($user, $command->getPassword()));

            $message = new TemplatedEmail();

            $message
                ->from($currentUser->getEmailAddress())
                ->to($user->getEmailAddress())
                ->subject('Your password has been changed')
                ->htmlTemplate('security/password-changed.html.twig')
                ->context([
                    'password' => $command->getPassword(),
                ])
            ;

            $this->mailer->send($message);
        } catch (InvalidPasswordException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        $this->repository->persist($user);
    }
}
