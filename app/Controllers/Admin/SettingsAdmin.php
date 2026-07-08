<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DocumentUpload;
use App\Models\LandingSettingModel;

class SettingsAdmin extends BaseController
{
    public function index()
    {
        $settingModel = new LandingSettingModel();
        $sessionRole = (string) session()->get('admin_role');
        $currentAdminId = (int) session()->get('admin_id');
        $currentAdmin = $currentAdminId > 0 ? $this->adminModel->find($currentAdminId) : null;

        $guessStartupRaw = trim((string) $settingModel->getValue(LandingSettingModel::KEY_GUESS_STARTUP_ENABLED, '1'));
        $isGuessStartupEnabled = $guessStartupRaw !== '0';
        $guessStartupVisibleRaw = trim((string) $settingModel->getValue(LandingSettingModel::KEY_GUESS_STARTUP_VISIBLE, '1'));
        $isGuessStartupVisible = $guessStartupVisibleRaw !== '0';

        $internsRaw = trim((string) $settingModel->getValue(LandingSettingModel::KEY_SHOW_INTERNS, '1'));
        $showInternsSection = $internsRaw !== '0';

        $activeCohortNames = $this->cohortModel->getActiveNames();
        $selectedLandingFilter = trim((string) $settingModel->getValue(
            LandingSettingModel::KEY_INCUBATEES_FILTER,
            'all'
        ));

        if ($selectedLandingFilter === '' || ($selectedLandingFilter !== 'all' && ! in_array($selectedLandingFilter, $activeCohortNames, true))) {
            $selectedLandingFilter = 'all';
        }

        $allowDuplicateEmails = trim((string) $settingModel->getValue(
            LandingSettingModel::KEY_APPLY_ALLOW_DUPLICATE_EMAILS,
            '0'
        )) === '1';
        $showApplicationDeadline = trim((string) $settingModel->getValue(
            LandingSettingModel::KEY_APPLY_SHOW_DEADLINE,
            '1'
        )) !== '0';
        $landingLoaderEnabled = trim((string) $settingModel->getValue(
            LandingSettingModel::KEY_LANDING_LOADER_ENABLED,
            '1'
        )) !== '0';
        $applicationStartDate = $this->normalizeDateValue($settingModel->getValue(
            LandingSettingModel::KEY_APPLY_START_DATE,
            ''
        )) ?? '';
        $applicationEndDate = $this->normalizeDateValue($settingModel->getValue(
            LandingSettingModel::KEY_APPLY_END_DATE,
            ''
        )) ?? '';
        $applicationWindowStatus = $this->applicationWindowStatus($applicationStartDate, $applicationEndDate);
        $leanCanvasTemplate = $this->resolveLeanCanvasTemplate($settingModel);
        $gmailConfig = config('GmailApi');
        $recaptchaConfig = config('Recaptcha');
        $gmailReady = ! empty($gmailConfig->enabled)
            && trim((string) $gmailConfig->senderEmail) !== ''
            && trim((string) $gmailConfig->clientId) !== ''
            && trim((string) $gmailConfig->clientSecret) !== ''
            && trim((string) $gmailConfig->refreshToken) !== '';
        $recaptchaReady = ! empty($recaptchaConfig->enabled)
            && trim((string) $recaptchaConfig->siteKey) !== ''
            && trim((string) $recaptchaConfig->apiKey) !== '';

        $data = [
            'pageTitle'             => 'Settings',
            'activePage'            => 'settings',
            'currentAdmin'          => is_array($currentAdmin) ? $currentAdmin : null,
            'canManageSiteSettings' => $sessionRole === 'superadmin',
            'isGuessStartupEnabled' => $isGuessStartupEnabled,
            'isGuessStartupVisible' => $isGuessStartupVisible,
            'showInternsSection'    => $showInternsSection,
            'landingFilterOptions'  => $activeCohortNames,
            'selectedLandingFilter' => $selectedLandingFilter,
            'allowDuplicateEmails'  => $allowDuplicateEmails,
            'showApplicationDeadline' => $showApplicationDeadline,
            'landingLoaderEnabled'  => $landingLoaderEnabled,
            'applicationStartDate'  => $applicationStartDate,
            'applicationEndDate'    => $applicationEndDate,
            'applicationWindowStatus' => $applicationWindowStatus,
            'leanCanvasTemplate'    => $leanCanvasTemplate,
            'gmailStatus' => [
                'label'       => $gmailReady ? 'Ready' : 'Needs setup',
                'state'       => $gmailReady ? 'ready' : 'off',
                'description' => $gmailReady
                    ? 'Email sending is ready for website messages.'
                    : 'Email sending needs setup before messages can be sent.',
                'detail'      => $gmailReady ? trim((string) $gmailConfig->senderEmail) : '',
            ],
            'recaptchaStatus' => [
                'label'       => $recaptchaReady ? 'On' : 'Off',
                'state'       => $recaptchaReady ? 'ready' : 'off',
                'description' => $recaptchaReady
                    ? 'Spam protection is on for public forms.'
                    : 'Spam protection is off or missing a key.',
            ],
            'loaderStatus' => [
                'label'       => $landingLoaderEnabled ? 'On' : 'Off',
                'state'       => $landingLoaderEnabled ? 'ready' : 'off',
                'description' => $landingLoaderEnabled
                    ? 'Runs once per browser session on the homepage.'
                    : 'Landing page opens directly without the intro animation.',
            ],
        ];

        return view('admin/layout/header', $data)
            . view('admin/settings/index', $data)
            . view('admin/layout/footer');
    }

