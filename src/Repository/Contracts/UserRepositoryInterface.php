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

namespace App\Repository\Contracts;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Interface to the 'User' entities repository.
 */
interface UserRepositoryInterface extends ObjectRepository, Selectable, PasswordUpgraderInterface
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist
     */
    public function persist(User $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove
     */
    public function remove(User $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh
     */
    public function refresh(User $entity): void;

    /**
     * Finds an account by email.
     */
    public function findOneByEmail(string $email): ?User;

    /**
     * Finds a user by password reset token.
     */
    public function findOneByResetToken(string $token): ?User;

    /**
     * Finds user by account provider and its UID.
     */
    public function findOneByProviderUid(AccountProviderEnum $provider, string $uid): ?User;
}
