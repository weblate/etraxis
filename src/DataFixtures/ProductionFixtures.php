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

namespace App\DataFixtures;

use App\Entity\Enums\LocaleEnum;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures for first-time deployment to production.
 */
class ProductionFixtures extends Fixture implements FixtureInterface, FixtureGroupInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly UserPasswordHasherInterface $hasher, protected readonly string $locale)
    {
    }

    /**
     * {@inheritDoc}
     */
    public static function getGroups(): array
    {
        return ['prod'];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user
            ->setEmail('admin@example.com')
            ->setPassword($this->hasher->hashPassword($user, 'secret'))
            ->setFullname('eTraxis Admin')
            ->setDescription('Built-in administrator')
            ->setAdmin(true)
            ->setLocale(LocaleEnum::tryFrom($this->locale) ?? LocaleEnum::FALLBACK)
        ;

        $manager->persist($user);
        $manager->flush();
    }
}
