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

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\AbstractExtendedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to eTraxis 4.0.
 */
final class Version0400 extends AbstractExtendedMigration
{
    /**
     * {@inheritDoc}
     */
    public function preUp(Schema $schema): void
    {
        if ($this->sm->tablesExist('tbl_sys_vars')) {
            $version = $this->connection->fetchOne('SELECT var_value FROM tbl_sys_vars WHERE var_name = \'FEATURE_LEVEL\'');
            $this->abortIf('3.10' !== $version, 'Current version of eTraxis should be 3.10 or later to import the existing data');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function postUp(Schema $schema): void
    {
        if ($this->sm->tablesExist('tbl_sys_vars')) {
            $this->write('Run "./bin/console etraxis:migrate-data" to import the existing data from eTraxis 3');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getVersion(): string
    {
        return '4.0';
    }

    /**
     * {@inheritDoc}
     */
    protected function upMariaDB(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(254) NOT NULL, password VARCHAR(255) DEFAULT NULL, fullname VARCHAR(50) NOT NULL, description VARCHAR(100) DEFAULT NULL, admin TINYINT(1) NOT NULL, disabled TINYINT(1) NOT NULL, account_provider VARCHAR(20) NOT NULL, account_uid VARCHAR(255) NOT NULL, settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9EA0A912A13605F58 (account_provider, account_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE groups (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, INDEX IDX_F06D3970166D1F9C (project_id), UNIQUE INDEX UNIQ_F06D3970166D1F9C5E237E06 (project_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_86FFD285FE54D947 (group_id), INDEX IDX_86FFD285A76ED395 (user_id), PRIMARY KEY(group_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, created_at INT NOT NULL, suspended TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_5C93B3A45E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE templates (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(50) NOT NULL, prefix VARCHAR(5) NOT NULL, description VARCHAR(100) DEFAULT NULL, locked TINYINT(1) NOT NULL, critical_age INT DEFAULT NULL, frozen_time INT DEFAULT NULL, INDEX IDX_6F287D8E166D1F9C (project_id), UNIQUE INDEX UNIQ_6F287D8E166D1F9C5E237E06 (project_id, name), UNIQUE INDEX UNIQ_6F287D8E166D1F9C93B1868E (project_id, prefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, INDEX IDX_F59214375DA0FB8 (template_id), PRIMARY KEY(template_id, role, permission)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template_group_permissions (permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_F96B196C5DA0FB8 (template_id), INDEX IDX_F96B196CFE54D947 (group_id), PRIMARY KEY(template_id, group_id, permission)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE states (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(12) NOT NULL, responsible VARCHAR(10) NOT NULL, INDEX IDX_31C2774D5DA0FB8 (template_id), UNIQUE INDEX UNIQ_31C2774D5DA0FB85E237E06 (template_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_role_transitions (role VARCHAR(20) NOT NULL, from_state_id INT NOT NULL, to_state_id INT NOT NULL, INDEX IDX_5A89E9654337DC5B (from_state_id), INDEX IDX_5A89E965C881A26F (to_state_id), PRIMARY KEY(from_state_id, to_state_id, role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_group_transitions (from_state_id INT NOT NULL, to_state_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_8304AEA24337DC5B (from_state_id), INDEX IDX_8304AEA2C881A26F (to_state_id), INDEX IDX_8304AEA2FE54D947 (group_id), PRIMARY KEY(from_state_id, to_state_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_responsible_groups (state_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_A24166F05D83CC1 (state_id), INDEX IDX_A24166F0FE54D947 (group_id), PRIMARY KEY(state_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fields (id INT AUTO_INCREMENT NOT NULL, state_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL, description VARCHAR(1000) DEFAULT NULL, position INT NOT NULL, required TINYINT(1) NOT NULL, removed_at INT DEFAULT NULL, parameters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_7EE5E3885D83CC1 (state_id), UNIQUE INDEX UNIQ_7EE5E3885D83CC15E237E06455180A5 (state_id, name, removed_at), UNIQUE INDEX UNIQ_7EE5E3885D83CC1462CE4F5455180A5 (state_id, position, removed_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, INDEX IDX_5D341D45443707B0 (field_id), PRIMARY KEY(field_id, role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_group_permissions (permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_47C8AF75443707B0 (field_id), INDEX IDX_47C8AF75FE54D947 (group_id), PRIMARY KEY(field_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE list_items (field_id INT NOT NULL, item_value INT NOT NULL, item_text VARCHAR(50) NOT NULL, INDEX IDX_388D7D4E443707B0 (field_id), UNIQUE INDEX UNIQ_388D7D4E443707B0F3BBE33C (field_id, item_text), PRIMARY KEY(field_id, item_value)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE decimal_values (id INT AUTO_INCREMENT NOT NULL, value NUMERIC(20, 10) NOT NULL, UNIQUE INDEX UNIQ_95FEDD351D775834 (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE string_values (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(250) NOT NULL, UNIQUE INDEX UNIQ_B4CDAF44D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE text_values (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(10000) NOT NULL, UNIQUE INDEX UNIQ_2F2BD515D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE issues (id INT AUTO_INCREMENT NOT NULL, state_id INT NOT NULL, author_id INT NOT NULL, responsible_id INT DEFAULT NULL, origin_id INT DEFAULT NULL, subject VARCHAR(250) NOT NULL, created_at INT NOT NULL, changed_at INT NOT NULL, closed_at INT DEFAULT NULL, resumes_at INT DEFAULT NULL, INDEX IDX_DA7D7F835D83CC1 (state_id), INDEX IDX_DA7D7F83F675F31B (author_id), INDEX IDX_DA7D7F83602AD315 (responsible_id), INDEX IDX_DA7D7F8356A273CC (origin_id), UNIQUE INDEX UNIQ_DA7D7F83F675F31B8B8E8428 (author_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE last_reads (issue_id INT NOT NULL, user_id INT NOT NULL, read_at INT NOT NULL, INDEX IDX_AA0B5AAA5E7AA58C (issue_id), INDEX IDX_AA0B5AAAA76ED395 (user_id), PRIMARY KEY(issue_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchers (issue_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C4DCAF2E5E7AA58C (issue_id), INDEX IDX_C4DCAF2EA76ED395 (user_id), PRIMARY KEY(issue_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, issue_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, created_at INT NOT NULL, parameter VARCHAR(100) DEFAULT NULL, INDEX IDX_5387574A5E7AA58C (issue_id), INDEX IDX_5387574AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transitions (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, state_id INT NOT NULL, INDEX IDX_904762465D83CC1 (state_id), UNIQUE INDEX UNIQ_9047624671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_values (id INT AUTO_INCREMENT NOT NULL, transition_id INT NOT NULL, field_id INT NOT NULL, value INT DEFAULT NULL, INDEX IDX_10E3C0E48BF1A064 (transition_id), INDEX IDX_10E3C0E4443707B0 (field_id), UNIQUE INDEX UNIQ_10E3C0E48BF1A064443707B0 (transition_id, field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE changes (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, field_id INT DEFAULT NULL, old_value INT DEFAULT NULL, new_value INT DEFAULT NULL, INDEX IDX_2020B83D71F7E88B (event_id), INDEX IDX_2020B83D443707B0 (field_id), UNIQUE INDEX UNIQ_2020B83D71F7E88B443707B0 (event_id, field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, body VARCHAR(10000) NOT NULL, private TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_5F9E962A71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, uid VARCHAR(36) NOT NULL, file_name VARCHAR(100) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(255) NOT NULL, removed_at INT DEFAULT NULL, UNIQUE INDEX UNIQ_635405971F7E88B (event_id), UNIQUE INDEX UNIQ_6354059539B0606 (uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dependencies (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, INDEX IDX_EA0F708D5E7AA58C (issue_id), UNIQUE INDEX UNIQ_EA0F708D71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE related_issues (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, INDEX IDX_76B107255E7AA58C (issue_id), UNIQUE INDEX UNIQ_76B1072571F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_role_permissions ADD CONSTRAINT FK_F59214375DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196C5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196CFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE states ADD CONSTRAINT FK_31C2774D5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E9654337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E965C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA24337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F05D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F0FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3885D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_role_permissions ADD CONSTRAINT FK_5D341D45443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE list_items ADD CONSTRAINT FK_388D7D4E443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F835D83CC1 FOREIGN KEY (state_id) REFERENCES states (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83602AD315 FOREIGN KEY (responsible_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F8356A273CC FOREIGN KEY (origin_id) REFERENCES issues (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAA5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2E5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_9047624671F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_904762465D83CC1 FOREIGN KEY (state_id) REFERENCES states (id)');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E48BF1A064 FOREIGN KEY (transition_id) REFERENCES transitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E4443707B0 FOREIGN KEY (field_id) REFERENCES fields (id)');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D443707B0 FOREIGN KEY (field_id) REFERENCES fields (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_635405971F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id)');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B1072571F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B107255E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id)');
    }

    /**
     * {@inheritDoc}
     */
    protected function upMySQL(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(254) NOT NULL, password VARCHAR(255) DEFAULT NULL, fullname VARCHAR(50) NOT NULL, description VARCHAR(100) DEFAULT NULL, `admin` TINYINT(1) NOT NULL, disabled TINYINT(1) NOT NULL, account_provider VARCHAR(20) NOT NULL, account_uid VARCHAR(255) NOT NULL, settings JSON DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9EA0A912A13605F58 (account_provider, account_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `groups` (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, INDEX IDX_F06D3970166D1F9C (project_id), UNIQUE INDEX UNIQ_F06D3970166D1F9C5E237E06 (project_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_86FFD285FE54D947 (group_id), INDEX IDX_86FFD285A76ED395 (user_id), PRIMARY KEY(group_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, created_at INT NOT NULL, suspended TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_5C93B3A45E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE templates (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(50) NOT NULL, prefix VARCHAR(5) NOT NULL, description VARCHAR(100) DEFAULT NULL, locked TINYINT(1) NOT NULL, critical_age INT DEFAULT NULL, frozen_time INT DEFAULT NULL, INDEX IDX_6F287D8E166D1F9C (project_id), UNIQUE INDEX UNIQ_6F287D8E166D1F9C5E237E06 (project_id, name), UNIQUE INDEX UNIQ_6F287D8E166D1F9C93B1868E (project_id, prefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, INDEX IDX_F59214375DA0FB8 (template_id), PRIMARY KEY(template_id, role, permission)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template_group_permissions (permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_F96B196C5DA0FB8 (template_id), INDEX IDX_F96B196CFE54D947 (group_id), PRIMARY KEY(template_id, group_id, permission)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE states (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(12) NOT NULL, responsible VARCHAR(10) NOT NULL, INDEX IDX_31C2774D5DA0FB8 (template_id), UNIQUE INDEX UNIQ_31C2774D5DA0FB85E237E06 (template_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_role_transitions (role VARCHAR(20) NOT NULL, from_state_id INT NOT NULL, to_state_id INT NOT NULL, INDEX IDX_5A89E9654337DC5B (from_state_id), INDEX IDX_5A89E965C881A26F (to_state_id), PRIMARY KEY(from_state_id, to_state_id, role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_group_transitions (from_state_id INT NOT NULL, to_state_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_8304AEA24337DC5B (from_state_id), INDEX IDX_8304AEA2C881A26F (to_state_id), INDEX IDX_8304AEA2FE54D947 (group_id), PRIMARY KEY(from_state_id, to_state_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state_responsible_groups (state_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_A24166F05D83CC1 (state_id), INDEX IDX_A24166F0FE54D947 (group_id), PRIMARY KEY(state_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fields (id INT AUTO_INCREMENT NOT NULL, state_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL, description VARCHAR(1000) DEFAULT NULL, position INT NOT NULL, required TINYINT(1) NOT NULL, removed_at INT DEFAULT NULL, parameters JSON DEFAULT NULL, INDEX IDX_7EE5E3885D83CC1 (state_id), UNIQUE INDEX UNIQ_7EE5E3885D83CC15E237E06455180A5 (state_id, name, removed_at), UNIQUE INDEX UNIQ_7EE5E3885D83CC1462CE4F5455180A5 (state_id, position, removed_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, INDEX IDX_5D341D45443707B0 (field_id), PRIMARY KEY(field_id, role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_group_permissions (permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_47C8AF75443707B0 (field_id), INDEX IDX_47C8AF75FE54D947 (group_id), PRIMARY KEY(field_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE list_items (field_id INT NOT NULL, item_value INT NOT NULL, item_text VARCHAR(50) NOT NULL, INDEX IDX_388D7D4E443707B0 (field_id), UNIQUE INDEX UNIQ_388D7D4E443707B0F3BBE33C (field_id, item_text), PRIMARY KEY(field_id, item_value)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE decimal_values (id INT AUTO_INCREMENT NOT NULL, value NUMERIC(20, 10) NOT NULL, UNIQUE INDEX UNIQ_95FEDD351D775834 (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE string_values (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(250) NOT NULL, UNIQUE INDEX UNIQ_B4CDAF44D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE text_values (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(10000) NOT NULL, UNIQUE INDEX UNIQ_2F2BD515D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE issues (id INT AUTO_INCREMENT NOT NULL, state_id INT NOT NULL, author_id INT NOT NULL, responsible_id INT DEFAULT NULL, origin_id INT DEFAULT NULL, subject VARCHAR(250) NOT NULL, created_at INT NOT NULL, changed_at INT NOT NULL, closed_at INT DEFAULT NULL, resumes_at INT DEFAULT NULL, INDEX IDX_DA7D7F835D83CC1 (state_id), INDEX IDX_DA7D7F83F675F31B (author_id), INDEX IDX_DA7D7F83602AD315 (responsible_id), INDEX IDX_DA7D7F8356A273CC (origin_id), UNIQUE INDEX UNIQ_DA7D7F83F675F31B8B8E8428 (author_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE last_reads (issue_id INT NOT NULL, user_id INT NOT NULL, read_at INT NOT NULL, INDEX IDX_AA0B5AAA5E7AA58C (issue_id), INDEX IDX_AA0B5AAAA76ED395 (user_id), PRIMARY KEY(issue_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchers (issue_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C4DCAF2E5E7AA58C (issue_id), INDEX IDX_C4DCAF2EA76ED395 (user_id), PRIMARY KEY(issue_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, issue_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, created_at INT NOT NULL, parameter VARCHAR(100) DEFAULT NULL, INDEX IDX_5387574A5E7AA58C (issue_id), INDEX IDX_5387574AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transitions (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, state_id INT NOT NULL, INDEX IDX_904762465D83CC1 (state_id), UNIQUE INDEX UNIQ_9047624671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_values (id INT AUTO_INCREMENT NOT NULL, transition_id INT NOT NULL, field_id INT NOT NULL, value INT DEFAULT NULL, INDEX IDX_10E3C0E48BF1A064 (transition_id), INDEX IDX_10E3C0E4443707B0 (field_id), UNIQUE INDEX UNIQ_10E3C0E48BF1A064443707B0 (transition_id, field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE changes (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, field_id INT DEFAULT NULL, old_value INT DEFAULT NULL, new_value INT DEFAULT NULL, INDEX IDX_2020B83D71F7E88B (event_id), INDEX IDX_2020B83D443707B0 (field_id), UNIQUE INDEX UNIQ_2020B83D71F7E88B443707B0 (event_id, field_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, body VARCHAR(10000) NOT NULL, private TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_5F9E962A71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, uid VARCHAR(36) NOT NULL, file_name VARCHAR(100) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(255) NOT NULL, removed_at INT DEFAULT NULL, UNIQUE INDEX UNIQ_635405971F7E88B (event_id), UNIQUE INDEX UNIQ_6354059539B0606 (uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dependencies (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, INDEX IDX_EA0F708D5E7AA58C (issue_id), UNIQUE INDEX UNIQ_EA0F708D71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE related_issues (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, INDEX IDX_76B107255E7AA58C (issue_id), UNIQUE INDEX UNIQ_76B1072571F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `groups` ADD CONSTRAINT FK_F06D3970166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_role_permissions ADD CONSTRAINT FK_F59214375DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196C5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196CFE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE states ADD CONSTRAINT FK_31C2774D5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E9654337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E965C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA24337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F05D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F0FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3885D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_role_permissions ADD CONSTRAINT FK_5D341D45443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE list_items ADD CONSTRAINT FK_388D7D4E443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F835D83CC1 FOREIGN KEY (state_id) REFERENCES states (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83602AD315 FOREIGN KEY (responsible_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F8356A273CC FOREIGN KEY (origin_id) REFERENCES issues (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAA5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2E5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_9047624671F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_904762465D83CC1 FOREIGN KEY (state_id) REFERENCES states (id)');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E48BF1A064 FOREIGN KEY (transition_id) REFERENCES transitions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E4443707B0 FOREIGN KEY (field_id) REFERENCES fields (id)');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D443707B0 FOREIGN KEY (field_id) REFERENCES fields (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_635405971F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id)');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B1072571F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B107255E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id)');
    }

    /**
     * {@inheritDoc}
     */
    protected function upPostgreSQL(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE groups_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE projects_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE templates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE states_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE fields_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE list_items_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE decimal_values_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE string_values_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE text_values_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE issues_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transitions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE field_values_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE changes_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE comments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE files_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE dependencies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE related_issues_id_seq INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(254) NOT NULL, password VARCHAR(255) DEFAULT NULL, fullname VARCHAR(50) NOT NULL, description VARCHAR(100) DEFAULT NULL, admin BOOLEAN NOT NULL, disabled BOOLEAN NOT NULL, account_provider VARCHAR(20) NOT NULL, account_uid VARCHAR(255) NOT NULL, settings JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9EA0A912A13605F58 ON users (account_provider, account_uid)');
        $this->addSql('CREATE TABLE groups (id INT NOT NULL, project_id INT DEFAULT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F06D3970166D1F9C ON groups (project_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D3970166D1F9C5E237E06 ON groups (project_id, name)');
        $this->addSql('CREATE TABLE membership (group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(group_id, user_id))');
        $this->addSql('CREATE INDEX IDX_86FFD285FE54D947 ON membership (group_id)');
        $this->addSql('CREATE INDEX IDX_86FFD285A76ED395 ON membership (user_id)');
        $this->addSql('CREATE TABLE projects (id INT NOT NULL, name VARCHAR(25) NOT NULL, description VARCHAR(100) DEFAULT NULL, created_at INT NOT NULL, suspended BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C93B3A45E237E06 ON projects (name)');
        $this->addSql('CREATE TABLE templates (id INT NOT NULL, project_id INT NOT NULL, name VARCHAR(50) NOT NULL, prefix VARCHAR(5) NOT NULL, description VARCHAR(100) DEFAULT NULL, locked BOOLEAN NOT NULL, critical_age INT DEFAULT NULL, frozen_time INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6F287D8E166D1F9C ON templates (project_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6F287D8E166D1F9C5E237E06 ON templates (project_id, name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6F287D8E166D1F9C93B1868E ON templates (project_id, prefix)');
        $this->addSql('CREATE TABLE template_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, PRIMARY KEY(template_id, role, permission))');
        $this->addSql('CREATE INDEX IDX_F59214375DA0FB8 ON template_role_permissions (template_id)');
        $this->addSql('CREATE TABLE template_group_permissions (permission VARCHAR(20) NOT NULL, template_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(template_id, group_id, permission))');
        $this->addSql('CREATE INDEX IDX_F96B196C5DA0FB8 ON template_group_permissions (template_id)');
        $this->addSql('CREATE INDEX IDX_F96B196CFE54D947 ON template_group_permissions (group_id)');
        $this->addSql('CREATE TABLE states (id INT NOT NULL, template_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(12) NOT NULL, responsible VARCHAR(10) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_31C2774D5DA0FB8 ON states (template_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31C2774D5DA0FB85E237E06 ON states (template_id, name)');
        $this->addSql('CREATE TABLE state_role_transitions (role VARCHAR(20) NOT NULL, from_state_id INT NOT NULL, to_state_id INT NOT NULL, PRIMARY KEY(from_state_id, to_state_id, role))');
        $this->addSql('CREATE INDEX IDX_5A89E9654337DC5B ON state_role_transitions (from_state_id)');
        $this->addSql('CREATE INDEX IDX_5A89E965C881A26F ON state_role_transitions (to_state_id)');
        $this->addSql('CREATE TABLE state_group_transitions (from_state_id INT NOT NULL, to_state_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(from_state_id, to_state_id, group_id))');
        $this->addSql('CREATE INDEX IDX_8304AEA24337DC5B ON state_group_transitions (from_state_id)');
        $this->addSql('CREATE INDEX IDX_8304AEA2C881A26F ON state_group_transitions (to_state_id)');
        $this->addSql('CREATE INDEX IDX_8304AEA2FE54D947 ON state_group_transitions (group_id)');
        $this->addSql('CREATE TABLE state_responsible_groups (state_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(state_id, group_id))');
        $this->addSql('CREATE INDEX IDX_A24166F05D83CC1 ON state_responsible_groups (state_id)');
        $this->addSql('CREATE INDEX IDX_A24166F0FE54D947 ON state_responsible_groups (group_id)');
        $this->addSql('CREATE TABLE fields (id INT NOT NULL, state_id INT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(10) NOT NULL, description VARCHAR(1000) DEFAULT NULL, position INT NOT NULL, required BOOLEAN NOT NULL, removed_at INT DEFAULT NULL, parameters JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7EE5E3885D83CC1 ON fields (state_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EE5E3885D83CC15E237E06455180A5 ON fields (state_id, name, removed_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EE5E3885D83CC1462CE4F5455180A5 ON fields (state_id, position, removed_at)');
        $this->addSql('CREATE TABLE field_role_permissions (role VARCHAR(20) NOT NULL, permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, PRIMARY KEY(field_id, role))');
        $this->addSql('CREATE INDEX IDX_5D341D45443707B0 ON field_role_permissions (field_id)');
        $this->addSql('CREATE TABLE field_group_permissions (permission VARCHAR(10) NOT NULL, field_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(field_id, group_id))');
        $this->addSql('CREATE INDEX IDX_47C8AF75443707B0 ON field_group_permissions (field_id)');
        $this->addSql('CREATE INDEX IDX_47C8AF75FE54D947 ON field_group_permissions (group_id)');
        $this->addSql('CREATE TABLE list_items (field_id INT NOT NULL, item_value INT NOT NULL, item_text VARCHAR(50) NOT NULL, PRIMARY KEY(field_id, item_value))');
        $this->addSql('CREATE INDEX IDX_388D7D4E443707B0 ON list_items (field_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_388D7D4E443707B0F3BBE33C ON list_items (field_id, item_text)');
        $this->addSql('CREATE TABLE decimal_values (id INT NOT NULL, value NUMERIC(20, 10) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95FEDD351D775834 ON decimal_values (value)');
        $this->addSql('CREATE TABLE string_values (id INT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(250) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4CDAF44D1B862B8 ON string_values (hash)');
        $this->addSql('CREATE TABLE text_values (id INT NOT NULL, hash VARCHAR(32) NOT NULL, value VARCHAR(10000) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2F2BD515D1B862B8 ON text_values (hash)');
        $this->addSql('CREATE TABLE issues (id INT NOT NULL, state_id INT NOT NULL, author_id INT NOT NULL, responsible_id INT DEFAULT NULL, origin_id INT DEFAULT NULL, subject VARCHAR(250) NOT NULL, created_at INT NOT NULL, changed_at INT NOT NULL, closed_at INT DEFAULT NULL, resumes_at INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DA7D7F835D83CC1 ON issues (state_id)');
        $this->addSql('CREATE INDEX IDX_DA7D7F83F675F31B ON issues (author_id)');
        $this->addSql('CREATE INDEX IDX_DA7D7F83602AD315 ON issues (responsible_id)');
        $this->addSql('CREATE INDEX IDX_DA7D7F8356A273CC ON issues (origin_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DA7D7F83F675F31B8B8E8428 ON issues (author_id, created_at)');
        $this->addSql('CREATE TABLE last_reads (issue_id INT NOT NULL, user_id INT NOT NULL, read_at INT NOT NULL, PRIMARY KEY(issue_id, user_id))');
        $this->addSql('CREATE INDEX IDX_AA0B5AAA5E7AA58C ON last_reads (issue_id)');
        $this->addSql('CREATE INDEX IDX_AA0B5AAAA76ED395 ON last_reads (user_id)');
        $this->addSql('CREATE TABLE watchers (issue_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(issue_id, user_id))');
        $this->addSql('CREATE INDEX IDX_C4DCAF2E5E7AA58C ON watchers (issue_id)');
        $this->addSql('CREATE INDEX IDX_C4DCAF2EA76ED395 ON watchers (user_id)');
        $this->addSql('CREATE TABLE events (id INT NOT NULL, issue_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, created_at INT NOT NULL, parameter VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5387574A5E7AA58C ON events (issue_id)');
        $this->addSql('CREATE INDEX IDX_5387574AA76ED395 ON events (user_id)');
        $this->addSql('CREATE TABLE transitions (id INT NOT NULL, event_id INT NOT NULL, state_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_904762465D83CC1 ON transitions (state_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9047624671F7E88B ON transitions (event_id)');
        $this->addSql('CREATE TABLE field_values (id INT NOT NULL, transition_id INT NOT NULL, field_id INT NOT NULL, value INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_10E3C0E48BF1A064 ON field_values (transition_id)');
        $this->addSql('CREATE INDEX IDX_10E3C0E4443707B0 ON field_values (field_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10E3C0E48BF1A064443707B0 ON field_values (transition_id, field_id)');
        $this->addSql('CREATE TABLE changes (id INT NOT NULL, event_id INT NOT NULL, field_id INT DEFAULT NULL, old_value INT DEFAULT NULL, new_value INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2020B83D71F7E88B ON changes (event_id)');
        $this->addSql('CREATE INDEX IDX_2020B83D443707B0 ON changes (field_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2020B83D71F7E88B443707B0 ON changes (event_id, field_id)');
        $this->addSql('CREATE TABLE comments (id INT NOT NULL, event_id INT NOT NULL, body VARCHAR(10000) NOT NULL, private BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F9E962A71F7E88B ON comments (event_id)');
        $this->addSql('CREATE TABLE files (id INT NOT NULL, event_id INT NOT NULL, uid VARCHAR(36) NOT NULL, file_name VARCHAR(100) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(255) NOT NULL, removed_at INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_635405971F7E88B ON files (event_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6354059539B0606 ON files (uid)');
        $this->addSql('CREATE TABLE dependencies (id INT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EA0F708D5E7AA58C ON dependencies (issue_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EA0F708D71F7E88B ON dependencies (event_id)');
        $this->addSql('CREATE TABLE related_issues (id INT NOT NULL, event_id INT NOT NULL, issue_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_76B107255E7AA58C ON related_issues (issue_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_76B1072571F7E88B ON related_issues (event_id)');

        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE template_role_permissions ADD CONSTRAINT FK_F59214375DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196C5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE template_group_permissions ADD CONSTRAINT FK_F96B196CFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE states ADD CONSTRAINT FK_31C2774D5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E9654337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_role_transitions ADD CONSTRAINT FK_5A89E965C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA24337DC5B FOREIGN KEY (from_state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2C881A26F FOREIGN KEY (to_state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_group_transitions ADD CONSTRAINT FK_8304AEA2FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F05D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE state_responsible_groups ADD CONSTRAINT FK_A24166F0FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3885D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE field_role_permissions ADD CONSTRAINT FK_5D341D45443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE field_group_permissions ADD CONSTRAINT FK_47C8AF75FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE list_items ADD CONSTRAINT FK_388D7D4E443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F835D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83602AD315 FOREIGN KEY (responsible_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F8356A273CC FOREIGN KEY (origin_id) REFERENCES issues (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAA5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE last_reads ADD CONSTRAINT FK_AA0B5AAAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2E5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watchers ADD CONSTRAINT FK_C4DCAF2EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_9047624671F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transitions ADD CONSTRAINT FK_904762465D83CC1 FOREIGN KEY (state_id) REFERENCES states (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E48BF1A064 FOREIGN KEY (transition_id) REFERENCES transitions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE field_values ADD CONSTRAINT FK_10E3C0E4443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE changes ADD CONSTRAINT FK_2020B83D443707B0 FOREIGN KEY (field_id) REFERENCES fields (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_635405971F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EA0F708D5E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B1072571F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE related_issues ADD CONSTRAINT FK_76B107255E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * {@inheritDoc}
     */
    protected function downMariaDB(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    /**
     * {@inheritDoc}
     */
    protected function downMySQL(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    /**
     * {@inheritDoc}
     */
    protected function downPostgreSQL(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
