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

namespace App\Entity;

use App\Entity\Enums\StateTypeEnum;
use App\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Entity\LastRead
 */
final class LastReadTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);

        $lastRead = new LastRead($issue, $user);

        self::assertSame($issue, $lastRead->getIssue());
        self::assertSame($user, $lastRead->getUser());

        $timestamp = $this->getProperty($lastRead, 'readAt');
        self::assertLessThanOrEqual(2, time() - $timestamp);
    }

    /**
     * @covers ::getIssue
     */
    public function testIssue(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);

        $lastRead = new LastRead($issue, $user);
        self::assertSame($issue, $lastRead->getIssue());
    }

    /**
     * @covers ::getUser
     */
    public function testUser(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);

        $lastRead = new LastRead($issue, $user);
        self::assertSame($user, $lastRead->getUser());
    }

    /**
     * @covers ::getReadAt
     * @covers ::touch
     */
    public function testReadAt(): void
    {
        $template = new Template(new Project());
        $state    = new State($template, StateTypeEnum::Initial);
        $user     = new User();

        /** @var \Doctrine\Common\Collections\Collection $states */
        $states = $this->getProperty($template, 'states');
        $states->add($state);

        $issue = new Issue($template, $user);

        $lastRead = new LastRead($issue, $user);

        $this->setProperty($lastRead, 'readAt', 0);
        self::assertGreaterThan(2, time() - $lastRead->getReadAt());

        $lastRead->touch();
        self::assertLessThanOrEqual(2, time() - $lastRead->getReadAt());
    }
}
