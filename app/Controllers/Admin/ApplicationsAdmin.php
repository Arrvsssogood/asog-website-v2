<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\GmailMailer;
use App\Models\IncubateeApplicationModel;
use App\Models\LandingSettingModel;

/**
 * ApplicationsAdmin — Review & manage incubatee applications.
 *
 * Routes: admin/applications, admin/applications/(:num), etc.
 */
class ApplicationsAdmin extends BaseController
{

    /**
     * List all applications received by the TBI.
     *
     * Supports text search, status filter, and column sorting via GET params.
     */

    public function index()
    {
        $search    = $this->request->getGet('search') ?? '';
        $status    = $this->request->getGet('status') ?? 'active';
        $sort      = $this->request->getGet('sort') ?? 'createdAt';
        $direction = $this->request->getGet('direction') ?? 'DESC';
        $perPage   = 20;
        $page      = max(1, (int) ($this->request->getGet('page') ?? 1));
        $total     = $this->applicationModel->countFilteredApplications($search, $status);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $data = [
            'pageTitle'    => 'Applications',
            'activePage'   => 'applications',
            'applications' => $this->applicationModel->getFilteredApplications($search, $status, $sort, $direction, $perPage, $offset),
            'allowDuplicateEmails' => trim((string) (new LandingSettingModel())->getValue(
                LandingSettingModel::KEY_APPLY_ALLOW_DUPLICATE_EMAILS,
                '0'
            )) === '1',
            'counts'       => $this->applicationModel->getCounts(),
            'search'       => $search,
            'status'       => $status,
            'sort'         => $sort,
            'direction'    => $direction,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'total'        => $total,
            'perPage'      => $perPage,
        ];

        return view('admin/layout/header', $data)
             . view('admin/applications/index', $data)
             . view('admin/layout/footer');
    }

    public function updateSettings()
    {
        $allowDuplicateEmails = $this->request->getPost('allowDuplicateEmails') === '1';

        $saved = (new LandingSettingModel())->setValue(
            LandingSettingModel::KEY_APPLY_ALLOW_DUPLICATE_EMAILS,
            $allowDuplicateEmails ? '1' : '0'
        );

        if (! $saved) {
            setToast('error', 'Unable to save the application submission rule.');
            return redirect()->back()->withInput();
        }

        setToast('success', 'Application submission rule updated.');

        return redirect()->to(site_url('admin/applications'));
    }

    /**
     * Show a single application as JSON.
     *
     * Used by the review modal to load application details.
     */

    public function show(int $id)
    {
        $app = $this->applicationModel->find($id);

        if (! $app) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Application not found.']);
        }

