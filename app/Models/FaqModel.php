<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    private const CACHE_TTL = 300;
    private const CACHE_PUBLISHED = 'asog_faqs_published';
    private const CACHE_ALL_ORDERED = 'asog_faqs_all_ordered';

    protected $table            = 'faqs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'question',
        'answer',
        'sortOrder',
        'isPublished',
    ];

    protected $validationRules = [
        'question'    => 'required|max_length[255]',
        'answer'      => 'required|max_length[5000]',
        'sortOrder'   => 'permit_empty|integer',
        'isPublished' => 'required|in_list[0,1]',
    ];

    protected $afterInsert = ['clearFaqCache'];
    protected $afterUpdate = ['clearFaqCache'];
    protected $afterDelete = ['clearFaqCache'];

    public function getPublished(): array
    {
        $cache = service('cache');
        $cached = $cache->get(self::CACHE_PUBLISHED);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->where('isPublished', 1)
            ->orderBy('sortOrder', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        $cache->save(self::CACHE_PUBLISHED, $rows, self::CACHE_TTL);

        return $rows;
    }

    public function getAllOrdered(): array
    {
        $cache = service('cache');
        $cached = $cache->get(self::CACHE_ALL_ORDERED);
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->orderBy('sortOrder', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        $cache->save(self::CACHE_ALL_ORDERED, $rows, self::CACHE_TTL);

        return $rows;
    }

    public function getNextSortOrder(): int
    {
        $row = $this->selectMax('sortOrder')->first();

        return ((int) ($row['sortOrder'] ?? 0)) + 1;
    }

    public function normalizeOrder(): void
    {
        foreach ($this->getAllOrdered() as $index => $faq) {
            $expected = $index + 1;
            if ((int) $faq['sortOrder'] !== $expected) {
                $this->update((int) $faq['id'], ['sortOrder' => $expected]);
            }
        }
    }

    protected function clearFaqCache(array $data): array
    {
        service('cache')->delete(self::CACHE_PUBLISHED);
        service('cache')->delete(self::CACHE_ALL_ORDERED);

        return $data;
    }
}
