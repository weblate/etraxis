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

namespace App\MessageHandler\Users;

use App\Entity\User;
use App\Message\Users\CreateUserCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Security\Voter\UserVoter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
final class CreateUserCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws TransportExceptionInterface
     */
    public function __invoke(CreateUserCommand $command): User
    {
        if (!$this->security->isGranted(UserVoter::CREATE_USER)) {
            throw new AccessDeniedHttpException('You are not allowed to create new user.');
        }

        $user = new User();

        $user
            ->setEmail($command->getEmail())
            ->setFullname($command->getFullname())
            ->setDescription($command->getDescription())
            ->setAdmin($command->isAdmin())
            ->setDisabled($command->isDisabled())
            ->setLocale($command->getLocale())
            ->setTimezone($command->getTimezone())
        ;

        try {
            $user->setPassword($this->hasher->hashPassword($user, $command->getPassword()));
        } catch (InvalidPasswordException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        /** @var User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();

        $message = new TemplatedEmail();
        $subject = $this->translator->trans('email.account_created.subject', domain: 'security', locale: $user->getLocale()->value);

        $message
            ->from($currentUser->getEmailAddress())
            ->to($user->getEmailAddress())
            ->subject($subject)
            ->htmlTemplate('security/account-created.html.twig')
            ->context([
                'locale'   => $user->getLocale()->value,
                'password' => $command->getPassword(),
            ])
        ;

        $this->mailer->send($message);

        $this->repository->persist($user);

        return $user;
    }
}
