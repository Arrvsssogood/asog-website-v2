<?php

namespace App\Models;

use CodeIgniter\Model;

/**  
 * IncubateeModel — manages the `incubatees` showcase table.
**/
class IncubateeModel extends Model
{
    private const CACHE_TTL = 300;
    private const CACHE_VERSION_KEY = 'asog_incubatees_public_version';

    protected $table            = 'incubatees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'companyName',
        'slug',
        'shortDescription',
        'content',
        'sdgNumbers',
        'logoPath',
        'logoWhitePath',
        'websiteUrl',
        'facebookUrl',
        'contactDetails',
        'contactName',
        'contactNumber',
        'contactEmail',
        'cohort',
        'teamMembers',
        'sortOrder',
        'isPublished',
    ];

    protected $afterInsert = ['clearPublicCacheAfterWrite'];
    protected $afterUpdate = ['clearPublicCacheAfterWrite'];
    protected $afterDelete = ['clearPublicCacheAfterWrite'];

    // ─── Query Helpers ───────────────────────────────────────

    /**  
     * All published incubatees, ordered by sortOrder then newest.
    **/
    public function getPublished(): array
    {
        $cache = service('cache');
        $cacheKey = $this->cacheKey('published');
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->where('isPublished', 1)
                    ->orderBy('sortOrder', 'ASC')
                    ->orderBy('createdAt', 'DESC')
                    ->findAll();
        $cache->save($cacheKey, $rows, self::CACHE_TTL);

        return $rows;
    }

    /**  
     * Daily-rotating featured incubatee.
     * Uses the current day-of-year modulo the number of published incubatees
     * so a different one is highlighted each day.
    **/
    public function getFeatured(): ?array
    {
        $published = $this->getPublished();

        if (empty($published)) {
            return null;
        }

        $dayOfYear = (int) date('z'); // 0–365
        $index     = $dayOfYear % count($published);

        return $published[$index];
    }

    /**  
     * Find by slug.
    **/
    public function getBySlug(string $slug): ?array
    {
        foreach ($this->getPublished() as $incubatee) {
            if ((string) ($incubatee['slug'] ?? '') === $slug) {
                return $incubatee;
            }
        }

        return null;
    }

    /**  
     * All published incubatees filtered by cohort.
    **/
    public function getPublishedByCohort(string $cohort): array
    {
        return array_values(array_filter(
            $this->getPublished(),
            static fn (array $incubatee): bool => (string) ($incubatee['cohort'] ?? '') === $cohort
        ));
    }

    /**  
     * Distinct cohort values from published incubatees, sorted naturally.
     * Returns e.g. ['Cohort 1', 'Cohort 2', 'Cohort 3']
    **/
    public function getDistinctCohorts(): array
    {
        $cohorts = [];
        foreach ($this->getPublished() as $incubatee) {
            $cohort = trim((string) ($incubatee['cohort'] ?? ''));
            if ($cohort !== '') {
                $cohorts[$cohort] = true;
            }
        }

        $cohortNames = array_keys($cohorts);
        sort($cohortNames, SORT_NATURAL | SORT_FLAG_CASE);

        return $cohortNames;
    }

    /**  
     * Grouped by cohort for display.
    **/
    public function getPublishedGroupedByCohort(): array
    {
        $all = $this->getPublished();
        $grouped = [];
        foreach ($all as $inc) {
            $cohort = $inc['cohort'] ?: 'Other';
            $grouped[$cohort][] = $inc;
        }
        return $grouped;
    }

    /**  
     * Generate a unique slug from the company name.
    **/
    public function generateSlug(string $companyName, ?int $excludeId = null): string
    {
        $base = url_title($companyName, '-', true);
        $slug = $base;
        $i    = 1;

        while (true) {
            // Use an independent builder so we don't pollute the model's
            // internal query state (which would break a subsequent update()).
            $builder = $this->db->table($this->table)->where('slug', $slug);
            if ($excludeId !== null) {
                $builder->where('id !=', $excludeId);
            }
            if ($builder->countAllResults() === 0) {
                break;
            }
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function clearPublicCache(): void
    {
        $cache = service('cache');
        $cache->save(self::CACHE_VERSION_KEY, time() . '_' . random_int(1000, 9999), 86400);
    }

    protected function clearPublicCacheAfterWrite(array $data): array
    {
        $this->clearPublicCache();

        return $data;
    }

    private function cacheKey(string $suffix): string
    {
        return 'asog_incubatees_public_' . $this->cacheVersion() . '_' . $suffix;
    }

    private function cacheVersion(): string
    {
        $version = service('cache')->get(self::CACHE_VERSION_KEY);

        return is_string($version) && $version !== '' ? $version : '1';
    }
}
