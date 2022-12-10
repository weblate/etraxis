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

namespace App\MessageHandler\Security;

use App\Entity\Enums\SecondsEnum;
use App\Message\Security\GenerateJwtCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Serializer\JwtEncoder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Command handler.
 */
final class GenerateJwtCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EncoderInterface $encoder
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(GenerateJwtCommand $command): string
    {
        /** @var \App\Entity\User $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user) {
            throw new NotFoundHttpException('Invalid credentials.');
        }

        $payload = [
            'sub' => $user->getUserIdentifier(),
            'exp' => time() + SecondsEnum::TwoHours->value,
            'iat' => time(),
        ];

        return $this->encoder->encode($payload, JwtEncoder::FORMAT);
    }
}
