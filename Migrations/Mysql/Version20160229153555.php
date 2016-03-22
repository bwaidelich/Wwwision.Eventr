<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Initial migration
 */
class Version20160229153555 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE eventr_aggregate_types (name VARCHAR(255) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("CREATE TABLE eventr_event_types (aggregatetype VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, `schema` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', INDEX IDX_5FD7AB9A86680307 (aggregatetype), PRIMARY KEY(aggregatetype, name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE eventr_event_types ADD CONSTRAINT FK_5FD7AB9A86680307 FOREIGN KEY (aggregatetype) REFERENCES eventr_aggregate_types (name)");

        $this->addSql("CREATE TABLE eventr_projections (name VARCHAR(255) NOT NULL, aggregatetype VARCHAR(255) DEFAULT NULL, synchronous TINYINT(1) NOT NULL, version INT NOT NULL, mapping LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', adapterconfiguration LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', INDEX IDX_8E7FDDB886680307 (aggregatetype), PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE eventr_projections ADD CONSTRAINT FK_8E7FDDB886680307 FOREIGN KEY (aggregatetype) REFERENCES eventr_aggregate_types (name)");

        $this->addSql("CREATE TABLE eventr_events (id INT AUTO_INCREMENT NOT NULL, stream VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, version INT NOT NULL, saved_at DATETIME NOT NULL, data LONGTEXT NOT NULL, metadata LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_4D906EE1F0E9BE1CBF1CD3C3 (stream, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE eventr_event_types DROP FOREIGN KEY FK_5FD7AB9A86680307");
        $this->addSql("ALTER TABLE eventr_projections DROP FOREIGN KEY FK_8E7FDDB886680307");
        $this->addSql("DROP TABLE eventr_aggregate_types");
        $this->addSql("DROP TABLE eventr_event_types");
        $this->addSql("DROP TABLE eventr_projections");
        $this->addSql("DROP TABLE eventr_events");
    }
}