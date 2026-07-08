<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationMemberModel extends Model
{
    private const CACHE_TTL = 300;
    private const CACHE_VERSION_KEY = 'asog_organization_public_version';

    public const SECTION_CORE_TEAM = 'core_team';
    public const SECTION_TBI_STAFF = 'tbi_staff';
    public const SECTION_INTERN = 'intern';
    public const SECTION_MENTOR = 'mentor';

    public const MENTOR_CATEGORIES = [
        'Business',
        'Artificial Intelligence',
        'Engineering',
        'Prototyping',
        'Financial Management',
        'Value Chain',
    ];

    public const SECTIONS = [
        self::SECTION_CORE_TEAM,
        self::SECTION_TBI_STAFF,
        self::SECTION_INTERN,
        self::SECTION_MENTOR,
    ];

    protected $table            = 'organization_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'section',
        'fullName',
        'rolePrimary',
        'roleSecondary',
        'mentorCategory',
        'photoPath',
        'isFeatured',
        'sortOrder',
        'isPublished',
    ];

    protected $validationRules = [
        'section'        => 'required|in_list[core_team,tbi_staff,intern,mentor]',
        'fullName'       => 'required|max_length[150]',
        'rolePrimary'    => 'permit_empty|max_length[255]',
        'roleSecondary'  => 'permit_empty|max_length[255]',
        'mentorCategory' => 'permit_empty|max_length[100]',
        'photoPath'      => 'permit_empty|max_length[500]',
        'isFeatured'     => 'required|in_list[0,1]',
        'sortOrder'      => 'permit_empty|integer',
        'isPublished'    => 'required|in_list[0,1]',
    ];

    protected $afterInsert = ['clearPublicCacheAfterWrite'];
    protected $afterUpdate = ['clearPublicCacheAfterWrite'];
    protected $afterDelete = ['clearPublicCacheAfterWrite'];

    public function getBySection(string $section, ?string $mentorCategory = null): array
    {
        $builder = $this->where('section', $section);

        if ($section === self::SECTION_MENTOR && $mentorCategory !== null && $mentorCategory !== '') {
            $builder->where('mentorCategory', $mentorCategory);
        }

        return $builder->orderBy('sortOrder', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array{category: string, members: list<array<string, mixed>>}>
     */
    public function getMentorsGroupedForAdmin(): array
    {
        $allMentors = $this->getBySection(self::SECTION_MENTOR);
        $grouped = [];

        foreach ($allMentors as $mentor) {
            $category = trim((string) ($mentor['mentorCategory'] ?? ''));
            if ($category === '') {
                $category = 'Uncategorized';
            }
            $grouped[$category][] = $mentor;
        }

        $result = [];
        foreach (self::MENTOR_CATEGORIES as $category) {
            $result[] = [
                'category' => $category,
                'members'  => $grouped[$category] ?? [],
            ];
            unset($grouped[$category]);
        }

        foreach ($grouped as $category => $members) {
            $result[] = ['category' => $category, 'members' => $members];
        }

        return $result;
    }

    public function getPublishedBySection(string $section): array
    {
        $cache = service('cache');
        $cacheKey = $this->cacheKey('section_' . hash('sha256', $section));
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->where('section', $section)
            ->where('isPublished', 1)
            ->orderBy('sortOrder', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        $cache->save($cacheKey, $rows, self::CACHE_TTL);

        return $rows;
    }

    public function getGroupedPublished(): array
    {
        $grouped = [];

        foreach (self::SECTIONS as $section) {
            $grouped[$section] = $this->getPublishedBySection($section);
        }

        return $grouped;
    }

    public function getNextSortOrder(string $section, ?string $mentorCategory = null): int
    {
        $builder = $this->selectMax('sortOrder')->where('section', $section);

        if ($section === self::SECTION_MENTOR && $mentorCategory !== null && $mentorCategory !== '') {
            $builder->where('mentorCategory', $mentorCategory);
        }

        $row = $builder->first();

        return ((int) ($row['sortOrder'] ?? 0)) + 1;
    }

    public function normalizeOrder(string $section, ?string $mentorCategory = null): void
    {
        foreach ($this->getBySection($section, $mentorCategory) as $index => $member) {
            $expected = $index + 1;
            if ((int) $member['sortOrder'] !== $expected) {
                $this->update((int) $member['id'], ['sortOrder' => $expected]);
            }
        }
    }

    public static function sectionLabel(string $section): string
    {
        return match ($section) {
            self::SECTION_CORE_TEAM => 'Core Team',
            self::SECTION_TBI_STAFF => 'TBI Staff',
            self::SECTION_INTERN    => 'Interns',
            self::SECTION_MENTOR    => 'Mentors',
            default                 => ucfirst(str_replace('_', ' ', $section)),
        };
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
        return 'asog_organization_public_' . $this->cacheVersion() . '_' . $suffix;
    }

    private function cacheVersion(): string
    {
        $version = service('cache')->get(self::CACHE_VERSION_KEY);

        return is_string($version) && $version !== '' ? $version : '1';
    }
}
