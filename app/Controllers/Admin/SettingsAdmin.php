<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LandingSettingModel;

class SettingsAdmin extends BaseController
{
    public function index()
    {
        $settingModel = new LandingSettingModel();

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
        $applicationStartDate = $this->normalizeDateValue($settingModel->getValue(
            LandingSettingModel::KEY_APPLY_START_DATE,
            ''
        )) ?? '';
        $applicationEndDate = $this->normalizeDateValue($settingModel->getValue(
            LandingSettingModel::KEY_APPLY_END_DATE,
            ''
        )) ?? '';
        $applicationWindowStatus = $this->applicationWindowStatus($applicationStartDate, $applicationEndDate);

        $data = [
            'pageTitle'             => 'Settings',
            'activePage'            => 'settings',
            'isGuessStartupEnabled' => $isGuessStartupEnabled,
            'isGuessStartupVisible' => $isGuessStartupVisible,
            'showInternsSection'    => $showInternsSection,
            'landingFilterOptions'  => $activeCohortNames,
            'selectedLandingFilter' => $selectedLandingFilter,
            'allowDuplicateEmails'  => $allowDuplicateEmails,
            'applicationStartDate'  => $applicationStartDate,
            'applicationEndDate'    => $applicationEndDate,
            'applicationWindowStatus' => $applicationWindowStatus,
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
        $saved = $settingModel->setValue(LandingSettingModel::KEY_APPLY_START_DATE, $startDate) && $saved;
        $saved = $settingModel->setValue(LandingSettingModel::KEY_APPLY_END_DATE, $endDate) && $saved;

        if (! $saved) {
            setToast('error', 'Unable to save application settings.');
            return redirect()->to(site_url('admin/settings'))->withInput();
        }

        setToast('success', 'Application settings updated.');
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

    private function applicationWindowStatus(string $startDate, string $endDate): array
    {
        $today = (new \DateTimeImmutable('today', new \DateTimeZone(config('App')->appTimezone)))->format('Y-m-d');

        if ($startDate === '' && $endDate === '') {
            return [
                'label' => 'Always open',
                'description' => 'No application timeline is currently configured.',
                'state' => 'open',
            ];
        }

        if ($startDate !== '' && $today < $startDate) {
            return [
                'label' => 'Not yet open',
                'description' => 'Applications will open on ' . $this->formatDateLabel($startDate) . '.',
                'state' => 'upcoming',
            ];
        }

        if ($endDate !== '' && $today > $endDate) {
            return [
                'label' => 'Closed',
                'description' => 'Applications closed on ' . $this->formatDateLabel($endDate) . '.',
                'state' => 'closed',
            ];
        }

        return [
            'label' => 'Open',
            'description' => $endDate !== ''
                ? 'Applications are open until ' . $this->formatDateLabel($endDate) . '.'
                : 'Applications are currently open.',
            'state' => 'open',
        ];
    }

    private function formatDateLabel(string $date): string
    {
        return (new \DateTimeImmutable($date))->format('F j, Y');
    }
}
