<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Initial migration
 */
class Version20160229153555 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("CREATE TABLE wwwision_eventr_domain_model_aggregatetype (name VARCHAR(255) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
		$this->addSql("CREATE TABLE wwwision_eventr_domain_model_eventtype (name VARCHAR(255) NOT NULL, aggregatetype VARCHAR(255) DEFAULT NULL, `schema` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', INDEX IDX_C217BE9786680307 (aggregatetype), PRIMARY KEY(name, aggregatetype)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
		$this->addSql("CREATE TABLE wwwision_eventr_domain_model_projection (name VARCHAR(255) NOT NULL, aggregatetype VARCHAR(255) DEFAULT NULL, synchronous TINYINT(1) NOT NULL, version INT NOT NULL, mapping LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', adapterconfiguration LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', INDEX IDX_EE7DA3F486680307 (aggregatetype), PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
		$this->addSql("ALTER TABLE wwwision_eventr_domain_model_eventtype ADD CONSTRAINT FK_C217BE9786680307 FOREIGN KEY (aggregatetype) REFERENCES wwwision_eventr_domain_model_aggregatetype (name)");
		$this->addSql("ALTER TABLE wwwision_eventr_domain_model_projection ADD CONSTRAINT FK_EE7DA3F486680307 FOREIGN KEY (aggregatetype) REFERENCES wwwision_eventr_domain_model_aggregatetype (name)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE wwwision_eventr_domain_model_eventtype DROP FOREIGN KEY FK_C217BE9786680307");
		$this->addSql("ALTER TABLE wwwision_eventr_domain_model_projection DROP FOREIGN KEY FK_EE7DA3F486680307");
		$this->addSql("DROP TABLE wwwision_eventr_domain_model_aggregatetype");
		$this->addSql("DROP TABLE wwwision_eventr_domain_model_eventtype");
		$this->addSql("DROP TABLE wwwision_eventr_domain_model_projection");
	}
}