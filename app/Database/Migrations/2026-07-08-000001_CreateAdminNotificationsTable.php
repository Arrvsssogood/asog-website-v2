<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'sourceType' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'sourceId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'priority' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'normal',
            ],
            'isRead' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'readAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'createdAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updatedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('type');
        $this->forge->addKey('isRead');
        $this->forge->addKey('createdAt');
        $this->forge->addKey(['sourceType', 'sourceId']);

        $this->forge->createTable('admin_notifications');
    }

    public function down()
    {
        $this->forge->dropTable('admin_notifications');
    }
}
