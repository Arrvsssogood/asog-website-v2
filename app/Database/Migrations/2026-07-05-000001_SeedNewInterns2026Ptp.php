<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedNewInterns2026Ptp extends Migration
{
    private const SECTION_INTERN = 'intern';

    /**
     * @return list<array{fullName: string, photoPath: string}>
     */
    private function interns(): array
    {
        return [
            ['fullName' => 'Fernanne Hannah A. Enimedez', 'photoPath' => 'assets/img/team/interns/Enimedez_2026PTP.webp'],
            ['fullName' => 'Jessica Mae T. Lanuzo', 'photoPath' => 'assets/img/team/interns/Lanuzo_2026PTP.webp'],
            ['fullName' => 'John Carlo E. Nas', 'photoPath' => 'assets/img/team/interns/Nas_2026PTP.webp'],
            ['fullName' => 'Harvey Lloyd V. Palacios', 'photoPath' => 'assets/img/team/interns/Palacios_2026PTP.webp'],
            ['fullName' => 'Marc Justin N. Prestado', 'photoPath' => 'assets/img/team/interns/Prestado_2026PTP.webp'],
            ['fullName' => 'John Patrick Y. Salcedo', 'photoPath' => 'assets/img/team/interns/Salcedo_2026PTP.webp'],
        ];
    }

    public function up(): void
    {
        if (! $this->db->tableExists('organization_members')) {
            return;
        }

        $table = $this->db->table('organization_members');

        // This migration intentionally replaces the intern roster only.
        $table->where('section', self::SECTION_INTERN)->delete();

        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($this->interns() as $index => $intern) {
            $rows[] = [
                'section'        => self::SECTION_INTERN,
                'fullName'       => $intern['fullName'],
                'rolePrimary'    => 'Intern',
                'roleSecondary'  => null,
                'mentorCategory' => null,
                'photoPath'      => $intern['photoPath'],
                'isFeatured'     => 0,
                'sortOrder'      => $index + 1,
                'isPublished'    => 1,
                'createdAt'      => $now,
                'updatedAt'      => $now,
            ];
        }

        if ($rows !== []) {
            $this->db->table('organization_members')->insertBatch($rows);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('organization_members')) {
            return;
        }

        $photoPaths = array_column($this->interns(), 'photoPath');

        if ($photoPaths !== []) {
            $this->db->table('organization_members')
                ->where('section', self::SECTION_INTERN)
                ->whereIn('photoPath', $photoPaths)
                ->delete();
        }
    }
}
