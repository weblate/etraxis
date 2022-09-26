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

namespace App\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;

/**
 * Abstract eTraxis migration.
 */
abstract class AbstractExtendedMigration extends AbstractMigration
{
    /**
     * @see AbstractMigration
     */
    final public function getDescription(): string
    {
        return 'eTraxis '.$this->getVersion();
    }

    /**
     * @see AbstractMigration
     */
    final public function up(Schema $schema): void
    {
        if ($this->isMariaDB()) {
            $this->write('Upgrading MariaDB');
            $this->upMariaDB($schema);
        } elseif ($this->isMySQL()) {
            $this->write('Upgrading MySQL');
            $this->upMySQL($schema);
        } elseif ($this->isPostgreSQL()) {
            $this->write('Upgrading PostgreSQL');
            $this->upPostgreSQL($schema);
        } else {
            throw new AbortMigration('Not supported - '.get_class($this->platform));
        }
    }

    /**
     * @see AbstractMigration
     */
    final public function down(Schema $schema): void
    {
        if ($this->isMariaDB()) {
            $this->write('Downgrading MariaDB');
            $this->downMariaDB($schema);
        } elseif ($this->isMySQL()) {
            $this->write('Downgrading MySQL');
            $this->downMySQL($schema);
        } elseif ($this->isPostgreSQL()) {
            $this->write('Downgrading PostgreSQL');
            $this->downPostgreSQL($schema);
        } else {
            throw new AbortMigration('Not supported - '.get_class($this->platform));
        }
    }

    /**
     * Returns eTraxis version this file migrates to.
     *
     * @return string Version in 'x.y' notation, where 'x' - major version number, 'y' - minor version number.
     */
    abstract protected function getVersion(): string;

    /**
     * Upgrades MariaDB database.
     */
    abstract protected function upMariaDB(Schema $schema): void;

    /**
     * Upgrades MySQL database.
     */
    abstract protected function upMySQL(Schema $schema): void;

    /**
     * Upgrades PostgreSQL database.
     */
    abstract protected function upPostgreSQL(Schema $schema): void;

    /**
     * Downgrades MariaDB database.
     */
    abstract protected function downMariaDB(Schema $schema): void;

    /**
     * Downgrades MySQL database.
     */
    abstract protected function downMySQL(Schema $schema): void;

    /**
     * Downgrades PostgreSQL database.
     */
    abstract protected function downPostgreSQL(Schema $schema): void;

    /**
     * Checks whether the current DB platform is MariaDB.
     */
    private function isMariaDB(): bool
    {
        return str_starts_with(strtolower(get_class($this->platform)), strtolower('Doctrine\DBAL\Platforms\MariaDb'));
    }

    /**
     * Checks whether the current DB platform is MySQL.
     */
    private function isMySQL(): bool
    {
        return str_starts_with(strtolower(get_class($this->platform)), strtolower('Doctrine\DBAL\Platforms\MySQL'));
    }

    /**
     * Checks whether the current DB platform is PostgreSQL.
     */
    private function isPostgreSQL(): bool
    {
        return str_starts_with(strtolower(get_class($this->platform)), strtolower('Doctrine\DBAL\Platforms\PostgreSQL'));
    }
}
