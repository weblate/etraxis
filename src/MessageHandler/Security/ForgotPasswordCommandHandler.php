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

use App\Message\Security\ForgotPasswordCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
final class ForgotPasswordCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     */
    public function __invoke(ForgotPasswordCommand $command): void
    {
        $user = $this->repository->findOneByEmail($command->getEmail());

        if (!$user || $user->isAccountExternal()) {
            return;
        }

        // Token expires in 2 hours.
        $token = $user->generateResetToken(new \DateInterval('PT2H'));

        $message = new TemplatedEmail();
        $subject = $this->translator->trans('email.forgot_password.subject', domain: 'passwords', locale: $user->getLocale()->value);

        $message
            ->to($user->getEmailAddress())
            ->subject($subject)
            ->htmlTemplate('security/forgot-password.html.twig')
            ->context([
                'locale' => $user->getLocale()->value,
                'token'  => $token,
            ])
        ;

        $this->mailer->send($message);

        $this->repository->persist($user);
    }
}
