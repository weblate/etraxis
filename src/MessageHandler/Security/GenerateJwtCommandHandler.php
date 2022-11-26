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
use App\Repository\Contracts\UserRepositoryInterface;
use App\Serializer\JwtEncoder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        private readonly EncoderInterface $encoder,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(GenerateJwtCommand $command): string
    {
        $user = $this->repository->findOneByEmail($command->getEmail());

        if (!$user || $user->isDisabled() || $user->isAccountExternal() || !$this->hasher->isPasswordValid($user, $command->getPassword())) {
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
