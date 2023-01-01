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

namespace App\MessageBus;

use App\MessageBus\Contracts\QueryBusInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @internal
 *
 * @coversDefaultClass \App\MessageBus\QueryBus
 */
final class QueryBusTest extends TestCase
{
    private QueryBusInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $messageBus = new class() implements MessageBusInterface {
            public function dispatch($message, array $stamps = []): Envelope
            {
                $result = [
                    'firstName' => 'Anna',
                    'lastName'  => 'Rodygina',
                ];

                $envelope = new Envelope($message);

                return $envelope
                    ->with(new BusNameStamp('query.bus'))
                    ->with(new HandledStamp($result, 'test_handler'))
                ;
            }
        };

        $this->queryBus = new QueryBus($messageBus);
    }

    /**
     * @covers ::execute
     */
    public function testExecute(): void
    {
        $expected = [
            'firstName' => 'Anna',
            'lastName'  => 'Rodygina',
        ];

        $query = new \stdClass();

        $result = $this->queryBus->execute($query);

        self::assertSame($expected, $result);
    }
}
