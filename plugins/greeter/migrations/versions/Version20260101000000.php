<?php

declare(strict_types=1);

namespace Greeter\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the greeter_subscribers table.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('greeter_subscribers');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('telegram_user_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('locale', 'string', ['length' => 10, 'default' => 'fa']);
        $table->addColumn('joined_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['telegram_user_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('greeter_subscribers');
    }
}