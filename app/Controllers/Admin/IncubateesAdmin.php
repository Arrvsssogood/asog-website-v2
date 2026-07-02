<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageUpload;

/**
 * IncubateesAdmin — Full CRUD for the incubatees showcase.
 *
 * Routes: admin/incubatees, admin/incubatees/create, admin/incubatees/(:num)/edit, etc.
 */
class IncubateesAdmin extends BaseController
{
    private const INCUBATEE_LOGO_MAX_BYTES = 1048576; // 1 MB
    private const INCUBATEE_TEAM_PHOTO_MAX_BYTES = 10485760; // 10 MB

    /**
     * Build a map of published incubatee counts keyed by cohort name.
     *
     * @return array<string, int>
     */
    private function getPublishedCountsByCohort(): array
    {
        $counts = [];

        $rows = $this->incubateeModel
            ->select('cohort, COUNT(*) as total')
            ->where('isPublished', 1)
            ->groupBy('cohort')
            ->findAll();

        foreach ($rows as $row) {
            $name = trim((string) ($row['cohort'] ?? ''));
            if ($name === '') {
                continue;
            }

            $counts[$name] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    private function formatUploadSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            $mb = $bytes / 1048576;
            return rtrim(rtrim(number_format($mb, 1, '.', ''), '0'), '.') . ' MB';
        }

        if ($bytes >= 1024) {
            $kb = $bytes / 1024;
            return rtrim(rtrim(number_format($kb, 1, '.', ''), '0'), '.') . ' KB';
        }

        return $bytes . ' bytes';
    }

