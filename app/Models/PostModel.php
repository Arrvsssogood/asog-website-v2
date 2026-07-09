<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PostModel - handles CRUD operations for the posts table.
 */
class PostModel extends Model
{
    protected $table         = 'posts';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'createdAt';
    protected $updatedField  = 'updatedAt';

    protected $allowedFields = [
        'title',
        'slug',
        'shortDescription',
        'content',
        'category',
        'sortOrder',
        'imagePath',
        'isPublished',
        'isFeatured',
        'authorName',
        'publishedAt',
    ];

    protected $returnType = 'array';

    protected ?bool $supportsSortOrderCache = null;
    protected ?bool $supportsSlugHistoryCache = null;

    // Validation
    protected $validationRules = [
        'title'            => 'required|min_length[3]|max_length[255]',
        'shortDescription' => 'permit_empty|max_length[500]',
        'content'          => 'permit_empty',
        'category'         => 'required|in_list[news,events,features]',
        'sortOrder'        => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'A post title is required.',
            'min_length' => 'Title must be at least 3 characters.',
        ],
        'category' => [
            'in_list' => 'Category must be one of: news, events, features.',
        ],
    ];

    /**
     * Return summary counts for the dashboard.
     */
    public function getCounts(): array
    {
        $total     = $this->countAllResults();
        $published = $this->where('isPublished', 1)->countAllResults();
        $drafts    = $this->where('isPublished', 0)->countAllResults();
        $featured  = $this->where('isFeatured', 1)->countAllResults();

        return compact('total', 'published', 'drafts', 'featured');
    }

    /**
     * Return only published posts, newest first.
     */
    public function getPublished(int $limit = 0)
    {
        $builder = $this->where('isPublished', 1)
                        ->orderBy('publishedAt', 'DESC');

        return $limit > 0 ? $builder->findAll($limit) : $builder->findAll();
    }

    /**
     * Return published posts filtered by category.
     */
    public function getByCategory(string $category, int $limit = 0)
    {
        $builder = $this->where('isPublished', 1)
                        ->where('category', $category)
                        ->orderBy('publishedAt', 'DESC');

        return $limit > 0 ? $builder->findAll($limit) : $builder->findAll();
    }

    public function getPublishedPage(array $categories = [], string $sort = 'newest', int $perPage = 10, int $page = 1): array
    {
        $builder = $this->where('isPublished', 1);

        if (!empty($categories)) {
            $builder->whereIn('category', $categories);
        }

        $builder->orderBy('publishedAt', $sort === 'oldest' ? 'ASC' : 'DESC');

        $total  = $builder->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $posts  = $builder->findAll($perPage, $offset);

        return [
            'posts'   => $posts,
            'total'   => $total,
            'perPage' => $perPage,
            'page'    => $page,
            'pages'   => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Return the single featured post (lowest sortOrder first, then newest).
     */
    public function getFeatured(): ?array
    {
        $builder = $this->where('isPublished', 1)
                        ->where('isFeatured', 1);

        if ($this->supportsSortOrder()) {
            $builder->orderBy('sortOrder', 'ASC');
        }

        return $builder->orderBy('publishedAt', 'DESC')
                       ->first();
    }

    /**
     * Return up to $limit published+featured posts with a cover image.
     * Used by the landing page hero slideshow.
     */
    public function getFeaturedSlides(int $limit = 5): array
    {
        $builder = $this->where('isPublished', 1)
                        ->where('isFeatured', 1)
                        ->where('imagePath !=', '')
                        ->where('imagePath IS NOT NULL', null, false);

        if ($this->supportsSortOrder()) {
            $builder->orderBy('sortOrder', 'ASC');
        }

        return $builder->orderBy('publishedAt', 'DESC')
                       ->findAll($limit);
    }

    /**
     * Return latest published posts for generic hero fallback.
     */
    public function getHeroSlides(int $limit = 5): array
    {
        return $this->where('isPublished', 1)
                    ->orderBy('publishedAt', 'DESC')
                    ->findAll($limit);
    }

    /**
     * Return posts in admin-friendly order.
     */
    public function getAdminList(int $perPage = 10, int $offset = 0): array
    {
        $builder = $this;

        if ($this->supportsSortOrder()) {
            $builder = $builder->orderBy('sortOrder', 'ASC');
        }

        return $builder->orderBy('createdAt', 'DESC')->findAll($perPage, $offset);
    }

    public function getAdminPage(string $search = '', string $status = 'all', string $category = 'all', int $perPage = 10, int $page = 1, string $sort = 'default'): array
    {
        $search = trim($search);
        $status = in_array($status, ['all', 'published', 'draft', 'featured'], true) ? $status : 'all';
        $category = in_array($category, ['all', 'news', 'events', 'features'], true) ? $category : 'all';
        $sort = in_array($sort, ['default', 'date_desc', 'date_asc'], true) ? $sort : 'default';
        $page = max(1, $page);

        $builder = $this->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('shortDescription', $search)
                ->orLike('category', $search)
                ->orLike('authorName', $search)
                ->orLike('slug', $search)
                ->groupEnd();
        }

        if ($status === 'published') {
            $builder->where('isPublished', 1);
        } elseif ($status === 'draft') {
            $builder->where('isPublished', 0);
        } elseif ($status === 'featured') {
            $builder->where('isFeatured', 1);
        }

        if ($category !== 'all') {
            $builder->where('category', $category);
        }

        $total = $builder->countAllResults(false);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        if ($sort === 'date_desc' || $sort === 'date_asc') {
            $builder->orderBy('COALESCE(publishedAt, createdAt)', $sort === 'date_asc' ? 'ASC' : 'DESC', false);
        } elseif ($this->supportsSortOrder()) {
            $builder->orderBy('sortOrder', 'ASC');
        }

        $posts = $builder
            ->orderBy('createdAt', 'DESC')
            ->get($perPage, $offset)
            ->getResultArray();

        return [
            'posts' => $posts,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ];
    }

    /**
     * Find a post by its slug.
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
                    ->where('isPublished', 1)
                    ->first();
    }

    /**
     * Resolve an old slug to the currently published post slug.
     */
    public function resolveSlugRedirect(string $slug): ?array
    {
        if (! $this->supportsSlugHistory()) {
            return null;
        }

        $row = $this->db->table('post_slug_history')
            ->select('posts.id, posts.slug, posts.title')
            ->join('posts', 'posts.id = post_slug_history.postId', 'inner')
            ->where('post_slug_history.oldSlug', $slug)
            ->where('posts.isPublished', 1)
            ->get()
            ->getFirstRow('array');

        return $row ?: null;
    }

    /**
     * Store an old slug so legacy URLs can still redirect.
     */
    public function rememberSlugHistory(int $postId, string $slug): bool
    {
        $slug = trim($slug);

        if ($slug === '' || ! $this->supportsSlugHistory()) {
            return false;
        }

        $existing = $this->db->table('post_slug_history')
            ->select('id')
            ->where('oldSlug', $slug)
            ->countAllResults();

        if ($existing > 0) {
            return false;
        }

        return (bool) $this->db->table('post_slug_history')->insert([
            'postId'    => $postId,
            'oldSlug'   => $slug,
            'createdAt' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a post while preserving the old slug for redirects when it changes.
     */
    public function updateWithSlugHistory(int $id, array $data, ?string $previousSlug = null): bool
    {
        $this->db->transBegin();

        try {
            $newSlug = isset($data['slug']) ? trim((string) $data['slug']) : '';
            $previousSlug = trim((string) ($previousSlug ?? ''));

            if ($previousSlug !== '' && $newSlug !== '' && $newSlug !== $previousSlug) {
                $this->rememberSlugHistory($id, $previousSlug);
            }

            $updated = $this->update($id, $data);

            if (! $updated || $this->db->transStatus() === false) {
                $this->db->transRollback();
                return false;
            }

            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Clear the featured flag on all posts.
     */
    public function clearFeatured(?int $excludeId = null): void
    {
        $builder = $this->builder();
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        $builder->set('isFeatured', 0)->update();
    }

    /**
     * Generate a URL-safe slug from the title, ensuring uniqueness.
     */
    public function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = url_title($title, '-', true);

        if ($this->isSlugTaken($slug, $excludeId)) {
            $slug .= '-' . time();
        }

        return $slug;
    }

    /**
     * Detect whether a slug is already used by a post or reserved by history.
     */
    protected function isSlugTaken(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->db->table($this->table)->select('id')->where('slug', $slug);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        if ($builder->countAllResults() > 0) {
            return true;
        }

        if (! $this->supportsSlugHistory()) {
            return false;
        }

        return $this->db->table('post_slug_history')
            ->select('id')
            ->where('oldSlug', $slug)
            ->countAllResults() > 0;
    }

    /**
     * Detect whether posts.sortOrder exists in the current database.
     */
    public function supportsSortOrder(): bool
    {
        if ($this->supportsSortOrderCache !== null) {
            return $this->supportsSortOrderCache;
        }

        try {
            $this->supportsSortOrderCache = $this->db->fieldExists('sortOrder', $this->table);
        } catch (\Throwable $e) {
            $this->supportsSortOrderCache = false;
        }

        return $this->supportsSortOrderCache;
    }

    /**
     * Detect whether the slug history table exists.
     */
    public function supportsSlugHistory(): bool
    {
        if ($this->supportsSlugHistoryCache !== null) {
            return $this->supportsSlugHistoryCache;
        }

        try {
            $this->supportsSlugHistoryCache = $this->db->tableExists('post_slug_history');
        } catch (\Throwable $e) {
            $this->supportsSlugHistoryCache = false;
        }

        return $this->supportsSlugHistoryCache;
    }
}
