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

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test fixtures for 'User' entity.
 */
class UserFixtures extends Fixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            ProductionFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            'artem@example.com'              => [
                'fullname' => 'Artem Rodygin',
            ],
            'einstein@ldap.forumsys.com'     => [
                'provider' => AccountProviderEnum::LDAP,
                'uid'      => 'uid=einstein,dc=example,dc=com',
                'fullname' => 'Albert Einstein',
            ],
            'tberge@example.com'             => [
                'fullname'    => 'Ted Berge',
                'description' => 'Disabled account',
                'disabled'    => true,
            ],
            'lucas.oconnell@example.com'     => [
                'fullname'    => 'Lucas O\'Connell',
                'description' => 'Client A+B+C',
            ],
            'clegros@example.com'            => [
                'fullname'    => 'Carson Legros',
                'description' => 'Client A+B',
            ],
            'jmueller@example.com'           => [
                'fullname'    => 'Jeramy Mueller',
                'description' => 'Client A+C',
            ],
            'dtillman@example.com'           => [
                'fullname'    => 'Derrick Tillman',
                'description' => 'Client B+C',
            ],
            'hstroman@example.com'           => [
                'fullname'    => 'Hunter Stroman',
                'description' => 'Client A',
            ],
            'aschinner@example.com'          => [
                'fullname'    => 'Alyson Schinner',
                'description' => 'Client B',
            ],
            'dmurazik@example.com'           => [
                'fullname'    => 'Denis Murazik',
                'description' => 'Client C',
            ],
            'ldoyle@example.com'             => [
                'fullname'    => 'Leland Doyle',
                'description' => 'Manager A+B+C+D',
            ],
            'dorcas.ernser@example.com'      => [
                'fullname'    => 'Dorcas Ernser',
                'description' => 'Manager A+B',
            ],
            'berenice.oconnell@example.com'  => [
                'fullname'    => 'Berenice O\'Connell',
                'description' => 'Manager A+C',
            ],
            'carolyn.hill@example.com'       => [
                'fullname'    => 'Carolyn Hill',
                'description' => 'Manager B+C',
            ],
            'dangelo.hill@example.com'       => [
                'fullname'    => 'Dangelo Hill',
                'description' => 'Manager A',
            ],
            'emmanuelle.bartell@example.com' => [
                'fullname'    => 'Emmanuelle Bartell',
                'description' => 'Manager B',
            ],
            'jgoodwin@example.com'           => [
                'fullname'    => 'Juanita Goodwin',
                'description' => 'Manager C',
            ],
            'fdooley@example.com'            => [
                'fullname'    => 'Francesca Dooley',
                'description' => 'Developer A+B+C',
            ],
            'labshire@example.com'           => [
                'fullname'    => 'Lola Abshire',
                'description' => 'Developer A+B',
            ],
            'dquigley@example.com'           => [
                'fullname'    => 'Dennis Quigley',
                'description' => 'Developer A+C',
            ],
            'akoepp@example.com'             => [
                'fullname'    => 'Ansel Koepp',
                'description' => 'Developer B+C',
            ],
            'christy.mcdermott@example.com'  => [
                'fullname'    => 'Christy McDermott',
                'description' => 'Developer A',
            ],
            'amarvin@example.com'            => [
                'fullname'    => 'Anissa Marvin',
                'description' => 'Developer B',
            ],
            'mbogisich@example.com'          => [
                'fullname'    => 'Millie Bogisich',
                'description' => 'Developer C',
            ],
            'tmarquardt@example.com'         => [
                'fullname'    => 'Tracy Marquardt',
                'description' => 'Support Engineer A+B+C',
            ],
            'bkemmer@example.com'            => [
                'fullname'    => 'Bell Kemmer',
                'description' => 'Support Engineer A+B',
            ],
            'cbatz@example.com'              => [
                'fullname'    => 'Carter Batz',
                'description' => 'Support Engineer A+C',
            ],
            'kbahringer@example.com'         => [
                'fullname'    => 'Kailyn Bahringer',
                'description' => 'Support Engineer B+C',
            ],
            'kschultz@example.com'           => [
                'fullname'    => 'Kyla Schultz',
                'description' => 'Support Engineer A',
            ],
            'vparker@example.com'            => [
                'fullname'    => 'Vida Parker',
                'description' => 'Support Engineer B',
            ],
            'tbuckridge@example.com'         => [
                'fullname'    => 'Tony Buckridge',
                'description' => 'Support Engineer C',
            ],
            'nhills@example.com'             => [
                'fullname'    => 'Nikko Hills',
                'description' => 'Support Engineer A+B, Developer C',
            ],
            'jkiehn@example.com'             => [
                'fullname'    => 'Jarrell Kiehn',
                'description' => 'Support Engineer A, Developer B, Manager C',
            ],
        ];

        $password = $this->hasher->hashPassword(new User(), 'secret');

        foreach ($data as $email => $row) {
            $user = new User();

            $user
                ->setEmail($email)
                ->setPassword($password)
                ->setFullname($row['fullname'])
                ->setDescription($row['description'] ?? null)
                ->setAdmin(false)
                ->setDisabled($row['disabled'] ?? false)
            ;

            if ($row['provider'] ?? false) {
                $user
                    ->setPassword(null)
                    ->setAccountProvider($row['provider'])
                    ->setAccountUid($row['uid'])
                ;
            }

            $this->addReference('user:'.$email, $user);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
