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

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversDefaultClass \App\Command\ExportEnumsCommand
 */
final class ExportEnumsCommandTest extends WebTestCase
{
    /**
     * @covers ::execute
     */
    public function testExportConstants(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new ExportEnumsCommand());

        $commandTester = new CommandTester($application->find('etraxis:export-enums'));
        $commandTester->execute([]);

        self::assertSame('[OK] Successfully exported.', trim($commandTester->getDisplay()));
    }
}