    private function assertSquareImage(\CodeIgniter\HTTP\Files\UploadedFile $file, string $label): void
    {
        $dimensions = @getimagesize($file->getTempName());
        if ($dimensions === false || ! isset($dimensions[0], $dimensions[1])) {
            throw new \RuntimeException($label . ' must be a valid image.');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        if ($width !== $height) {
            throw new \RuntimeException($label . ' must be square (1:1).');
        }
    }


    /**
     * List all incubatees in the admin panel.
     *
     * Retrieves incubatee records for the admin list view.
     */
    public function index()
    {
        $cohorts = $this->cohortModel->getAllSorted();
        $cohortStartupCounts = $this->getPublishedCountsByCohort();

        $data = [
            'pageTitle'   => 'Incubatees',
            'activePage'  => 'incubatees',
            'incubatees'  => $this->incubateeModel->orderBy('sortOrder', 'ASC')->orderBy('createdAt', 'DESC')->findAll(),
            'cohorts'     => $cohorts,
            'cohortStartupCounts' => $cohortStartupCounts,
        ];

        return view('admin/layout/header', $data)
             . view('admin/incubatees/index', $data)
             . view('admin/layout/footer');
    }

    /**
     * Persist the current incubatee order after drag-and-drop reordering.
     */
    public function saveOrder()
    {
        $orderedIds = $this->request->getPost('order');
        $cohort = trim((string) ($this->request->getPost('cohort') ?? 'all'));

        if (! is_array($orderedIds) || $orderedIds === []) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'No incubatee order received.',
            ]);
        }

        $normalizedIds = [];
        foreach (array_values($orderedIds) as $id) {
            $incubateeId = (int) $id;
            if ($incubateeId > 0) {
                $normalizedIds[] = $incubateeId;
            }
        }

        if ($normalizedIds === []) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'No valid incubatee order received.',
            ]);
        }

        $this->db->transStart();

        if ($cohort !== '' && strtolower($cohort) !== 'all') {
            $currentRows = $this->db->table('incubatees')
                ->select('id, sortOrder')
                ->where('cohort', $cohort)
                ->orderBy('sortOrder', 'ASC')
                ->orderBy('createdAt', 'DESC')
                ->get()
                ->getResultArray();

            $allowedIds = array_fill_keys(array_map(static fn ($row) => (int) $row['id'], $currentRows), true);
            $sortSlots = array_map(static fn ($row) => (int) $row['sortOrder'], $currentRows);
            $position = 0;

            foreach ($normalizedIds as $incubateeId) {
                if (! isset($allowedIds[$incubateeId], $sortSlots[$position])) {
                    continue;
                }

                $this->db->table('incubatees')
                    ->where('id', $incubateeId)
                    ->update(['sortOrder' => $sortSlots[$position]]);

                $position++;
            }
        } else {
            foreach ($normalizedIds as $index => $incubateeId) {
                $this->db->table('incubatees')
                    ->where('id', $incubateeId)
                    ->update(['sortOrder' => $index + 1]);
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Unable to save the new order.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
        ]);
    }

    public function create()
    {
        $cohorts = $this->cohortModel->getAllSorted();
        $data = [
            'pageTitle'       => 'New Incubatee',
            'activePage'      => 'incubatees',
            'existingCohorts' => $this->cohortModel->getActiveNames(),
            'allCohorts'      => $cohorts,
            'cohortStartupCounts' => $this->getPublishedCountsByCohort(),
            'logoUploadMaxBytes' => self::INCUBATEE_LOGO_MAX_BYTES,
            'logoUploadMaxLabel' => $this->formatUploadSize(self::INCUBATEE_LOGO_MAX_BYTES),
            'teamPhotoUploadMaxBytes' => self::INCUBATEE_TEAM_PHOTO_MAX_BYTES,
            'teamPhotoUploadMaxLabel' => $this->formatUploadSize(self::INCUBATEE_TEAM_PHOTO_MAX_BYTES),
        ];

        return view('admin/layout/header', $data)
             . view('admin/incubatees/form', $data)
             . view('admin/layout/footer');
    }

    /**
     * Store a new incubatee.
     *
     * Validates and transforms request data, handles logo uploads,
     * and inserts a new incubatee record.
     */


    public function store()
    {
        $content = trim($this->request->getPost('content') ?? '');
        
        // Quill sends <p><br></p> when editor is empty — treat as null
        if (in_array($content, ['', '<p><br></p>', '<p></p>'], true)) {
            $content = null;
        }

        // Build team members JSON from repeater inputs (with per-member photos)
        try {
            $teamMembers = $this->buildTeamMembersFromRequest();
        } catch (\RuntimeException $e) {
            setToast('error', $e->getMessage());
            return redirect()->back()->withInput();
        }

        $contacts = $this->buildContactsFromRequest();
        $primaryContact = $contacts[0] ?? ['person' => null, 'number' => null, 'email' => null];

        $data = [
            'companyName'      => trim($this->request->getPost('companyName') ?? ''),
            'shortDescription' => trim($this->request->getPost('shortDescription') ?? '') ?: null,
            'content'          => $content,
            'sdgNumbers'       => $this->normalizeSdgNumbers($this->request->getPost('sdgNumbers')),
            'websiteUrl'       => trim($this->request->getPost('websiteUrl') ?? '') ?: null,
            'facebookUrl'      => trim($this->request->getPost('facebookUrl') ?? '') ?: null,
            'contactDetails'   => ! empty($contacts) ? json_encode($contacts) : null,
            'contactName'      => $primaryContact['person'],
            'contactNumber'    => $primaryContact['number'],
            'contactEmail'     => $primaryContact['email'],
            'cohort'           => trim($this->request->getPost('cohort') ?? '') ?: null,
            'teamMembers'      => ! empty($teamMembers) ? json_encode($teamMembers) : null,
            'sortOrder'        => (int) ($this->request->getPost('sortOrder') ?: 0),
            'isPublished'      => $this->request->getPost('isPublished') ? 1 : 0,
        ];

        // This one generates a slug. A slug is a URL-friendly version of the company name, typically lowercase with words separated by hyphens. 
        $data['slug'] = $this->incubateeModel->generateSlug($data['companyName']);

        // Handle logo upload
        try {
            $file = $this->request->getFile('logo');

            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $file->isValid()) {
                    setToast('error', 'Logo upload failed: ' . $file->getErrorString());
                    return redirect()->back()->withInput();
                }

                if ($file->hasMoved()) {
                    setToast('error', 'Logo upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $path = $uploader->upload($file, 'incubatees', self::INCUBATEE_LOGO_MAX_BYTES);
                if ($path !== null) {
                    $data['logoPath'] = $path;
                } else {
                    setToast('error', 'Logo upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Incubatee logo upload error: ' . $e->getMessage());
            setToast('error', 'Logo upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        // Handle white logo upload
        try {
            $whiteFile = $this->request->getFile('logoWhite');

            if ($whiteFile !== null && $whiteFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $whiteFile->isValid()) {
                    setToast('error', 'White logo upload failed: ' . $whiteFile->getErrorString());
                    return redirect()->back()->withInput();
                }

                if ($whiteFile->hasMoved()) {
                    setToast('error', 'White logo upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $whitePath = $uploader->upload($whiteFile, 'incubatees', self::INCUBATEE_LOGO_MAX_BYTES);
                if ($whitePath !== null) {
                    $data['logoWhitePath'] = $whitePath;
                } else {
                    setToast('error', 'White logo upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Incubatee white logo upload error: ' . $e->getMessage());
            setToast('error', 'White logo upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        if (! $this->incubateeModel->insert($data)) {
            setToast('error', 'Validation failed: ' . implode(', ', $this->incubateeModel->errors()));
            return redirect()->back()->withInput();
        }

        $newId = (int) $this->incubateeModel->getInsertID();
        setToast('success', 'Incubatee saved successfully.');
        if ($newId > 0) {
            return redirect()->to(site_url('admin/incubatees/' . $newId . '/edit'));
        }
        return redirect()->back();
    }

    /**
     * EDIT — Show form for editing an existing incubatee.
     * This method retrieves the incubatee by ID and passes it to the form view for editing. If the incubatee is not found, it redirects back with an error message.  
     */
    public function edit(int $id)
    {
        $incubatee = $this->incubateeModel->find($id);

        if (! $incubatee) {
            setToast('error', 'Incubatee not found.');
            return redirect()->to(site_url('admin/incubatees'));
        }

        $cohorts = $this->cohortModel->getAllSorted();
        $data = [
            'pageTitle'       => 'Edit Incubatee',
            'activePage'      => 'incubatees',
            'incubatee'       => $incubatee,
            'existingCohorts' => $this->cohortModel->getActiveNames(),
            'allCohorts'      => $cohorts,
            'cohortStartupCounts' => $this->getPublishedCountsByCohort(),
            'logoUploadMaxBytes' => self::INCUBATEE_LOGO_MAX_BYTES,
            'logoUploadMaxLabel' => $this->formatUploadSize(self::INCUBATEE_LOGO_MAX_BYTES),
            'teamPhotoUploadMaxBytes' => self::INCUBATEE_TEAM_PHOTO_MAX_BYTES,
            'teamPhotoUploadMaxLabel' => $this->formatUploadSize(self::INCUBATEE_TEAM_PHOTO_MAX_BYTES),
        ];

        return view('admin/layout/header', $data)
             . view('admin/incubatees/form', $data)
             . view('admin/layout/footer');
    }

    /**
     * UPDATE — Handle form submission for updating an existing incubatee.
     * This method processes the form data, handles logo uploads, and updates the incubatee in the database. It includes error handling for validation and file uploads, and it also manages the deletion of old logo files if new ones are uploaded.   
     */
    public function update(int $id)
    {
        $incubatee = $this->incubateeModel->find($id);

        if (! $incubatee) {
            setToast('error', 'Incubatee not found.');
            return redirect()->to(site_url('admin/incubatees'));
        }

        $content = trim($this->request->getPost('content') ?? '');
        // Quill sends <p><br></p> when editor is empty — treat as null
        if (in_array($content, ['', '<p><br></p>', '<p></p>'], true)) {
            $content = null;
        }

        $oldTeamMembers = ! empty($incubatee['teamMembers'])
            ? (json_decode($incubatee['teamMembers'], true) ?: [])
            : [];

        // Build team members JSON from repeater inputs (with per-member photos)
        try {
            $teamMembers = $this->buildTeamMembersFromRequest();
        } catch (\RuntimeException $e) {
            setToast('error', $e->getMessage());
            return redirect()->back()->withInput();
        }

        $contacts = $this->buildContactsFromRequest();
        $primaryContact = $contacts[0] ?? ['person' => null, 'number' => null, 'email' => null];

        $data = [
            'companyName'      => trim($this->request->getPost('companyName') ?? ''),
            'shortDescription' => trim($this->request->getPost('shortDescription') ?? '') ?: null,
            'content'          => $content,
            'sdgNumbers'       => $this->normalizeSdgNumbers($this->request->getPost('sdgNumbers')),
            'websiteUrl'       => trim($this->request->getPost('websiteUrl') ?? '') ?: null,
            'facebookUrl'      => trim($this->request->getPost('facebookUrl') ?? '') ?: null,
            'contactDetails'   => ! empty($contacts) ? json_encode($contacts) : null,
            'contactName'      => $primaryContact['person'],
            'contactNumber'    => $primaryContact['number'],
            'contactEmail'     => $primaryContact['email'],
            'cohort'           => trim($this->request->getPost('cohort') ?? '') ?: null,
            'teamMembers'      => ! empty($teamMembers) ? json_encode($teamMembers) : null,
            'sortOrder'        => (int) ($this->request->getPost('sortOrder') ?: 0),
            'isPublished'      => $this->request->getPost('isPublished') ? 1 : 0,
        ];

        // Regenerate slug only if name changed
        if ($data['companyName'] !== ($incubatee['companyName'] ?? '')) {
            $data['slug'] = $this->incubateeModel->generateSlug($data['companyName'], $id);
        }

        // Handle logo upload (optional on edit)
        try {
            $file = $this->request->getFile('logo');

            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $file->isValid()) {
                    setToast('error', 'Logo upload failed: ' . $file->getErrorString());
                    return redirect()->back()->withInput();
                }

                if ($file->hasMoved()) {
                    setToast('error', 'Logo upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $path = $uploader->upload($file, 'incubatees', self::INCUBATEE_LOGO_MAX_BYTES);
                if ($path !== null) {
                    // Delete old logo
                    if (! empty($incubatee['logoPath'])) {
                        $uploader->delete($incubatee['logoPath']);
                    }
                    $data['logoPath'] = $path;
                } else {
                    setToast('error', 'Logo upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Incubatee logo update error: ' . $e->getMessage());
            setToast('error', 'Logo upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        // Handle white logo upload (optional on edit)
        try {
            $whiteFile = $this->request->getFile('logoWhite');

            if ($whiteFile !== null && $whiteFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $whiteFile->isValid()) {
                    setToast('error', 'White logo upload failed: ' . $whiteFile->getErrorString());
                    return redirect()->back()->withInput();
                }

                if ($whiteFile->hasMoved()) {
                    setToast('error', 'White logo upload error: file was already processed.');
                    return redirect()->back()->withInput();
                }

                $uploader = new ImageUpload();
                $whitePath = $uploader->upload($whiteFile, 'incubatees', self::INCUBATEE_LOGO_MAX_BYTES);
                if ($whitePath !== null) {
                    // Delete old white logo
                    if (! empty($incubatee['logoWhitePath'])) {
                        $uploader->delete($incubatee['logoWhitePath']);
                    }
                    $data['logoWhitePath'] = $whitePath;
                } else {
                    setToast('error', 'White logo upload failed: ' . $uploader->getError());
                    return redirect()->back()->withInput();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Incubatee white logo update error: ' . $e->getMessage());
            setToast('error', 'White logo upload error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        // Remove deleted/replaced team-member photos
        $oldPhotos = array_values(array_filter(array_map(static function ($m) {
            return trim((string) ($m['photo'] ?? ''));
        }, $oldTeamMembers)));
        $newPhotos = array_values(array_filter(array_map(static function ($m) {
            return trim((string) ($m['photo'] ?? ''));
        }, $teamMembers)));

        $removedPhotos = array_diff($oldPhotos, $newPhotos);
        if (! empty($removedPhotos)) {
            $uploader = new ImageUpload();
            foreach ($removedPhotos as $path) {
                $uploader->delete($path);
            }
        }

        // Use a clean DB builder for the update to avoid any residual
        // query-builder state from find() or generateSlug().
        $ok = $this->db->table('incubatees')->where('id', $id)->update($data);

        if (! $ok) {
            setToast('error', 'Update failed. Please try again.');
            return redirect()->back()->withInput();
        }

        setToast('success', 'Incubatee saved successfully.');
        return redirect()->to(site_url('admin/incubatees/' . $id . '/edit'));
    }

    // ──────────────────────────────────────────────
    // DELETE
    // ──────────────────────────────────────────────
    public function delete(int $id)
    {
        $incubatee = $this->incubateeModel->find($id);

        if (! $incubatee) {
            setToast('error', 'Incubatee not found.');
            return redirect()->to(site_url('admin/incubatees'));
        }

        // Delete logo files
        $uploader = new ImageUpload();
        if (! empty($incubatee['logoPath'])) {
            $uploader->delete($incubatee['logoPath']);
        }
        if (! empty($incubatee['logoWhitePath'])) {
            $uploader->delete($incubatee['logoWhitePath']);
        }
        if (! empty($incubatee['teamMembers'])) {
            $members = json_decode($incubatee['teamMembers'], true) ?: [];
            foreach ($members as $member) {
                $memberPhoto = trim((string) ($member['photo'] ?? ''));
                if ($memberPhoto !== '') {
                    $uploader->delete($memberPhoto);
                }
            }
        }

        $this->incubateeModel->delete($id);

        setToast('success', 'Incubatee deleted.');
        return redirect()->to(site_url('admin/incubatees'));
    }

    /**
     * Build team-members payload from repeater fields and uploads.
     *
     * Output format:
     * [
     *   ['name' => '...', 'role' => '...', 'photo' => 'uploads/...'],
     *   ...
     * ]
     *
     * @throws \RuntimeException
     */
    private function buildTeamMembersFromRequest(): array
    {
        $tmNames         = $this->request->getPost('tm_name') ?? [];
        $tmRoles         = $this->request->getPost('tm_role') ?? [];
        $tmPhotoExisting = $this->request->getPost('tm_photo_existing') ?? [];
        $tmPhotoFiles    = $this->request->getFileMultiple('tm_photo') ?? [];

        $uploader = new ImageUpload();
        $teamMembers = [];

        foreach ($tmNames as $i => $nameRaw) {
            $name = trim((string) $nameRaw);
            if ($name === '') {
                continue;
            }

            $role      = trim((string) ($tmRoles[$i] ?? ''));
            $photoPath = trim((string) ($tmPhotoExisting[$i] ?? ''));

            $photoFile = $tmPhotoFiles[$i] ?? null;
            if ($photoFile !== null && $photoFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $photoFile->isValid()) {
                    throw new \RuntimeException('Team member photo upload failed: ' . $photoFile->getErrorString());
                }

                if ($photoFile->hasMoved()) {
                    throw new \RuntimeException('Team member photo upload error: file was already processed.');
                }

                $this->assertSquareImage($photoFile, 'Founder photo');

                $uploaded = $uploader->upload($photoFile, 'incubatees/team', self::INCUBATEE_TEAM_PHOTO_MAX_BYTES);
                if ($uploaded === null) {
                    throw new \RuntimeException('Team member photo upload failed: ' . $uploader->getError());
                }

                $photoPath = $uploaded;
            }

            $teamMembers[] = [
                'name'  => $name,
                'role'  => $role,
                'photo' => $photoPath !== '' ? $photoPath : null,
            ];
        }

        return $teamMembers;
    }

    /**
     * Build contacts payload from repeater fields.
     *
     * Output format:
     * [
     *   ['person' => '...', 'number' => '...', 'email' => '...'],
     *   ...
     * ]
     */
    private function buildContactsFromRequest(): array
    {
        $persons = $this->request->getPost('contact_person') ?? [];
        $numbers = $this->request->getPost('contact_number') ?? [];
        $emails  = $this->request->getPost('contact_email') ?? [];

        if (! is_array($persons)) {
            $persons = [$persons];
        }
        if (! is_array($numbers)) {
            $numbers = [$numbers];
        }
        if (! is_array($emails)) {
            $emails = [$emails];
        }

        // Backward compatibility for older form payloads using single-value fields.
        if ($persons === [] && $numbers === [] && $emails === []) {
            $persons[] = $this->request->getPost('contactName') ?? '';
            $numbers[] = $this->request->getPost('contactNumber') ?? '';
            $emails[]  = $this->request->getPost('contactEmail') ?? '';
        }

        $max = max(count($persons), count($numbers), count($emails));
        $contacts = [];

        for ($i = 0; $i < $max; $i++) {
            $person = trim((string) ($persons[$i] ?? ''));
            $number = trim((string) ($numbers[$i] ?? ''));
            $email  = trim((string) ($emails[$i] ?? ''));

            if ($person === '' && $number === '' && $email === '') {
                continue;
            }

            $contacts[] = [
                'person' => $person !== '' ? $person : null,
                'number' => $number !== '' ? $number : null,
                'email'  => $email !== '' ? $email : null,
            ];
        }

        return $contacts;
    }

    /**
     * Normalize SDG checkbox values to CSV (example: "1,9,12").
     *
     * @param array|string|null $raw
     */
    private function normalizeSdgNumbers($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $values = is_array($raw) ? $raw : explode(',', (string) $raw);
        $numbers = [];

        foreach ($values as $value) {
            $id = (int) $value;
            if ($id >= 1 && $id <= 17) {
                $numbers[$id] = $id;
            }
        }

        if ($numbers === []) {
            return null;
        }

        ksort($numbers);
        return implode(',', array_values($numbers));
    }

    // ──────────────────────────────────────────────
    // COHORT MANAGEMENT (AJAX)
    // ──────────────────────────────────────────────

    public function addCohort()
    {
        $number = $this->cohortModel->nextNumber();
        $name   = 'Cohort ' . $number;

        if (! $this->cohortModel->insert(['name' => $name, 'number' => $number])) {
            return $this->response->setJSON(['ok' => false, 'error' => implode(', ', $this->cohortModel->errors())]);
        }

        $cohort = $this->cohortModel->find($this->cohortModel->getInsertID());
        return $this->response->setJSON(['ok' => true, 'cohort' => $cohort]);
    }

    public function deleteCohort(int $id)
    {
        $cohort = $this->cohortModel->find($id);

        if (! $cohort) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Cohort not found.']);
        }

        // Check if any incubatees use this cohort
        $count = $this->incubateeModel->where('cohort', $cohort['name'])->countAllResults();
        if ($count > 0) {
            return $this->response->setJSON(['ok' => false, 'error' => $cohort['name'] . ' has ' . $count . ' incubatee(s). Remove them first.']);
        }

        $this->cohortModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }
}
