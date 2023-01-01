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

namespace App\Doctrine;

use App\ReflectionTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Doctrine\AbstractExtendedMigration
 */
final class AbstractExtendedMigrationTest extends WebTestCase
{
    use ReflectionTrait;

    /**
     * @covers ::getDescription
     */
    public function testGetDescription(): void
    {
        $migration = $this->createMock(AbstractExtendedMigration::class);

        $migration
            ->method('getVersion')
            ->willReturn('4.0')
        ;

        self::assertSame('eTraxis 4.0', $migration->getDescription());
    }

    /**
     * @covers ::down
     * @covers ::isMariaDB
     * @covers ::up
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testMariaDB(): void
    {
        $schema    = $this->createMock(Schema::class);
        $platform  = new MariaDBPlatform();
        $migration = $this->createMigration($platform);

        self::assertTrue($this->callMethod($migration, 'isMariaDB'));
        self::assertFalse($this->callMethod($migration, 'isMySQL'));
        self::assertFalse($this->callMethod($migration, 'isPostgreSQL'));

        self::assertEmpty($migration->log);

        $migration->up($schema);
        self::assertSame('upMariaDB', $migration->log);

        $migration->down($schema);
        self::assertSame('downMariaDB', $migration->log);
    }

    /**
     * @covers ::down
     * @covers ::isMySQL
     * @covers ::up
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testMySQL(): void
    {
        $schema    = $this->createMock(Schema::class);
        $platform  = new MySQLPlatform();
        $migration = $this->createMigration($platform);

        self::assertFalse($this->callMethod($migration, 'isMariaDB'));
        self::assertTrue($this->callMethod($migration, 'isMySQL'));
        self::assertFalse($this->callMethod($migration, 'isPostgreSQL'));

        self::assertEmpty($migration->log);

        $migration->up($schema);
        self::assertSame('upMySQL', $migration->log);

        $migration->down($schema);
        self::assertSame('downMySQL', $migration->log);
    }

    /**
     * @covers ::down
     * @covers ::isPostgreSQL
     * @covers ::up
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testPostgreSQL(): void
    {
        $schema    = $this->createMock(Schema::class);
        $platform  = new PostgreSQLPlatform();
        $migration = $this->createMigration($platform);

        self::assertFalse($this->callMethod($migration, 'isMariaDB'));
        self::assertFalse($this->callMethod($migration, 'isMySQL'));
        self::assertTrue($this->callMethod($migration, 'isPostgreSQL'));

        self::assertEmpty($migration->log);

        $migration->up($schema);
        self::assertSame('upPostgreSQL', $migration->log);

        $migration->down($schema);
        self::assertSame('downPostgreSQL', $migration->log);
    }

    /**
     * @covers ::up
     */
    public function testUnsupportedPlatformUpgrade(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Not supported - Doctrine\DBAL\Platforms\SqlitePlatform');

        $schema    = $this->createMock(Schema::class);
        $platform  = new SqlitePlatform();
        $migration = $this->createMigration($platform);

        self::assertFalse($this->callMethod($migration, 'isMariaDB'));
        self::assertFalse($this->callMethod($migration, 'isMySQL'));
        self::assertFalse($this->callMethod($migration, 'isPostgreSQL'));

        $migration->up($schema);
    }

    /**
     * @covers ::down
     */
    public function testUnsupportedPlatformDowngrade(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Not supported - Doctrine\DBAL\Platforms\SqlitePlatform');

        $schema    = $this->createMock(Schema::class);
        $platform  = new SqlitePlatform();
        $migration = $this->createMigration($platform);

        self::assertFalse($this->callMethod($migration, 'isMariaDB'));
        self::assertFalse($this->callMethod($migration, 'isMySQL'));
        self::assertFalse($this->callMethod($migration, 'isPostgreSQL'));

        $migration->down($schema);
    }

    /**
     * Mocks migration for specified platform.
     */
    private function createMigration(AbstractPlatform $platform): AbstractExtendedMigration
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($this->createMock(AbstractSchemaManager::class))
        ;
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        return new class($connection, $logger) extends AbstractExtendedMigration {
            public string $log = '';

            protected function getVersion(): string
            {
                return '4.0';
            }

            protected function upMariaDB(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }

            protected function upMySQL(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }

            protected function upPostgreSQL(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }

            protected function downMariaDB(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }

            protected function downMySQL(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }

            protected function downPostgreSQL(Schema $schema): void
            {
                $this->log = __FUNCTION__;
            }
        };
    }
}