    public function updateGuessStartupAvailability()
    {
        $settingModel = new LandingSettingModel();
        $enabled = $this->request->getPost('guessStartupEnabled') === '1';
        $visible = $this->request->getPost('guessStartupVisible') === '1';

        $saved = $settingModel->setValue(LandingSettingModel::KEY_GUESS_STARTUP_ENABLED, $enabled ? '1' : '0');
        $saved = $settingModel->setValue(LandingSettingModel::KEY_GUESS_STARTUP_VISIBLE, $visible ? '1' : '0') && $saved;

        if (! $saved) {
            setToast('error', 'Unable to save game settings.');
            return redirect()->to(site_url('admin/settings'));
        }

        setToast('success', 'Guess the Startup settings updated.');

        return redirect()->to(site_url('admin/settings'));
    }

    public function updateInternsVisibility()
    {
        $settingModel = new LandingSettingModel();
        $enabled = $this->request->getPost('showInternsSection') === '1';

        if (! $settingModel->setValue(LandingSettingModel::KEY_SHOW_INTERNS, $enabled ? '1' : '0')) {
            setToast('error', 'Unable to save interns section setting.');
            return redirect()->to(site_url('admin/settings'));
        }

        $status = $enabled ? 'visible' : 'hidden';
        setToast('success', 'Interns section is now ' . $status . '.');

        return redirect()->to(site_url('admin/settings'));
    }

    public function updateLandingFilter()
    {
        $selected = trim((string) ($this->request->getPost('landingCohortFilter') ?? 'all'));

        $allowed = ['all'];
        foreach ($this->cohortModel->getActiveNames() as $cohortName) {
            $allowed[] = (string) $cohortName;
        }

        if (! in_array($selected, $allowed, true)) {
            setToast('error', 'Invalid cohort selection.');
            return redirect()->to(site_url('admin/settings'));
        }

        $settingModel = new LandingSettingModel();
        if (! $settingModel->setValue(LandingSettingModel::KEY_INCUBATEES_FILTER, $selected)) {
            setToast('error', 'Unable to save landing cohort setting.');
            return redirect()->to(site_url('admin/settings'));
        }

        $label = $selected === 'all' ? 'All Cohorts' : $selected;
        setToast('success', 'Landing incubatees set to ' . $label . '.');

        return redirect()->to(site_url('admin/settings'));
    }

