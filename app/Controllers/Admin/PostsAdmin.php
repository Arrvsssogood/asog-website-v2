<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageUpload;

/**
 * PostsAdmin — Full CRUD for the blog/posts CMS.
 *
 * Routes: admin/posts, admin/posts/create, admin/posts/(:num)/edit, etc.
 */
class PostsAdmin extends BaseController
{

    /**
     * Controller for blog post management in the admin panel.
     * Handles listing, creating, editing, updating, and deleting posts.
     * Also manages image uploads and post slugs.
     */
    public function index()
    {
        $perPage    = 5;
        $search     = trim((string) ($this->request->getGet('search') ?? ''));
        $status     = trim((string) ($this->request->getGet('status') ?? 'all'));
        $status     = in_array($status, ['all', 'published', 'draft', 'featured'], true) ? $status : 'all';
        $category   = trim((string) ($this->request->getGet('category') ?? 'all'));
        $category   = in_array($category, ['all', 'news', 'events', 'features'], true) ? $category : 'all';
        $sort       = trim((string) ($this->request->getGet('sort') ?? 'default'));
        $sort       = in_array($sort, ['default', 'date_desc', 'date_asc'], true) ? $sort : 'default';
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $result     = $this->postModel->getAdminPage($search, $status, $category, $perPage, $page, $sort);

        $featuredStories = $this->postModel->where('isFeatured', 1);
        if ($this->postModel->supportsSortOrder()) {
            $featuredStories->orderBy('sortOrder', 'ASC');
        }
        $featuredStories = $featuredStories->orderBy('createdAt', 'DESC')->findAll();

        $data = [
            'pageTitle'         => 'Posts',
            'activePage'        => 'posts',
            'supportsSortOrder' => $this->postModel->supportsSortOrder(),
            'posts'             => $result['posts'],
            'featuredStories'   => $featuredStories,
            'currentPage'       => $result['currentPage'],
            'totalPages'        => $result['totalPages'],
            'total'             => $result['total'],
            'perPage'           => $result['perPage'],
            'search'            => $search,
            'status'            => $status,
            'category'          => $category,
            'sort'              => $sort,
        ];

        return view('admin/layout/header', $data)
             . view('admin/posts/index', $data)
             . view('admin/layout/footer');
    }