        return $this->response->setJSON($app);
    }

    /**
     * Update application status.
     *
     * Accepts the new status from the request payload, validates it,
     * and returns a JSON success response.
     */

    public function updateStatus(int $id)
    {
        $app = $this->applicationModel->find($id);

        if (! $app) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Application not found.']);
        }

        $payload = $this->request->getJSON(true) ?? [];
        $status = $payload['status'] ?? '';
        $remark = $this->normalizeRemark($payload['remark'] ?? null);

        if ($status === IncubateeApplicationModel::STATUS_FOR_REVALIDATION && $remark === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'Please add a remark explaining what the applicant needs to update.',
            ]);
        }

        if (! in_array($status, IncubateeApplicationModel::allowedStatuses(), true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid status value.']);
        }

        $revalidationUrl = null;
        $updateData = [
            'applicationStatus' => $status,
            'statusRemark' => $remark,
            'revalidationTokenHash' => null,
            'revalidationTokenExpiresAt' => null,
            'revalidationRequestedAt' => null,
        ];

        if ($status === IncubateeApplicationModel::STATUS_FOR_REVALIDATION) {
            $token = bin2hex(random_bytes(32));
            $updateData['revalidationTokenHash'] = hash('sha256', $token);
            $updateData['revalidationTokenExpiresAt'] = date('Y-m-d H:i:s', strtotime('+14 days'));
            $updateData['revalidationRequestedAt'] = date('Y-m-d H:i:s');
            $revalidationUrl = site_url('apply/revalidate/' . $token);
        }

        if (! $this->applicationModel->update($id, $updateData)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Unable to update application status.']);
        }

        $app = $this->applicationModel->find($id);
        try {
            $this->sendStatusEmail($app, $status, $remark, $revalidationUrl);
        } catch (\Throwable $e) {
            log_message('error', '[ApplicationsAdmin] sendStatusEmail failed: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Application marked as ' . IncubateeApplicationModel::statusLabel($status) . '.',
        ]);
    }

    /**
     * Toggle the archived state of a single application.
     */

    public function toggleArchive(int $id)
    {
        $app = $this->applicationModel->find($id);

        if (! $app) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Application not found.']);
        }

        $newState = $app['isArchived'] ? 0 : 1;
        $this->applicationModel->update($id, ['isArchived' => $newState]);

        return $this->response->setJSON([
            'success'    => true,
            'isArchived' => $newState,
            'message'    => 'Application ' . ($newState ? 'archived' : 'restored') . '.',
        ]);
    }

    /**
     * Perform a bulk action on selected applications.
     *
     * Accepts: ids (array), action (pending|accepted|rejected|archive|unarchive)
     */

    public function bulk()
    {
        $payload = $this->request->getJSON(true);
        $ids     = $payload['ids']    ?? [];
        $action  = $payload['action'] ?? '';

        if (empty($ids) || ! is_array($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No applications selected.']);
        }

        $validActions = ['pending', 'accepted', 'rejected', 'archive', 'unarchive'];
        if (! in_array($action, $validActions, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid bulk action.']);
        }

        if (in_array($action, ['archive', 'unarchive'], true)) {
            $isArchived = ($action === 'archive') ? 1 : 0;
            $this->applicationModel->whereIn('id', $ids)->set(['isArchived' => $isArchived])->update();
        } else {
            // Fetch applicants before the batch update so we have email addresses
            $apps = $this->applicationModel->whereIn('id', $ids)->findAll();
            $this->applicationModel->whereIn('id', $ids)->set([
                'applicationStatus' => $action,
                'statusRemark'      => null,
                'revalidationTokenHash' => null,
                'revalidationTokenExpiresAt' => null,
                'revalidationRequestedAt' => null,
            ])->update();
            foreach ($apps as $app) {
                try {
                    $this->sendStatusEmail($app, $action, null);
                } catch (\Throwable $e) {
                    log_message('error', '[ApplicationsAdmin] bulk sendStatusEmail failed for app #' . $app['id'] . ': ' . $e->getMessage());
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Bulk action applied.',
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Send a transactional email to the applicant when their status changes.
     *
     * Silently skips if SMTP credentials are not configured in .env.
     */
    private function sendStatusEmail(array $app, string $newStatus, ?string $remark = null, ?string $revalidationUrl = null): void
    {
        $statusMap = [
            'pending'  => [
                'label'     => 'For Review',
                'badgeBg'   => '#eef2ff',
                'badgeColor'=> '#4f46e5',
                'message'   => 'Your application has been received and is ready for review by our team. We appreciate your patience as we evaluate all submissions.',
                'nextSteps' => 'Our team will review your application in the coming days. You will receive another email once a final decision has been made. Please keep this email for your reference.',
                'subject'   => 'ASOG TBI — Application Status Update',
            ],
            'for_revalidation' => [
                'label'     => 'For Revalidation',
                'badgeBg'   => '#fff7ed',
                'badgeColor'=> '#c2410c',
                'message'   => 'Your application is not rejected, but our review team needs you to update or correct some details before we can continue evaluating it.',
                'nextSteps' => 'Please review the remarks below and use the update link to revise your existing application within 14 days. After you resubmit, your application will return to For Review.',
                'subject'   => 'ASOG TBI - Please Update Your Application',
            ],
            'accepted' => [
                'label'     => 'Accepted',
                'badgeBg'   => '#dcfce7',
                'badgeColor'=> '#15803d',
                'message'   => 'We are pleased to inform you that your application to the ASOG TBI Incubation Program has been accepted. Congratulations on this achievement!',
                'nextSteps' => 'Our team will be reaching out to you within 3–5 business days to discuss onboarding and the next steps for joining the program. Please ensure your contact information is up to date.',
                'subject'   => 'ASOG TBI — Your Application Has Been Accepted',
            ],
            'rejected' => [
                'label'     => 'Not Selected',
                'badgeBg'   => '#fee2e2',
                'badgeColor'=> '#dc2626',
                'message'   => 'Thank you for your interest in the ASOG TBI Incubation Program. After careful review, we regret to inform you that your application was not selected for this cohort.',
                'nextSteps' => 'We encourage you to continue developing your startup and consider applying again in future cohorts. If you have any questions, feel free to reach out to us.',
                'subject'   => 'ASOG TBI — Update on Your Application',
            ],
        ];

        $info = $statusMap[$newStatus] ?? $statusMap['pending'];

        ob_start();
        try {
            $body = view('emails/application_status_update', [
                'applicantName' => $app['applicantName'],
                'startupName'   => $app['startupName'],
                'newStatus'     => $newStatus,
                'statusLabel'   => $info['label'],
                'badgeBg'       => $info['badgeBg'],
                'badgeColor'    => $info['badgeColor'],
                'message'       => $info['message'],
                'nextSteps'     => $info['nextSteps'],
                'statusRemark'  => $remark,
                'revalidationUrl' => $revalidationUrl,
                'revalidationExpiresAt' => $app['revalidationTokenExpiresAt'] ?? null,
            ]);

            $gmail = new GmailMailer();

            if (! $gmail->send($app['applicantEmail'], $info['subject'], $body)) {
                log_message('error', 'Status email failed via Gmail API for app #' . $app['id'] . '.');
            } else {
                log_message('info', 'Status email sent to: ' . $app['applicantEmail'] . ' (status: ' . $newStatus . ')');
            }
        } finally {
            ob_end_clean();
        }
    }

    private function normalizeRemark($remark): ?string
    {
        if (! is_string($remark)) {
            return null;
        }

        $remark = trim($remark);
        if ($remark === '') {
            return null;
        }

        return mb_substr($remark, 0, 2000);
    }
}
