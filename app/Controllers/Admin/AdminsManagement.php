<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\GmailMailer;

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
        $role      = in_array($role, ['all', 'superadmin', 'admin', 'editor'], true) ? $role : 'all';
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

        if (! empty($result['requiresReauth'])) {
            session()->destroy();
            return redirect()->to('/asog-admin')->with('success', 'Your role was updated. Please sign in again to continue with your new access.');
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
        $result = $this->createAccountFromRequest(false);
        if (! $result['ok']) {
            return $this->modalErrorResponse('add', null, [$result['message']]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => $result['message'],
            'welcomeEmailUrl' => ! empty($result['accountId']) ? site_url('admin/accounts/' . $result['accountId'] . '/welcome-email') : null,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function sendWelcomeEmail(int $id)
    {
        $admin = $this->adminModel->find($id);
        if (! is_array($admin)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Account not found.',
            ]);
        }

        if (empty($admin['isActive'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Welcome email was not sent because the account is inactive.',
            ]);
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        if (! $this->adminModel->update($id, [
            'resetToken' => $tokenHash,
            'resetTokenExpiresAt' => $expiresAt,
        ])) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Unable to prepare the set-password link.',
            ]);
        }

        $email = strtolower(trim((string) ($admin['email'] ?? '')));
        $sent = $this->sendWelcomeSetPasswordEmail(
            $email,
            (string) ($admin['fullName'] ?? 'there'),
            (string) ($admin['role'] ?? 'admin'),
            $token
        );

        return $this->response
            ->setStatusCode($sent ? 200 : 502)
            ->setJSON([
                'ok' => $sent,
                'message' => $sent
                    ? 'Welcome email sent.'
                    : 'Account was created, but the welcome email could not be sent. Ask the user to use Forgot Password.',
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
            'requiresReauth' => ! empty($result['requiresReauth']),
            'logoutUrl' => site_url('asog-admin/logout'),
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

        if ($this->wouldRemoveLastActiveSuperadmin($admin, null, false)) {
            setToast('error', $this->lastSuperadminMessage());
            return redirect()->to('admin/accounts');
        }

        if ($this->adminModel->delete($id)) {
            setToast('success', 'Admin account deleted.');
        } else {
            setToast('error', 'Failed to delete admin account.');
        }

        return redirect()->to('admin/accounts');
    }

    private function createAccountFromRequest(bool $sendWelcomeEmail = true): array
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

        $setupToken = null;
        if ($sendWelcomeEmail) {
            $setupToken = bin2hex(random_bytes(32));
            $data['resetToken'] = hash('sha256', $setupToken);
            $data['resetTokenExpiresAt'] = date('Y-m-d H:i:s', time() + 86400);
        }

        if (! $this->adminModel->insert($data)) {
            return ['ok' => false, 'message' => 'Error: ' . implode(', ', $this->adminModel->errors())];
        }

        $accountId = (int) $this->adminModel->getInsertID();

        if (! $sendWelcomeEmail) {
            return [
                'ok' => true,
                'accountId' => $accountId,
                'message' => 'Account added. The welcome email will be sent in the background.',
            ];
        }

        $sent = $setupToken !== null && $this->sendWelcomeSetPasswordEmail($email, $fullName, $role, $setupToken);
        if (! $sent) {
            return [
                'ok' => true,
                'accountId' => $accountId,
                'message' => 'Account added, but the welcome email could not be sent. Ask the user to use Forgot Password to set their password.',
            ];
        }

        return [
            'ok' => true,
            'accountId' => $accountId,
            'message' => 'Account added. A set-password email was sent to ' . $email . '.',
        ];
    }

    private function updateAccountFromRequest(int $id): array
    {
        $admin = $this->adminModel->find($id);
        if (! is_array($admin)) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }

        $fullName    = trim((string) $this->request->getPost('fullName'));
        $email    = trim((string) $this->request->getPost('email'));
        $role     = $this->sanitizeRole((string) $this->request->getPost('role'));
        $isActive = $this->request->getPost('isActive') === '1';

        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Full name is required.'];
        }

        if ($this->adminModel->isEmailTaken($email, $id)) {
            return ['ok' => false, 'message' => 'That email is already used by another admin.'];
        }

        if ($this->wouldRemoveLastActiveSuperadmin($admin, $role, $isActive)) {
            return ['ok' => false, 'message' => $this->lastSuperadminMessage()];
        }

        $updateData = [
            'fullName' => $fullName,
            'email'    => $email,
            'role'     => $role,
            'isActive' => $isActive ? 1 : 0,
        ];

        if (! $this->adminModel->update($id, $updateData)) {
            return ['ok' => false, 'message' => 'Error: ' . implode(', ', $this->adminModel->errors())];
        }

        return [
            'ok' => true,
            'message' => 'Account updated.',
            'requiresReauth' => $this->requiresReauthAfterSelfUpdate($admin, $role, $isActive),
        ];
    }

    private function sanitizeRole(string $role): string
    {
        $role = trim($role);
        return in_array($role, ['superadmin', 'admin', 'editor'], true) ? $role : 'superadmin';
    }

    private function sendWelcomeSetPasswordEmail(string $email, string $fullName, string $role, string $token): bool
    {
        $setupUrl = site_url('asog-admin/reset-password/' . $token);

        try {
            $gmail = new GmailMailer();
            $sent = $gmail->send($email, 'Set Your ASOG TBI Account Password', view('emails/admin_account_welcome', [
                'adminName' => $fullName,
                'role' => $role,
                'setupUrl' => $setupUrl,
            ]));

            if (! $sent) {
                log_message('error', 'New account welcome email failed via Gmail API for ' . $email . '.');
            }

            return $sent;
        } catch (\Throwable $e) {
            log_message('error', 'New account welcome email exception for ' . $email . ': ' . $e->getMessage());
            return false;
        }
    }

    private function requiresReauthAfterSelfUpdate(array $admin, string $nextRole, bool $nextActive): bool
    {
        $adminId = (int) ($admin['id'] ?? 0);
        $currentAdminId = (int) session()->get('admin_id');

        if ($adminId === 0 || $adminId !== $currentAdminId) {
            return false;
        }

        $currentRole = (string) session()->get('admin_role');

        return $currentRole !== $nextRole || ! $nextActive;
    }

    private function wouldRemoveLastActiveSuperadmin(array $admin, ?string $nextRole, bool $nextActive): bool
    {
        $adminId = (int) ($admin['id'] ?? 0);
        $currentlyActiveSuperadmin = (string) ($admin['role'] ?? '') === 'superadmin'
            && (int) ($admin['isActive'] ?? 0) === 1;
        $willRemainActiveSuperadmin = $nextRole === 'superadmin' && $nextActive;

        if (! $currentlyActiveSuperadmin || $willRemainActiveSuperadmin) {
            return false;
        }

        return $this->activeSuperadminCount($adminId) === 0;
    }

    private function activeSuperadminCount(?int $excludeId = null): int
    {
        $builder = $this->adminModel->builder()
            ->where('role', 'superadmin')
            ->where('isActive', 1);

        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return (int) $builder->countAllResults();
    }

    private function lastSuperadminMessage(): string
    {
        return 'At least one active super admin is required. Add or activate another super admin before changing this account.';
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
