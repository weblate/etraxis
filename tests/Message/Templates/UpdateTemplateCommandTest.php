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
 * @coversDefaultClass \App\Message\Templates\UpdateTemplateCommand
 */
final class UpdateTemplateCommandTest extends TestCase
{
    /**
     * @covers ::getCriticalAge
     * @covers ::getDescription
     * @covers ::getFrozenTime
     * @covers ::getName
     * @covers ::getPrefix
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $command = new UpdateTemplateCommand(1, 'Bug report', 'bug', null, 7, 30);

        self::assertSame(1, $command->getTemplate());
        self::assertSame('Bug report', $command->getName());
        self::assertSame('bug', $command->getPrefix());
        self::assertNull($command->getDescription());
        self::assertSame(7, $command->getCriticalAge());
        self::assertSame(30, $command->getFrozenTime());
    }
}
