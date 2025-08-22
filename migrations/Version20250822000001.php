<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250822000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for JiraExporter';
    }

    public function up(Schema $schema): void
    {
        // Users table
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(180) NOT NULL UNIQUE,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Jobs table
        $this->addSql('CREATE TABLE jobs (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            jql TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Job logs table
        $this->addSql('CREATE TABLE job_logs (
            id INT AUTO_INCREMENT NOT NULL,
            job_id INT NOT NULL,
            job_name VARCHAR(255) NOT NULL,
            started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            status VARCHAR(20) NOT NULL,
            issue_count INT DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Jira config table
        $this->addSql('CREATE TABLE jira_config (
            id INT AUTO_INCREMENT NOT NULL,
            base_url VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            verify_tls TINYINT(1) NOT NULL DEFAULT 1,
            export_base_dir VARCHAR(500) NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add indexes
        $this->addSql('CREATE INDEX IDX_JOB_LOGS_JOB_ID ON job_logs (job_id)');
        $this->addSql('CREATE INDEX IDX_JOB_LOGS_STARTED_AT ON job_logs (started_at)');
        $this->addSql('CREATE INDEX IDX_JOB_LOGS_STATUS ON job_logs (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE jira_config');
        $this->addSql('DROP TABLE job_logs');
        $this->addSql('DROP TABLE jobs');
        $this->addSql('DROP TABLE users');
    }
}
