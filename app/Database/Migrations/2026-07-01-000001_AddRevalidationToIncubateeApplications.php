<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRevalidationToIncubateeApplications extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE incubatee_applications
             MODIFY COLUMN applicationStatus ENUM('pending','for_revalidation','accepted','rejected') NOT NULL DEFAULT 'pending'"
        );

        if (! $this->db->fieldExists('revalidationTokenHash', 'incubatee_applications')) {
            $this->forge->addColumn('incubatee_applications', [
                'revalidationTokenHash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'statusRemark',
                ],
                'revalidationTokenExpiresAt' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'revalidationTokenHash',
                ],
                'revalidationRequestedAt' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'revalidationTokenExpiresAt',
                ],
                'revalidatedAt' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'revalidationRequestedAt',
                ],
            ]);
        }

        if (! $this->hasIndex('incubatee_applications', 'idx_incubatee_applications_revalidation_token')) {
            $this->db->query(
                'CREATE INDEX `idx_incubatee_applications_revalidation_token`
                 ON `incubatee_applications` (`revalidationTokenHash`)'
            );
        }
    }

    public function down()
    {
        $this->db->query(
            "UPDATE incubatee_applications
             SET applicationStatus = 'pending'
             WHERE applicationStatus = 'for_revalidation'"
        );

        $this->db->query(
            "ALTER TABLE incubatee_applications
             MODIFY COLUMN applicationStatus ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'"
        );

        if ($this->hasIndex('incubatee_applications', 'idx_incubatee_applications_revalidation_token')) {
            $this->db->query('DROP INDEX `idx_incubatee_applications_revalidation_token` ON `incubatee_applications`');
        }

        foreach (['revalidatedAt', 'revalidationRequestedAt', 'revalidationTokenExpiresAt', 'revalidationTokenHash'] as $field) {
            if ($this->db->fieldExists($field, 'incubatee_applications')) {
                $this->forge->dropColumn('incubatee_applications', $field);
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        foreach ($this->db->getIndexData($table) as $key => $index) {
            if ($key === $indexName) {
                return true;
            }

            if (is_object($index) && (($index->name ?? null) === $indexName)) {
                return true;
            }

            if (is_array($index) && (($index['name'] ?? null) === $indexName)) {
                return true;
            }
        }

        return false;
    }
}