    /**
     * Handle inline image upload from the Quill editor.
     *
     * Accepts a single image file via AJAX POST, saves it to
     * public/uploads/posts/, and returns the URL as JSON.
     **/
    public function uploadImage()
    {
        $file = $this->request->getFile('image');

        if ($file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'No valid image uploaded.']);
        }

        $uploader = new ImageUpload();
        $path = $uploader->upload($file, 'posts');

        if ($path === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => $uploader->getError()]);
        }

        return $this->response->setJSON([
            'url' => site_url($path),
        ]);
    }


    public function create()
    {
        $data = [
            'pageTitle'  => 'New Post',
            'activePage' => 'posts',
            'supportsSortOrder' => $this->postModel->supportsSortOrder(),
        ];

        return view('admin/layout/header', $data)
             . view('admin/posts/form', $data)
             . view('admin/layout/footer');
    }

    public function store()
    {
        $slugInput = trim((string) $this->request->getPost('slug'));
        $action = $this->request->getPost('action');

        $data = [
            'title'            => $this->request->getPost('title'),
            'slug'             => '',
            'shortDescription' => $this->request->getPost('shortDescription'),
            'content'          => $this->request->getPost('content'),
            'category'         => $this->request->getPost('category'),
            'isPublished'      => $action === 'publish' ? 1 : 0,
            'isFeatured'       => $this->request->getPost('isFeatured') ? 1 : 0,
            'authorName'       => $this->request->getPost('authorName') ?: 'ASOG TBI',
        ];

        $sortOrderInput = $this->request->getPost('sortOrder');
        if ($this->postModel->supportsSortOrder() && $sortOrderInput !== null && $sortOrderInput !== '') {
            $data['sortOrder'] = (int) $sortOrderInput;
        }

        // Use the editor-provided slug when available, otherwise generate one from the title.
        $data['slug'] = $slugInput !== ''
            ? $this->postModel->generateSlug($slugInput)
            : $this->postModel->generateSlug($data['title']);

        // Set publishedAt — use custom date if provided, otherwise default to now
        $customDate = $this->request->getPost('publishedAt');
        if ($data['isPublished']) {
            $data['publishedAt'] = !empty($customDate)
                ? $customDate . ' ' . date('H:i:s')
                : date('Y-m-d H:i:s');
        } elseif (!empty($customDate)) {
            $data['publishedAt'] = $customDate . ' ' . date('H:i:s');
        }

        // Handle image upload
        try {
            $file = $this->request->getFile('image');

            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                
                // A file was submitted — check if PHP accepted it
                if (! $file->isValid()) {
                    $phpError = $file->getErrorString();
                    setToast('error', 'Image upload failed: ' . $phpError);
                    return redirect()->back()->withInput();
                }

                if ($file->hasMoved()) {
                    setToast('error', 'Image upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $path = $uploader->upload($file, 'posts');
                if ($path !== null) {
                    $data['imagePath'] = $path;

                } else {
                    setToast('error', 'Image upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            setToast('error', 'Image upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        if (! $this->postModel->insert($data)) {
            setToast('error', 'Validation failed: ' . implode(', ', $this->postModel->errors()));
            return redirect()->back()->withInput();
        }

        setToast(
            'success',
            $action === 'publish'
                ? 'Post published successfully.'
                : 'Draft saved successfully.'
        );

        return redirect()->to(site_url('admin/posts'));
    }

    public function edit(int $id)
    {
        $post = $this->postModel->find($id);

        if (! $post) {
            setToast('error', 'Post not found.');
            return redirect()->to(site_url('admin/posts'));
        }

        $data = [
            'pageTitle'  => 'Edit Post',
            'activePage' => 'posts',
            'post'       => $post,
            'supportsSortOrder' => $this->postModel->supportsSortOrder(),
        ];

        return view('admin/layout/header', $data)
             . view('admin/posts/form', $data)
             . view('admin/layout/footer');
    }


    public function update(int $id)
    {
        $post = $this->postModel->find($id);

        if (! $post) {
            setToast('error', 'Post not found.');
            return redirect()->to(site_url('admin/posts'));
        }

        $slugInput = trim((string) $this->request->getPost('slug'));
        $action = $this->request->getPost('action');

        $data = [
            'title'            => $this->request->getPost('title'),
            'slug'             => '',
            'shortDescription' => $this->request->getPost('shortDescription'),
            'content'          => $this->request->getPost('content'),
            'category'         => $this->request->getPost('category'),
            'isPublished'      => $action === 'publish' ? 1 : 0,
            'isFeatured'       => $this->request->getPost('isFeatured') ? 1 : 0,
            'authorName'       => $this->request->getPost('authorName') ?: 'ASOG TBI',
        ];

        $sortOrderInput = $this->request->getPost('sortOrder');
        if ($this->postModel->supportsSortOrder() && $sortOrderInput !== null && $sortOrderInput !== '') {
            $data['sortOrder'] = (int) $sortOrderInput;
        }

        // Keep the current slug unless the editor provides a new one.
        if ($slugInput !== '') {
            $data['slug'] = $this->postModel->generateSlug($slugInput, $id);
        } elseif (! empty($post['slug'])) {
            $data['slug'] = $post['slug'];
        } else {
            $data['slug'] = $this->postModel->generateSlug($data['title'], $id);
        }

        // Set publishedAt — use custom date if provided
        $customDate = $this->request->getPost('publishedAt');
        if (!empty($customDate)) {
            $data['publishedAt'] = $customDate . ' ' . date('H:i:s');
        } elseif ($data['isPublished'] && empty($post['publishedAt'])) {
            $data['publishedAt'] = date('Y-m-d H:i:s');
        }

        // Handle image upload (optional on edit)
        try {
            $file = $this->request->getFile('image');

            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                
                // A file was submitted — check if PHP accepted it
                if (! $file->isValid()) {
                    $phpError = $file->getErrorString();
                    setToast('error', 'Image upload failed: ' . $phpError);
                    return redirect()->back()->withInput();
                }

                if ($file->hasMoved()) {
                    setToast('error', 'Image upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $path = $uploader->upload($file, 'posts');
                if ($path !== null) {
                    
                    // Delete old image
                    if (! empty($post['imagePath'])) {
                        $uploader->delete($post['imagePath']);
                    }
                    $data['imagePath'] = $path;

                } else {
                    setToast('error', 'Image upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            setToast('error', 'Image upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        if (! $this->postModel->updateWithSlugHistory($id, $data, (string) ($post['slug'] ?? ''))) {
            setToast('error', 'Validation failed: ' . implode(', ', $this->postModel->errors()));
            return redirect()->back()->withInput();
        }

        setToast(
            'success',
            $action === 'publish'
                ? 'Post updated successfully.'
                : 'Draft updated successfully.'
        );

        return redirect()->to(site_url('admin/posts'));
    }

    /**
     * Delete a post
     */
    public function delete(int $id)
    {
        $post = $this->postModel->find($id);

        if (! $post) {
            setToast('error', 'Post not found.');
            return redirect()->to(site_url('admin/posts'));
        }

        // Delete image file
        if (! empty($post['imagePath'])) {
            (new ImageUpload())->delete($post['imagePath']);
        }

        $this->postModel->delete($id);

        setToast('success', 'Post deleted.');
        return redirect()->to(site_url('admin/posts'));
    }

    /**
     * Save featured post order from admin modal drag-and-drop list.
     */
    public function saveFeaturedOrder()
    {
        if (! $this->postModel->supportsSortOrder()) {
            setToast('error', 'Featured ordering is unavailable because posts.sortOrder is missing.');
            return redirect()->to(site_url('admin/posts'));
        }

        $rawOrder = $this->request->getPost('featuredOrder');
        if (! is_array($rawOrder)) {
            $rawOrder = [];
        }

        $ids = [];
        foreach ($rawOrder as $value) {
            $id = (int) $value;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            setToast('error', 'No featured stories were provided.');
            return redirect()->to(site_url('admin/posts'));
        }

        $currentFeatured = $this->postModel
            ->where('isFeatured', 1)
            ->findAll();

        $currentFeaturedIds = array_map(static function (array $post): int {
            return (int) $post['id'];
        }, $currentFeatured);

        // Keep any currently featured post not present in the submitted list at the end.
        foreach ($currentFeaturedIds as $featuredId) {
            if (! in_array($featuredId, $ids, true)) {
                $ids[] = $featuredId;
            }
        }

        $position = 0;
        foreach ($ids as $postId) {
            $post = $this->postModel->find($postId);
            if (! $post || (int) ($post['isFeatured'] ?? 0) !== 1) {
                continue;
            }

            $this->postModel->update($postId, ['sortOrder' => $position]);
            $position++;
        }

        setToast('success', 'Featured stories order updated.');
        return redirect()->to(site_url('admin/posts'));
    }

}
