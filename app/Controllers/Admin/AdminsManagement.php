<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * AdminsManagement — CRUD for admin accounts and Google OAuth authorization.
 */
class AdminsManagement extends BaseController
{
    /**
     * List all admin accounts with their Google authorization status.
     */
    public function index()
    {
        $search    = trim((string) ($this->request->getGet('search') ?? ''));
        $status    = trim((string) ($this->request->getGet('status') ?? 'all'));
        $status    = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';
        $role      = trim((string) ($this->request->getGet('role') ?? 'all'));
        $role      = in_array($role, ['all', 'superadmin', 'admin'], true) ? $role : 'all';
        $sort      = trim((string) ($this->request->getGet('sort') ?? 'fullName'));
        $direction = trim((string) ($this->request->getGet('direction') ?? 'ASC'));
        $page      = max(1, (int) ($this->request->getGet('page') ?? 1));

        $result = $this->adminModel->getFiltered($search, $status, $role, $sort, $direction, $page, 10);
        $counts = $this->adminModel->getCounts();

        $data = [
            'pageTitle'   => 'Accounts',
            'activePage'  => 'admins',
            'admins'      => $result['admins'],
            'total'       => $result['total'],
            'currentPage' => $result['page'],
            'totalPages'  => $result['totalPages'],
            'perPage'     => $result['perPage'],
            'search'      => $search,
            'status'      => $status,
            'role'        => $role,
            'sort'        => $sort,
            'direction'   => $direction,
            'counts'      => $counts,
        ];

        return view('admin/layout/header', $data)
             . view('admin/admins/index', $data)
             . view('admin/layout/footer');
    }

    /**
     * Show form to create a new admin account.
     */
    public function create()
    {
        return redirect()->to(site_url('admin/accounts') . '?modal=add');
    }

    /**
     * Store a new admin account.
     */
    public function store()
    {
        $result = $this->createAccountFromRequest();
        if (! $result['ok']) {
            setToast('error', $result['message']);
            return redirect()->back()->withInput();
        }

        setToast('success', $result['message']);
        return redirect()->to('admin/accounts');
    }

    /**
     * Show form to edit an admin account.
     */
    public function edit($id = null)
    {
        $id = (int) $id;
        if ($id === 0) {
            return redirect()->to('admin/accounts')->with('error', 'Invalid admin ID.');
        }

        $admin = $this->adminModel->find($id);
        if ($admin === null) {
            return redirect()->to('admin/accounts')->with('error', 'Admin not found.');
        }

        return redirect()->to(site_url('admin/accounts') . '?modal=edit&accountId=' . $id);
    }

    /**
     * Update admin account.
     */
    public function update($id = null)
    {
        $id = (int) $id;
        if ($id === 0 || $this->adminModel->find($id) === null) {
            return redirect()->to('admin/accounts')->with('error', 'Invalid admin ID.');
        }

        $result = $this->updateAccountFromRequest($id);
        if (! $result['ok']) {
            setToast('error', $result['message']);
            return redirect()->back()->withInput();
        }

        setToast('success', $result['message']);
        return redirect()->to('admin/accounts');
    }

    public function modalCreate()
    {
        return $this->response->setBody($this->renderAccountModal([
            'mode' => 'add',
            'admin' => null,
            'errors' => [],
            'formData' => [],
            'submitUrl' => site_url('admin/accounts/modal'),
        ]));
    }

    public function modalEdit(int $id)
    {
        $admin = $this->adminModel->find($id);
        if (! is_array($admin)) {
            return $this->response->setStatusCode(404)->setBody('Account not found.');
        }

        return $this->response->setBody($this->renderAccountModal([
            'mode' => 'edit',
            'admin' => $admin,
            'errors' => [],
            'formData' => [],
            'submitUrl' => site_url('admin/accounts/modal/' . $id),
        ]));
    }

    public function modalStore()
    {
        $result = $this->createAccountFromRequest();
        if (! $result['ok']) {
            return $this->modalErrorResponse('add', null, [$result['message']]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => $result['message'],
        ]);
    }

