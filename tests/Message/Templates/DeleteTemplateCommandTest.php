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
 * @coversDefaultClass \App\Message\Templates\DeleteTemplateCommand
 */
final class DeleteTemplateCommandTest extends TestCase
{
    /**
     * @covers ::getTemplate
     */
    public function testConstructor(): void
    {
        $command = new DeleteTemplateCommand(1);

        self::assertSame(1, $command->getTemplate());
    }
}
