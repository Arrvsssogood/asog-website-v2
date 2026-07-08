<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedDefaultLeanCanvasTemplate extends Migration
{
    private const SETTING_KEY = 'apply_lean_canvas_template';
    private const SOURCE_FILE = APPPATH . 'Database/MigrationFiles/asog-tbi-startup-lean-canvas.docx';
    private const TARGET_RELATIVE_PATH = 'templates/asog-tbi-startup-lean-canvas.docx';

    public function up(): void
    {
        if (! $this->db->tableExists('landing_settings')) {
            return;
        }

        $existing = $this->db->table('landing_settings')
            ->where('settingKey', self::SETTING_KEY)
            ->get()
            ->getRowArray();

        if (is_array($existing) && trim((string) ($existing['settingValue'] ?? '')) !== '') {
            return;
        }

        $sourcePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::SOURCE_FILE);
        $targetPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::TARGET_RELATIVE_PATH);
        $targetDir = dirname($targetPath);

        if (! is_file($sourcePath)) {
            return;
        }

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return;
        }

        if (! is_file($targetPath) && ! copy($sourcePath, $targetPath)) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        if (is_array($existing) && isset($existing['id'])) {
            $this->db->table('landing_settings')
                ->where('id', (int) $existing['id'])
                ->update([
                    'settingValue' => self::TARGET_RELATIVE_PATH,
                    'updatedAt' => $now,
                ]);
            $this->clearSettingCache();

            return;
        }

        $this->db->table('landing_settings')->insert([
            'settingKey' => self::SETTING_KEY,
            'settingValue' => self::TARGET_RELATIVE_PATH,
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
        $this->clearSettingCache();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('landing_settings')) {
            return;
        }

        $this->db->table('landing_settings')
            ->where('settingKey', self::SETTING_KEY)
            ->where('settingValue', self::TARGET_RELATIVE_PATH)
            ->delete();
        $this->clearSettingCache();

        $targetPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::TARGET_RELATIVE_PATH);

        if (is_file($targetPath)) {
            unlink($targetPath);
        }
    }

    private function clearSettingCache(): void
    {
        service('cache')->delete('asog_landing_setting_' . hash('sha256', self::SETTING_KEY));
    }
}
