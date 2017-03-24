<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create batchsize column
 */
class Version20160913125512 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        /**
         * This migration is empty on purpose. Its content  has been moved to Version20160229153555.php because of
         * a migration order problem with another dependent package.
         **/
        // $this->addSql('ALTER TABLE eventr_projections ADD batchsize INT DEFAULT NULL');

    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        /**
         * See above
         */
        // $this->addSql('ALTER TABLE eventr_projections DROP batchsize');
    }
}
