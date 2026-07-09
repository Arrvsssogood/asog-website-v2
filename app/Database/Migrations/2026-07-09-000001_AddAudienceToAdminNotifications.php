<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAudienceToAdminNotifications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('admin_notifications', [
            'targetRole' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'sourceId',
            ],
            'targetAdminId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'targetRole',
            ],
            'actorAdminId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'targetAdminId',
            ],
        ]);

        $this->db->table('admin_notifications')
            ->where('targetRole', null)
            ->where('targetAdminId', null)
            ->update(['targetRole' => 'admin']);

        $this->forge->addKey('targetRole');
        $this->forge->addKey('targetAdminId');
        $this->forge->addKey('actorAdminId');
        $this->forge->processIndexes('admin_notifications');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'notificationId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'adminId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'readAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['notificationId', 'adminId']);
        $this->forge->addUniqueKey(['notificationId', 'adminId'], 'admin_notification_reads_unique');
        $this->forge->createTable('admin_notification_reads');
    }

    public function down()
    {
        $this->forge->dropTable('admin_notification_reads', true);
        $this->forge->dropColumn('admin_notifications', ['targetRole', 'targetAdminId', 'actorAdminId']);
    }
}
