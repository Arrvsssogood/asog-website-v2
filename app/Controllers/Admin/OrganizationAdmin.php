<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageUpload;
use App\Models\OrganizationMemberModel;

class OrganizationAdmin extends BaseController
{
    private const PHOTO_MAX_BYTES = 2097152; // 2 MB

    public function saveOrder()
    {
        $section = $this->sanitizeSection((string) $this->request->getPost('section'));
        $category = $this->sanitizeMentorCategory((string) $this->request->getPost('category'), $section);
        $orderedIds = $this->request->getPost('order');

        if (! is_array($orderedIds) || $orderedIds === []) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'No member order received.',
            ]);
        }

        $memberModel = $this->memberModel();
        $members = $memberModel->getBySection(
            $section,
            $section === OrganizationMemberModel::SECTION_MENTOR ? $category : null
        );

        $allowed = [];
        foreach ($members as $m) {
            $allowed[(int) ($m['id'] ?? 0)] = $m;
        }

        $featuredIds = [];
        if ($section !== OrganizationMemberModel::SECTION_MENTOR) {
            foreach ($members as $m) {
                if (! empty($m['isFeatured'])) {
                    $featuredIds[] = (int) $m['id'];
                }
            }
        }
        $featuredSet = array_fill_keys($featuredIds, true);

        $normalized = [];
        foreach (array_values($orderedIds) as $id) {
            $mid = (int) $id;
            if ($mid <= 0) {
                continue;
            }
            if (! isset($allowed[$mid])) {
                continue;
            }
            if (isset($featuredSet[$mid])) {
                continue; // featured are pinned (not reorderable)
            }
            $normalized[] = $mid;
        }

        // Preserve current featured ordering at the top, then apply drag order for remaining items.
        $finalOrder = array_values(array_merge($featuredIds, $normalized));

        $this->db->transStart();

        foreach ($finalOrder as $index => $mid) {
            $this->db->table('organization_members')
                ->where('id', $mid)
                ->update(['sortOrder' => $index + 1]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Unable to save the new order.',
            ]);
        }

        $memberModel->clearPublicCache();

        return $this->response->setJSON([
            'ok' => true,
        ]);
    }

    private function memberModel(): OrganizationMemberModel
    {
        return new OrganizationMemberModel();
    }

    public function index()
    {
        $memberModel = $this->memberModel();
        $activeSection = trim((string) ($this->request->getGet('section') ?? OrganizationMemberModel::SECTION_CORE_TEAM));

        if (! in_array($activeSection, OrganizationMemberModel::SECTIONS, true)) {
            $activeSection = OrganizationMemberModel::SECTION_CORE_TEAM;
        }

        $activeMentorCategory = '';
        if ($activeSection === OrganizationMemberModel::SECTION_MENTOR) {
            $activeMentorCategory = trim((string) ($this->request->getGet('category') ?? ''));
            if ($activeMentorCategory !== '' && ! in_array($activeMentorCategory, OrganizationMemberModel::MENTOR_CATEGORIES, true)) {
                $activeMentorCategory = '';
            }
        }

        $modalMode = trim((string) ($this->request->getGet('modal') ?? ''));
        if (! in_array($modalMode, ['add', 'edit'], true)) {
            $modalMode = '';
        }

        $modalMemberId = (int) ($this->request->getGet('memberId') ?? 0);
        $modalMember = null;
        if ($modalMode === 'edit' && $modalMemberId > 0) {
            $modalMember = $memberModel->find($modalMemberId);
            if (! $modalMember) {
                $modalMode = '';
            }
        }

        $isModalOpen = $modalMode !== '';

        $membersBySection = [];
        foreach (OrganizationMemberModel::SECTIONS as $section) {
            $membersBySection[$section] = $memberModel->getBySection($section);
        }

        $data = [
            'pageTitle'           => 'Organization',
            'activePage'          => 'organization',
            'activeSection'       => $activeSection,
            'activeMentorCategory'=> $activeMentorCategory,
            'modalMode'           => $modalMode,
            'modalMember'         => $modalMember,
            'isModalOpen'         => $isModalOpen,
            'membersBySection'    => $membersBySection,
            'mentorGroups'        => $memberModel->getMentorsGroupedForAdmin(),
            'sectionLabels'       => array_combine(
                OrganizationMemberModel::SECTIONS,
                array_map([OrganizationMemberModel::class, 'sectionLabel'], OrganizationMemberModel::SECTIONS)
            ),
            'defaultSection'      => $modalMode === 'edit' && is_array($modalMember)
                ? (string) ($modalMember['section'] ?? $activeSection)
                : $activeSection,
            'defaultCategory'     => $modalMode === 'edit' && is_array($modalMember)
                ? (string) ($modalMember['mentorCategory'] ?? '')
                : $activeMentorCategory,
            'mentorCategories'    => OrganizationMemberModel::MENTOR_CATEGORIES,
        ];

        return view('admin/layout/header', $data)
            . view('admin/organization/index', $data)
            . view('admin/layout/footer');
    }

    public function create()
    {
        $section = trim((string) ($this->request->getGet('section') ?? OrganizationMemberModel::SECTION_CORE_TEAM));
        if (! in_array($section, OrganizationMemberModel::SECTIONS, true)) {
            $section = OrganizationMemberModel::SECTION_CORE_TEAM;
        }

        $defaultCategory = trim((string) ($this->request->getGet('category') ?? ''));
        if ($section !== OrganizationMemberModel::SECTION_MENTOR) {
            $defaultCategory = '';
        } elseif ($defaultCategory !== '' && ! in_array($defaultCategory, OrganizationMemberModel::MENTOR_CATEGORIES, true)) {
            $defaultCategory = '';
        }

        return redirect()->to($this->organizationListUrl($section, $defaultCategory, [
            'modal' => 'add',
        ]));
    }

    public function modalCreate()
    {
        $section = $this->sanitizeSection((string) ($this->request->getGet('section') ?? OrganizationMemberModel::SECTION_CORE_TEAM));
        $category = $this->sanitizeMentorCategory((string) ($this->request->getGet('category') ?? ''), $section);

        return $this->response->setBody($this->renderMemberModal([
            'mode' => 'add',
            'member' => null,
            'defaultSection' => $section,
            'defaultCategory' => $category,
            'submitUrl' => site_url('admin/organization/modal'),
            'errors' => [],
            'formData' => [],
        ]));
    }

    public function modalEdit(int $id)
    {
        $member = $this->memberModel()->find($id);
        if (! is_array($member)) {
            return $this->response->setStatusCode(404)->setBody('Member not found.');
        }

        $updatedAt = (string) ($member['updatedAt'] ?? '');
        $since = trim((string) ($this->request->getGet('since') ?? ''));
        if ($since !== '' && $since === $updatedAt) {
            return $this->response->setStatusCode(204);
        }

        return $this->response
            ->setHeader('X-Member-Updated-At', $updatedAt)
            ->setBody($this->renderMemberModal([
                'mode' => 'edit',
                'member' => $member,
                'defaultSection' => (string) ($member['section'] ?? OrganizationMemberModel::SECTION_CORE_TEAM),
                'defaultCategory' => (string) ($member['mentorCategory'] ?? ''),
                'submitUrl' => site_url('admin/organization/modal/' . $id),
                'errors' => [],
                'formData' => [],
            ]));
    }

    public function store()
    {
        $memberModel = $this->memberModel();
        $payload = $this->memberPayload();

        try {
            $photoPath = $this->handlePhotoUpload();
            if ($photoPath !== null) {
                $payload['photoPath'] = $photoPath;
            }
        } catch (\RuntimeException $e) {
            setToast('error', $e->getMessage());
            return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null, [
                'modal' => 'add',
            ]))->withInput();
        }

        $payload['sortOrder'] = $memberModel->getNextSortOrder(
            $payload['section'],
            $payload['section'] === OrganizationMemberModel::SECTION_MENTOR ? ($payload['mentorCategory'] ?? null) : null
        );

        if (! $memberModel->insert($payload)) {
            setToast('error', $this->validationMessage($memberModel));
            return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null, [
                'modal' => 'add',
            ]))->withInput();
        }

        $memberId = (int) $memberModel->getInsertID();
        $this->clearFeaturedPeers($memberModel, $payload, $memberId);

        setToast('success', 'Member added.');

        return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null));
    }

    public function edit(int $id)
    {
        $memberModel = $this->memberModel();
        $member = $memberModel->find($id);
        if (! $member) {
            setToast('error', 'Member not found.');
            return redirect()->to(site_url('admin/organization'));
        }

        return redirect()->to($this->organizationListUrl(
            (string) $member['section'],
            (string) ($member['mentorCategory'] ?? ''),
            ['modal' => 'edit', 'memberId' => $id]
        ));
    }

    public function modalStore()
    {
        $memberModel = $this->memberModel();
        $payload = $this->memberPayload();

        try {
            $photoPath = $this->handlePhotoUpload();
            if ($photoPath !== null) {
                $payload['photoPath'] = $photoPath;
            }
        } catch (\RuntimeException $e) {
            return $this->modalErrorResponse('add', null, $payload, ['photo' => $e->getMessage()]);
        }

        $payload['sortOrder'] = $memberModel->getNextSortOrder(
            $payload['section'],
            $payload['section'] === OrganizationMemberModel::SECTION_MENTOR ? ($payload['mentorCategory'] ?? null) : null
        );

        if (! $memberModel->insert($payload)) {
            return $this->modalErrorResponse('add', null, $payload, $memberModel->errors());
        }

        $memberId = (int) $memberModel->getInsertID();
        $this->clearFeaturedPeers($memberModel, $payload, $memberId);

        return $this->modalSuccessResponse($memberId, 'Member added.', 'insert');
    }

    public function modalUpdate(int $id)
    {
        $memberModel = $this->memberModel();
        $member = $memberModel->find($id);
        if (! is_array($member)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Member not found.',
            ]);
        }

        $payload = $this->memberPayload();

        // Edit modal should not allow changing the section.
        // We lock section to the current member to prevent accidental/invalid relocation.
        $payload['section'] = (string) ($member['section'] ?? OrganizationMemberModel::SECTION_CORE_TEAM);
        if ($payload['section'] !== OrganizationMemberModel::SECTION_MENTOR) {
            $payload['mentorCategory'] = null;
        }

        try {
            $photoPath = $this->handlePhotoUpload($member['photoPath'] ?? null);
            if ($photoPath !== null) {
                $payload['photoPath'] = $photoPath;
            }
        } catch (\RuntimeException $e) {
            return $this->modalErrorResponse('edit', $member, $payload, ['photo' => $e->getMessage()], $id);
        }

        $previousLocation = [
            'section' => (string) ($member['section'] ?? ''),
            'category' => (string) ($member['mentorCategory'] ?? ''),
        ];

        // If mentorCategory changes, treat it as a relocation within the mentor list.
        $relocated = $previousLocation['section'] !== $payload['section']
            || (
                $payload['section'] === OrganizationMemberModel::SECTION_MENTOR
                && $previousLocation['category'] !== ($payload['mentorCategory'] ?? '')
            );

        // When relocating, ensure correct ordering in the target list.
        if ($relocated) {
            $payload['sortOrder'] = $memberModel->getNextSortOrder(
                $payload['section'],
                $payload['section'] === OrganizationMemberModel::SECTION_MENTOR ? ($payload['mentorCategory'] ?? null) : null
            );
        }

        if (! $memberModel->update($id, $payload)) {
            return $this->modalErrorResponse('edit', $member, $payload, $memberModel->errors(), $id);
        }

        $this->clearFeaturedPeers($memberModel, $payload, $id);

        return $this->modalSuccessResponse(
            $id,
            'Member updated.',
            $relocated ? 'relocate' : 'update',
            $relocated ? $previousLocation : null
        );
    }

    public function update(int $id)
    {
        $memberModel = $this->memberModel();
        $member = $memberModel->find($id);
        if (! $member) {
            setToast('error', 'Member not found.');
            return redirect()->to(site_url('admin/organization'));
        }

        $payload = $this->memberPayload();

        try {
            $photoPath = $this->handlePhotoUpload($member['photoPath'] ?? null);
            if ($photoPath !== null) {
                $payload['photoPath'] = $photoPath;
            }
        } catch (\RuntimeException $e) {
            setToast('error', $e->getMessage());
            return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null, [
                'modal' => 'edit',
                'memberId' => $id,
            ]))->withInput();
        }

        if (! $memberModel->update($id, $payload)) {
            setToast('error', $this->validationMessage($memberModel));
            return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null, [
                'modal' => 'edit',
                'memberId' => $id,
            ]))->withInput();
        }

        $this->clearFeaturedPeers($memberModel, $payload, $id);

        setToast('success', 'Member updated.');

        return redirect()->to($this->organizationListUrl($payload['section'], $payload['mentorCategory'] ?? null));
    }

    public function delete(int $id)
    {
        $memberModel = $this->memberModel();
        $member = $memberModel->find($id);
        if (! $member) {
            setToast('error', 'Member not found.');
            return redirect()->to(site_url('admin/organization'));
        }

        $section = (string) $member['section'];
        $mentorCategory = $section === OrganizationMemberModel::SECTION_MENTOR
            ? ($member['mentorCategory'] ?? null)
            : null;

        if (! $memberModel->delete($id)) {
            setToast('error', 'Unable to delete member.');
            return redirect()->to($this->organizationListUrl($section, $mentorCategory));
        }

        $this->deleteUploadedPhoto($member['photoPath'] ?? null);
        $memberModel->normalizeOrder($section, $mentorCategory);

        setToast('success', 'Member deleted.');

        return redirect()->to($this->organizationListUrl($section, $mentorCategory));
    }

    public function move(int $id, string $direction)
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            setToast('error', 'Invalid position.');
            return redirect()->to(site_url('admin/organization'));
        }

        $memberModel = $this->memberModel();
        $member = $memberModel->find($id);
        if (! $member) {
            setToast('error', 'Member not found.');
            return redirect()->to(site_url('admin/organization'));
        }

        $section = (string) $member['section'];
        $mentorCategory = $section === OrganizationMemberModel::SECTION_MENTOR
            ? ($member['mentorCategory'] ?? null)
            : null;

        $memberModel->normalizeOrder($section, $mentorCategory);
        $members = $memberModel->getBySection($section, $mentorCategory);
        $currentIndex = null;

        foreach ($members as $index => $row) {
            if ((int) $row['id'] === $id) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            setToast('error', 'Member not found.');
            return redirect()->to($this->organizationListUrl($section, $mentorCategory));
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if (! isset($members[$targetIndex])) {
            return redirect()->to($this->organizationListUrl($section, $mentorCategory));
        }

        $current = $members[$currentIndex];
        $target = $members[$targetIndex];

        $this->db->transStart();
        $memberModel->update((int) $current['id'], ['sortOrder' => (int) $target['sortOrder']]);
        $memberModel->update((int) $target['id'], ['sortOrder' => (int) $current['sortOrder']]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            setToast('error', 'Unable to move member.');
        } else {
            setToast('success', 'Order updated.');
        }

        return redirect()->to($this->organizationListUrl($section, $mentorCategory));
    }

    private function organizationListUrl(string $section, ?string $mentorCategory = null, array $query = []): string
    {
        $params = ['section' => $section];

        if ($section === OrganizationMemberModel::SECTION_MENTOR && $mentorCategory !== null && $mentorCategory !== '') {
            $params['category'] = $mentorCategory;
        }

        foreach ($query as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[$key] = (string) $value;
            }
        }

        return site_url('admin/organization?' . http_build_query($params));
    }

    /**
     * @param array{mode:string,member:?array,defaultSection:string,defaultCategory:string,submitUrl:string,errors:array,formData:array} $data
     */
    private function renderMemberModal(array $data): string
    {
        return view('admin/organization/_member_modal', [
            'modalMode' => $data['mode'],
            'modalMember' => $data['member'],
            'defaultSection' => $data['defaultSection'],
            'defaultCategory' => $data['defaultCategory'],
            'mentorCategories' => OrganizationMemberModel::MENTOR_CATEGORIES,
            'modalSubmitUrl' => $data['submitUrl'],
            'formErrors' => $data['errors'],
            'formData' => $data['formData'],
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,string> $errors
     */
    private function modalErrorResponse(string $mode, ?array $member, array $payload, array $errors, ?int $memberId = null)
    {
        $section = $this->sanitizeSection((string) ($payload['section'] ?? ($member['section'] ?? OrganizationMemberModel::SECTION_CORE_TEAM)));
        $category = $this->sanitizeMentorCategory((string) ($payload['mentorCategory'] ?? ($member['mentorCategory'] ?? '')), $section);

        $modalHtml = $this->renderMemberModal([
            'mode' => $mode,
            'member' => $member,
            'defaultSection' => $section,
            'defaultCategory' => $category,
            'submitUrl' => $mode === 'edit' && $memberId !== null
                ? site_url('admin/organization/modal/' . $memberId)
                : site_url('admin/organization/modal'),
            'errors' => $errors,
            'formData' => $payload,
        ]);

        return $this->response->setStatusCode(422)->setJSON([
            'ok' => false,
            'modalHtml' => $modalHtml,
        ]);
    }

    /**
     * @param array{section:string,category:string}|null $previousLocation
     */
    private function modalSuccessResponse(int $memberId, string $message, string $action, ?array $previousLocation = null)
    {
        $memberModel = $this->memberModel();
        $member = $memberModel->find($memberId);
        if (! is_array($member)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Member not found after save.',
            ]);
        }

        $section = $this->sanitizeSection((string) ($member['section'] ?? OrganizationMemberModel::SECTION_CORE_TEAM));
        $mentorCategory = $this->sanitizeMentorCategory((string) ($member['mentorCategory'] ?? ''), $section);
        $members = $memberModel->getBySection(
            $section,
            $section === OrganizationMemberModel::SECTION_MENTOR ? $mentorCategory : null
        );
        $memberIndex = null;
        foreach ($members as $index => $row) {
            if ((int) ($row['id'] ?? 0) === $memberId) {
                $memberIndex = $index;
                break;
            }
        }

        $lastIndex = count($members) - 1;
        $rowHtml = view('admin/organization/_member_row', [
            'member' => $member,
            'activeSection' => $section,
            'isFirst' => $memberIndex === 0,
            'isLast' => $memberIndex === $lastIndex,
        ]);

        $patchRows = [];
        if ($action === 'insert' && $memberIndex !== null && $memberIndex > 0) {
            $previousMember = $members[$memberIndex - 1];
            $patchRows[] = [
                'id' => (int) $previousMember['id'],
                'rowHtml' => view('admin/organization/_member_row', [
                    'member' => $previousMember,
                    'activeSection' => $section,
                    'isFirst' => ($memberIndex - 1) === 0,
                    'isLast' => false,
                ]),
            ];
        }

        if ($section !== OrganizationMemberModel::SECTION_MENTOR && (int) ($member['isFeatured'] ?? 0) === 1) {
            foreach ($members as $index => $sectionMember) {
                $sectionMemberId = (int) ($sectionMember['id'] ?? 0);
                if ($sectionMemberId === $memberId) {
                    continue;
                }

                $patchRows[] = [
                    'id' => $sectionMemberId,
                    'rowHtml' => view('admin/organization/_member_row', [
                        'member' => $sectionMember,
                        'activeSection' => $section,
                        'isFirst' => $index === 0,
                        'isLast' => $index === $lastIndex,
                    ]),
                ];
            }
        }

        $sectionCounts = $this->sectionCounts();
        $listEmpty = null;
        if ($action === 'relocate' && is_array($previousLocation)) {
            $patchRows = array_merge(
                $patchRows,
                $this->neighborRowPatches(
                    $previousLocation['section'],
                    $previousLocation['section'] === OrganizationMemberModel::SECTION_MENTOR ? $previousLocation['category'] : null
                )
            );

            if ($memberIndex !== null && $memberIndex > 0) {
                $previousMember = $members[$memberIndex - 1];
                $patchRows[] = [
                    'id' => (int) $previousMember['id'],
                    'rowHtml' => view('admin/organization/_member_row', [
                        'member' => $previousMember,
                        'activeSection' => $section,
                        'isFirst' => ($memberIndex - 1) === 0,
                        'isLast' => false,
                    ]),
                ];
            }

            $oldSection = $this->sanitizeSection($previousLocation['section']);
            $oldCategory = $this->sanitizeMentorCategory($previousLocation['category'], $oldSection);
            if (($sectionCounts[$oldSection] ?? 0) === 0) {
                $listEmpty = [
                    'selector' => $this->listSelector($oldSection, $oldCategory),
                    'html' => $this->renderEmptyList($oldSection),
                ];
            }
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => $message,
            'action' => $action,
            'memberId' => $memberId,
            'section' => $section,
            'category' => $mentorCategory,
            'listSelector' => $this->listSelector($section, $mentorCategory),
            'rowHtml' => $rowHtml,
            'patchRows' => $patchRows,
            'listEmpty' => $listEmpty,
            'sectionCounts' => $sectionCounts,
            'totalCount' => array_sum($sectionCounts),
            'memberUpdatedAt' => (string) ($member['updatedAt'] ?? ''),
        ]);
    }

    /**
     * @return list<array{id:int,rowHtml:string}>
     */
    private function neighborRowPatches(string $section, ?string $mentorCategory = null): array
    {
        $members = $this->memberModel()->getBySection($section, $mentorCategory);
        $lastIndex = count($members) - 1;
        $patches = [];

        foreach ($members as $index => $member) {
            if ($index !== 0 && $index !== $lastIndex) {
                continue;
            }

            $patches[] = [
                'id' => (int) $member['id'],
                'rowHtml' => view('admin/organization/_member_row', [
                    'member' => $member,
                    'activeSection' => $section,
                    'isFirst' => $index === 0,
                    'isLast' => $index === $lastIndex,
                ]),
            ];
        }

        return $patches;
    }

    /**
     * @return array<string,int>
     */
    private function sectionCounts(): array
    {
        $memberModel = $this->memberModel();
        $counts = [];

        foreach (OrganizationMemberModel::SECTIONS as $section) {
            $counts[$section] = $memberModel->where('section', $section)->countAllResults();
        }

        return $counts;
    }

    private function listSelector(string $section, string $mentorCategory = ''): string
    {
        if ($section === OrganizationMemberModel::SECTION_MENTOR) {
            return '#mentor-group-' . $this->mentorCategorySlug($mentorCategory);
        }

        return '#org-section-list';
    }

    private function renderEmptyList(string $section): string
    {
        if ($section === OrganizationMemberModel::SECTION_MENTOR) {
            return '<div class="org-admin-empty org-admin-empty-compact"><span>No mentors in this area yet.</span></div>';
        }

        return '<div class="org-admin-empty"><strong>No members in this section</strong><span>Add the first member using the button above.</span></div>';
    }

    private function sanitizeSection(string $section): string
    {
        $section = trim($section);
        if (! in_array($section, OrganizationMemberModel::SECTIONS, true)) {
            return OrganizationMemberModel::SECTION_CORE_TEAM;
        }
        return $section;
    }

    private function sanitizeMentorCategory(string $category, string $section): string
    {
        if ($section !== OrganizationMemberModel::SECTION_MENTOR) {
            return '';
        }

        $category = trim($category);
        if ($category === '' || ! in_array($category, OrganizationMemberModel::MENTOR_CATEGORIES, true)) {
            return OrganizationMemberModel::MENTOR_CATEGORIES[0];
        }

        return $category;
    }

    private function mentorCategorySlug(string $category): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $category));
    }

    private function memberPayload(): array
    {
        $section = trim((string) $this->request->getPost('section'));
        $mentorCategory = trim((string) $this->request->getPost('mentorCategory'));

        return [
            'section'        => $section,
            'fullName'       => trim((string) $this->request->getPost('fullName')),
            'rolePrimary'    => trim((string) $this->request->getPost('rolePrimary')) ?: null,
            'roleSecondary'  => trim((string) $this->request->getPost('roleSecondary')) ?: null,
            'mentorCategory' => $section === OrganizationMemberModel::SECTION_MENTOR && $mentorCategory !== ''
                ? $mentorCategory
                : null,
            'isFeatured'     => $this->request->getPost('isFeatured') === '1' ? 1 : 0,
            'isPublished'    => $this->request->getPost('isPublished') === '1' ? 1 : 0,
        ];
    }

    private function clearFeaturedPeers(OrganizationMemberModel $memberModel, array $payload, int $currentId): void
    {
        $section = (string) ($payload['section'] ?? '');
        if ($section === OrganizationMemberModel::SECTION_MENTOR || (int) ($payload['isFeatured'] ?? 0) !== 1) {
            return;
        }

        $this->db->table('organization_members')
            ->where('section', $section)
            ->where('id !=', $currentId)
            ->update(['isFeatured' => 0]);
    }

    private function handlePhotoUpload(?string $existingPath = null): ?string
    {
        $file = $this->request->getFile('photo');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->hasMoved()) {
            throw new \RuntimeException('Invalid photo upload.');
        }

        $this->assertSquareImage($file, 'Photo');

        $uploader = new ImageUpload();
        $path = $uploader->upload($file, 'team', self::PHOTO_MAX_BYTES);

        if ($existingPath !== null && str_starts_with($existingPath, 'uploads/')) {
            $uploader->delete($existingPath);
        }

        return $path;
    }

    private function deleteUploadedPhoto(?string $path): void
    {
        if ($path !== null && str_starts_with($path, 'uploads/')) {
            (new ImageUpload())->delete($path);
        }
    }

    private function assertSquareImage(\CodeIgniter\HTTP\Files\UploadedFile $file, string $label): void
    {
        $dimensions = @getimagesize($file->getTempName());
        if ($dimensions === false || ! isset($dimensions[0], $dimensions[1])) {
            throw new \RuntimeException($label . ' must be a valid image.');
        }

        if ((int) $dimensions[0] !== (int) $dimensions[1]) {
            throw new \RuntimeException($label . ' must be square (1:1).');
        }
    }

    private function validationMessage(OrganizationMemberModel $memberModel): string
    {
        $errors = $memberModel->errors();

        return $errors === []
            ? 'Unable to save member.'
            : implode(' ', array_values($errors));
    }
}
