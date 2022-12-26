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

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversDefaultClass \App\Command\FakeWebpackCommand
 */
final class FakeWebpackCommandTest extends WebTestCase
{
    /**
     * @covers ::execute
     */
    public function testFakeWebpack(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new FakeWebpackCommand());

        $commandTester = new CommandTester($application->find('etraxis:fake-webpack'));
        $commandTester->execute([]);

        self::assertSame('[OK] Done.', trim($commandTester->getDisplay()));
    }
}
