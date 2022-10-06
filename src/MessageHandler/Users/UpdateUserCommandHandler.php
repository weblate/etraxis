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

namespace App\MessageHandler\Users;

use App\Message\Users\UpdateUserCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\UserVoter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
final class UpdateUserCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     * @throws TransportExceptionInterface
     */
    public function __invoke(UpdateUserCommand $command): void
    {
        /** @var null|\App\Entity\User $user */
        $user = $this->repository->find($command->getUser());

        if (!$user) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(UserVoter::UPDATE_USER, $user)) {
            throw new AccessDeniedHttpException('You are not allowed to update this user.');
        }

        $oldEmail = $user->getEmail();

        $user
            ->setEmail($command->getEmail())
            ->setFullname($command->getFullname())
            ->setDescription($command->getDescription())
            ->setLocale($command->getLocale())
            ->setTimezone($command->getTimezone())
        ;

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();

        // Don't disable yourself.
        if ($user->getId() !== $currentUser->getId()) {
            $user->setAdmin($command->isAdmin());
            $user->setDisabled($command->isDisabled());
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as usernames, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        // Warn the user about email change, using their previous email address.
        if ($user->getEmail() !== $oldEmail && !$user->isDisabled() && $user !== $currentUser) {
            $message = new TemplatedEmail();
            $subject = $this->translator->trans('email.login_changed.subject', locale: $user->getLocale()->value);

            $message
                ->from($currentUser->getEmailAddress())
                ->to(new Address($oldEmail, $user->getFullname()))
                ->subject($subject)
                ->htmlTemplate('security/login-changed.html.twig')
                ->context([
                    'locale'   => $user->getLocale()->value,
                    'newEmail' => $user->getEmail(),
                ])
            ;

            $this->mailer->send($message);
        }

        $this->repository->persist($user);
    }
}
