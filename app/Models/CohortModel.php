<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CohortModel — manages the `cohorts` table.
 * Cohorts exist independently of incubatees so they can
 * appear in navigation and landing pages even when empty.
 */
class CohortModel extends Model
{
    private const CACHE_TTL = 300;
    private const CACHE_ACTIVE = 'asog_cohorts_active';
    private const CACHE_ACTIVE_NAMES = 'asog_cohorts_active_names';

    protected $table            = 'cohorts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'name',
        'number',
        'isActive',
    ];

    protected $validationRules = [
        'name'   => 'required|max_length[100]|is_unique[cohorts.name,id,{id}]',
        'number' => 'required|integer|is_unique[cohorts.number,id,{id}]',
    ];

    protected $afterInsert = ['clearCohortCache'];
    protected $afterUpdate = ['clearCohortCache'];
    protected $afterDelete = ['clearCohortCache'];

    // ─── Query Helpers ───────────────────────────────────────

    /**
     * All active cohorts, sorted by number ascending.
     */
    public function getActive(): array
    {
        $cache = service('cache');
        $cached = $cache->get(self::CACHE_ACTIVE);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->where('isActive', 1)
                    ->orderBy('number', 'ASC')
                    ->findAll();
        $cache->save(self::CACHE_ACTIVE, $rows, self::CACHE_TTL);

        return $rows;
    }

    /**
     * Return just the cohort names of active cohorts.
     * e.g. ['Cohort 1', 'Cohort 2']
     */
    public function getActiveNames(): array
    {
        $cache = service('cache');
        $cached = $cache->get(self::CACHE_ACTIVE_NAMES);
        if (is_array($cached)) {
            return $cached;
        }

        $names = array_column($this->getActive(), 'name');
        $cache->save(self::CACHE_ACTIVE_NAMES, $names, self::CACHE_TTL);

        return $names;
    }

    /**
     * Get all cohorts (including inactive), sorted by number.
     */
    public function getAllSorted(): array
    {
        return $this->orderBy('number', 'ASC')->findAll();
    }

    /**
     * Find the next available cohort number.
     */
    public function nextNumber(): int
    {
        $max = $this->selectMax('number')->first();
        return ($max['number'] ?? 0) + 1;
    }

    protected function clearCohortCache(array $data): array
    {
        service('cache')->delete(self::CACHE_ACTIVE);
        service('cache')->delete(self::CACHE_ACTIVE_NAMES);

        return $data;
    }
}