    public function modalUpdate(int $id)
    {
        $admin = $this->adminModel->find($id);
        if (! is_array($admin)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Account not found.',
            ]);
        }

        $result = $this->updateAccountFromRequest($id);
        if (! $result['ok']) {
            return $this->modalErrorResponse('edit', $admin, [$result['message']], $id);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Delete an admin account.
     */
    public function delete($id = null)
    {
        $id = (int) $id;
        if ($id === 0) {
            return redirect()->to('admin/accounts')->with('error', 'Invalid admin ID.');
        }

        $admin = $this->adminModel->find($id);
        if ($admin === null) {
            return redirect()->to('admin/accounts')->with('error', 'Admin not found.');
        }

        if ($this->adminModel->delete($id)) {
            setToast('success', 'Admin account deleted.');
        } else {
            setToast('error', 'Failed to delete admin account.');
        }

        return redirect()->to('admin/accounts');
    }

    private function createAccountFromRequest(): array
    {
        $fullName = trim((string) $this->request->getPost('fullName'));
        $email = trim((string) $this->request->getPost('email'));
        $role  = $this->sanitizeRole((string) $this->request->getPost('role'));

        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Full name is required.'];
        }

        if ($this->adminModel->isEmailTaken($email)) {
            return ['ok' => false, 'message' => 'That email is already used by another admin.'];
        }

        $tempPassword = bin2hex(random_bytes(8));

        $data = [
            'fullName' => $fullName,
            'email'    => $email,
            'password' => $tempPassword,
            'role'     => $role,
            'isActive' => 1,
        ];

        if (! $this->adminModel->insert($data)) {
            return ['ok' => false, 'message' => 'Error: ' . implode(', ', $this->adminModel->errors())];
        }

        return ['ok' => true, 'message' => 'Account added. Email: ' . $email . ' | Role: ' . ucfirst($role)];
    }

    private function updateAccountFromRequest(int $id): array
    {
        $fullName    = trim((string) $this->request->getPost('fullName'));
        $email       = trim((string) $this->request->getPost('email'));
        $googleEmail = trim((string) $this->request->getPost('googleEmail'));
        $role        = $this->sanitizeRole((string) $this->request->getPost('role'));
        $isActive    = (bool) $this->request->getPost('isActive');

        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Full name is required.'];
        }

        if ($this->adminModel->isEmailTaken($email, $id)) {
            return ['ok' => false, 'message' => 'That email is already used by another admin.'];
        }

        $updateData = [
            'fullName'    => $fullName,
            'email'       => $email,
            'googleEmail' => $googleEmail === '' ? null : $googleEmail,
            'role'        => $role,
            'isActive'    => $isActive ? 1 : 0,
        ];

        if (! $this->adminModel->update($id, $updateData)) {
            return ['ok' => false, 'message' => 'Error: ' . implode(', ', $this->adminModel->errors())];
        }

        return ['ok' => true, 'message' => 'Account updated.'];
    }

    private function sanitizeRole(string $role): string
    {
        $role = trim($role);
        return in_array($role, ['superadmin', 'admin', 'editor'], true) ? $role : 'superadmin';
    }

    private function renderAccountModal(array $data): string
    {
        return view('admin/admins/_account_modal', [
            'modalMode' => $data['mode'],
            'modalAdmin' => $data['admin'],
            'modalErrors' => $data['errors'] ?? [],
            'formData' => $data['formData'] ?? [],
            'modalSubmitUrl' => $data['submitUrl'],
        ]);
    }

    private function modalErrorResponse(string $mode, ?array $admin, array $errors, ?int $adminId = null)
    {
        $modalHtml = $this->renderAccountModal([
            'mode' => $mode,
            'admin' => $admin,
            'errors' => $errors,
            'formData' => $this->request->getPost(),
            'submitUrl' => $mode === 'edit' && $adminId !== null
                ? site_url('admin/accounts/modal/' . $adminId)
                : site_url('admin/accounts/modal'),
        ]);

        return $this->response->setStatusCode(422)->setJSON([
            'ok' => false,
            'modalHtml' => $modalHtml,
        ]);
    }
}
