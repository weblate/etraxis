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

namespace App\Message\Templates;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Message\Templates\CloneTemplateCommand
 */
final class CloneTemplateCommandTest extends TestCase
{
    /**
     * @covers ::getCriticalAge
     * @covers ::getDescription
     * @covers ::getFrozenTime
     * @covers ::getName
     * @covers ::getPrefix
     * @covers ::getProject
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $command = new CloneTemplateCommand(1, 2, 'Bug report', 'bug', null, 7, 30);

        self::assertSame(1, $command->getTemplate());
        self::assertSame(2, $command->getProject());
        self::assertSame('Bug report', $command->getName());
        self::assertSame('bug', $command->getPrefix());
        self::assertNull($command->getDescription());
        self::assertSame(7, $command->getCriticalAge());
        self::assertSame(30, $command->getFrozenTime());
    }
}