    public function updateApplicationSettings()
    {
        $allowDuplicateEmails = $this->request->getPost('allowDuplicateEmails') === '1';
        $showDeadline = $this->request->getPost('showApplicationDeadline') === '1';
        $startDate = $this->normalizeDateValue($this->request->getPost('applicationStartDate'));
        $endDate = $this->normalizeDateValue($this->request->getPost('applicationEndDate'));

        if ($startDate === null || $endDate === null) {
            setToast('error', 'Please enter valid application dates.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
            setToast('error', 'Application end date must be on or after the start date.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        $settingModel = new LandingSettingModel();
        $saved = $settingModel->setValue(
            LandingSettingModel::KEY_APPLY_ALLOW_DUPLICATE_EMAILS,
            $allowDuplicateEmails ? '1' : '0'
        );
        $saved = $settingModel->setValue(
            LandingSettingModel::KEY_APPLY_SHOW_DEADLINE,
            $showDeadline ? '1' : '0'
        ) && $saved;
        $saved = $settingModel->setValue(LandingSettingModel::KEY_APPLY_START_DATE, $startDate) && $saved;
        $saved = $settingModel->setValue(LandingSettingModel::KEY_APPLY_END_DATE, $endDate) && $saved;

        if (! $saved) {
            setToast('error', 'Unable to save application settings.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        $this->notifySystemUpdate(
            'Application settings updated',
            'Public application availability or submission rules were changed.',
            site_url('admin/settings')
        );

        setToast('success', 'Application settings updated.');
        return redirect()->to(site_url('admin/settings'));
    }

    /**
     * Upload (or replace) the Lean Canvas template applicants download from the form.
     */
    public function uploadLeanCanvasTemplate()
    {
        $file = $this->request->getFile('leanCanvasTemplate');

        if ($file === null || ! $file->isValid()) {
            setToast('error', 'No valid file was uploaded.');
            return redirect()->to(site_url('admin/settings'));
        }

        $uploader = new DocumentUpload();
        $path = $uploader->upload($file, 'templates');

        if ($path === null) {
            setToast('error', $uploader->getError());
            return redirect()->to(site_url('admin/settings'));
        }

        $settingModel = new LandingSettingModel();

        // Remove the previous template file (if any) before storing the new path.
        $previousPath = $this->normalizeLeanCanvasTemplatePath(trim((string) $settingModel->getValue(LandingSettingModel::KEY_APPLY_LEAN_CANVAS_TEMPLATE, '')));
        if ($previousPath !== '' && $previousPath !== $path) {
            $uploader->delete($previousPath);
        }

        if (! $settingModel->setValue(LandingSettingModel::KEY_APPLY_LEAN_CANVAS_TEMPLATE, $path)) {
            setToast('error', 'Template uploaded but the setting could not be saved.');
            return redirect()->to(site_url('admin/settings'));
        }

        setToast('success', 'Lean Canvas template updated.');
        return redirect()->to(site_url('admin/settings'));
    }

    /**
     * Delete the current Lean Canvas template.
     */
    public function deleteLeanCanvasTemplate()
    {
        $settingModel = new LandingSettingModel();
        $relativePath = $this->normalizeLeanCanvasTemplatePath(trim((string) $settingModel->getValue(LandingSettingModel::KEY_APPLY_LEAN_CANVAS_TEMPLATE, '')));

        if ($relativePath === '') {
            setToast('error', 'There is no Lean Canvas template to delete.');
            return redirect()->to(site_url('admin/settings'));
        }

        (new DocumentUpload())->delete($relativePath);

        if (! $settingModel->setValue(LandingSettingModel::KEY_APPLY_LEAN_CANVAS_TEMPLATE, '')) {
            setToast('error', 'Template file removed but the setting could not be cleared.');
            return redirect()->to(site_url('admin/settings'));
        }

        setToast('success', 'Lean Canvas template deleted.');
        return redirect()->to(site_url('admin/settings'));
    }

    public function updateSiteExperience()
    {
        $settingModel = new LandingSettingModel();
        $loaderEnabled = $this->request->getPost('landingLoaderEnabled') === '1';

        if (! $settingModel->setValue(LandingSettingModel::KEY_LANDING_LOADER_ENABLED, $loaderEnabled ? '1' : '0')) {
            setToast('error', 'Unable to save site experience setting.');
            return redirect()->to(site_url('admin/settings'));
        }

        $this->notifySystemUpdate(
            'Site experience updated',
            'Homepage experience settings were changed.',
            site_url('admin/settings')
        );

        setToast('success', 'Site experience settings updated.');
        return redirect()->to(site_url('admin/settings'));
    }

    public function updatePassword()
    {
        $adminId = (int) session()->get('admin_id');
        $admin = $adminId > 0 ? $this->adminModel->find($adminId) : null;

        if (! is_array($admin)) {
            setToast('error', 'Account not found.');
            return redirect()->to(site_url('admin/settings'));
        }

        $currentPassword = (string) $this->request->getPost('currentPassword');
        $newPassword = (string) $this->request->getPost('newPassword');
        $confirmPassword = (string) $this->request->getPost('confirmPassword');

        if ($currentPassword === '' || ! password_verify($currentPassword, (string) ($admin['password'] ?? ''))) {
            setToast('error', 'Current password is incorrect.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        if (strlen($newPassword) < 8) {
            setToast('error', 'New password must be at least 8 characters.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        if ($newPassword !== $confirmPassword) {
            setToast('error', 'New password and confirmation do not match.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        if (! $this->adminModel->update($adminId, ['password' => $newPassword])) {
            setToast('error', 'Unable to update password.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        setToast('success', 'Password updated.');
        return redirect()->to(site_url('admin/settings'));
    }

    private function normalizeDateValue($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (! $date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    private function notifySystemUpdate(string $title, string $body, ?string $link = null): void
    {
        try {
            $this->adminNotificationModel->createSystemUpdate($title, $body, $link, 'high');
        } catch (\Throwable $e) {
            log_message('error', '[SettingsAdmin] createSystemUpdate notification failed: ' . $e->getMessage());
        }
    }

    private function applicationWindowStatus(string $startDate, string $endDate): array
    {
        $today = (new \DateTimeImmutable('today', new \DateTimeZone(config('App')->appTimezone)))->format('Y-m-d');

        if ($startDate === '' && $endDate === '') {
            return [
                'label' => 'Always open',
                'description' => 'No application dates are set.',
                'state' => 'open',
            ];
        }

        if ($startDate !== '' && $today < $startDate) {
            return [
                'label' => 'Not yet open',
                'description' => 'Application starts on ' . $this->formatDateLabel($startDate) . '.',
                'state' => 'upcoming',
            ];
        }

        if ($endDate !== '' && $today > $endDate) {
            return [
                'label' => 'Closed',
                'description' => 'Application ended on ' . $this->formatDateLabel($endDate) . '.',
                'state' => 'closed',
            ];
        }

        return [
            'label' => 'Open',
            'description' => $endDate !== ''
                ? 'Application ends on ' . $this->formatDateLabel($endDate) . '.'
                : 'Applications are currently open.',
            'state' => 'open',
        ];
    }

    private function formatDateLabel(string $date): string
    {
        return (new \DateTimeImmutable($date))->format('F j, Y');
    }

    /**
     * Resolve the current Lean Canvas template into display-ready metadata.
     */
    private function resolveLeanCanvasTemplate(LandingSettingModel $settingModel): array
    {
        $uploader = new DocumentUpload();
        $storedPath = trim((string) $settingModel->getValue(LandingSettingModel::KEY_APPLY_LEAN_CANVAS_TEMPLATE, ''));
        $relativePath = $this->normalizeLeanCanvasTemplatePath($storedPath);

        if ($relativePath === '') {
            return [
                'path'  => '',
                'url'   => '',
                'name'  => '',
                'mime'  => '',
                'isPdf' => false,
            ];
        }

        // Only report a template if the underlying file still exists.
        $fullPath = $this->resolveLeanCanvasTemplateFile($relativePath);

        if (! is_file($fullPath)) {
            return [
                'path'  => '',
                'url'   => '',
                'name'  => '',
                'mime'  => '',
                'isPdf' => false,
            ];
        }

        try {
            $mime = (new \CodeIgniter\Files\File($fullPath))->getMimeType() ?: 'application/octet-stream';
        } catch (\Throwable $e) {
            $mime = 'application/octet-stream';
        }

        return [
            'path'  => $relativePath,
            'url'   => $uploader->publicUrl($relativePath),
            'name'  => basename($relativePath),
            'mime'  => $mime,
            'isPdf' => $mime === 'application/pdf',
        ];
    }

    private function normalizeLeanCanvasTemplatePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?? '');
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^.*?/uploads/#', '', $path) ?? $path;
        $path = preg_replace('#^(?:writable/)?uploads/#', '', ltrim($path, '/')) ?? $path;

        return ltrim($path, '/');
    }

    private function resolveLeanCanvasTemplateFile(string $relativePath): string
    {
        return WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR
            . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    }
}
