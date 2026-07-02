<?php

namespace App\Controllers;

use App\Libraries\GmailMailer;
use App\Libraries\RecaptchaVerifier;
use App\Models\FaqModel;
use App\Models\IncubateeApplicationModel;
use App\Models\LandingSettingModel;

class Incubatees extends BaseController
{
    public function index()
    {
        $cohorts = $this->cohortModel->getActive();
        $allIncubatees = [];

        foreach ($cohorts as &$cohort) {
            $incubatees = $this->incubateeModel->getPublishedByCohort($cohort['name']);
            $cohort['_count'] = count($incubatees);
            $allIncubatees = array_merge($allIncubatees, $incubatees);
        }
        unset($cohort);

        $data = [
            'title'          => 'Incubatees - ASOG TBI',
            'heroSubtitle'   => 'Our Startups',
            'heroTitle'      => 'Incubatees',
            'heroDesc'       => 'Meet the startups and MSMEs building the future of food value chain management through engineering and AI.',
            'heroBg'         => 'bg-navy',
            'cohorts'        => $cohorts,
            'allIncubatees'  => $allIncubatees,
        ];

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/index', $data)
            . view('templates/footer');
    }

    public function apply(): string
    {
        $faqModel = new FaqModel();
        $settings = new LandingSettingModel();

        $data = [
            'title'        => 'Be an Incubatee - ASOG TBI',
            'heroSubtitle' => 'Join the Program',
            'heroTitle'    => 'Be an Incubatee',
            'heroDesc'     => 'Apply to the ASOG TBI incubation program and turn your innovation into a market-ready solution.',
            'faqs'         => $faqModel->getPublished(),
            'faqTitle'     => $settings->getValue(
                LandingSettingModel::KEY_APPLY_FAQ_TITLE,
                'A few things you might be wondering.'
            ),
            'faqIntro'     => $settings->getValue(
                LandingSettingModel::KEY_APPLY_FAQ_INTRO,
                'Find quick answers about eligibility, requirements, and what happens after you submit your application.'
            ),
            'allowDuplicateEmails' => $this->allowDuplicateEmails(),
        ];

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/apply', $data)
            . view('templates/footer');
    }

    public function cohort(int $num): string
    {
        $cohortLabel = 'Cohort ' . $num;

        // Cohort 1 shows all published incubatees
        $incubatees = ($num === 1)
            ? $this->incubateeModel->getPublished()
            : $this->incubateeModel->getPublishedByCohort($cohortLabel);

        $data = [
            'title'        => $cohortLabel . ' - ASOG TBI',
            'heroSubtitle' => 'Incubation Program',
            'heroTitle'    => $cohortLabel,
            'heroDesc'     => 'The startups and MSMEs in ' . $cohortLabel . ' of the ASOG TBI incubation program.',
            'incubatees'   => $incubatees,
            'cohortLabel'  => $cohortLabel,
            'cohortNum'    => $num,
        ];

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/cohort', $data)
            . view('templates/footer');
    }

    public function applyForm(): string
    {
        $data = $this->buildApplyFormViewData();

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/apply_form', $data)
            . view('templates/footer');
    }

    public function applyFormStore(): \CodeIgniter\HTTP\ResponseInterface
    {
        $applicationModel = $this->applicationModel;
        
        $data = [
            'startupName'           => $this->request->getPost('startupName'),
            'startupDescription'    => $this->request->getPost('startupDescription'),
            'mainRisk'              => $this->request->getPost('mainRisk') ?? null,
            'shortTermGoals'        => $this->request->getPost('shortTermGoals') ?? null,
            'videoPresentationLink' => $this->request->getPost('videoPresentationLink'),
            'applicantName'         => $this->request->getPost('applicantName'),
            'applicantEmail'        => trim((string) $this->request->getPost('applicantEmail')),
            'contactNumber'         => preg_replace('/\D+/', '', (string) $this->request->getPost('contactNumber')),
            'privacyAgreement'      => (string) ($this->request->getPost('privacyAgreement') ?? ''),
            'applicationStatus'     => IncubateeApplicationModel::STATUS_PENDING,
        ];

        if ($this->requestBodyExceededPhpLimits()) {
            return $this->renderApplyFormResponse(
                $data,
                [],
                $this->uploadLimitExceededMessage(),
                413
            );
        }

        if ($this->request->getPost('privacyAgreement') !== '1') {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['privacyAgreement' => 'Please confirm your privacy consent before continuing.']);
        }

        $recaptcha = new RecaptchaVerifier();
        if (! $recaptcha->verifyRequest('application_submit')) {
            return $this->renderApplyFormResponse(
                $data,
                [],
                $recaptcha->failureMessage(),
                422
            );
        }

        // Validate
        if (! $applicationModel->validate($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $applicationModel->errors());
        }

        if (! $this->allowDuplicateEmails() && $applicationModel->emailExists($data['applicantEmail'])) {
            return $this->renderApplyFormResponse(
                $data,
                ['applicantEmail' => $applicationModel->duplicateEmailMessage()],
                null,
                422
            );
        }

        $teamCvUploadError = $this->multipleUploadErrorMessage(
            $this->request->getFileMultiple('teamCv') ?? [],
            'One or more CV files'
        );
        if ($teamCvUploadError !== null) {
            return $this->renderApplyFormResponse($data, [], $teamCvUploadError, 413);
        }

        $leanCanvasUploadError = $this->uploadErrorMessage(
            $this->request->getFile('leanCanvas'),
            'Your Lean Canvas file'
        );
        if ($leanCanvasUploadError !== null) {
            return $this->renderApplyFormResponse(
                $data,
                ['leanCanvas' => $leanCanvasUploadError],
                null,
                413
            );
        }

        // Handle file upload (Curriculum Vitae for team members)
        if ($this->request->getFileMultiple('teamCv')) {
            $files = $this->request->getFileMultiple('teamCv');
            $uploadedPaths = [];
            $seenFiles = [];

            if (count($files) > 10) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Only up to 10 CV files can be uploaded.');
            }

            foreach ($files as $file) {
                if ($file->isValid() && ! $file->hasMoved()) {
                    $fileKey = $file->getClientName() . '|' . $file->getSize();
                    if (isset($seenFiles[$fileKey])) {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Duplicate CV files are not allowed.');
                    }
                    $seenFiles[$fileKey] = true;

                    // Validate file type and size
                    if ($file->getSize() > 104857600) { // 100 MB
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'CV file exceeds 100 MB limit.');
                    }

                    if ($file->getMimeType() !== 'application/pdf') {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Only PDF files are accepted for CV.');
                    }

                    $newName = $file->getRandomName();
                    $file->move(WRITEPATH . 'uploads/applications', $newName);
                    $uploadedPaths[] = 'uploads/applications/' . $newName;
                }
            }

            if (! empty($uploadedPaths)) {
                $data['teamCvPath'] = implode(',', $uploadedPaths);
            }
        }

        // Handle Lean Canvas upload
        $leanCanvasFile = $this->request->getFile('leanCanvas');
        if ($leanCanvasFile && $leanCanvasFile->isValid() && ! $leanCanvasFile->hasMoved()) {
            $allowedMimes = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if ($leanCanvasFile->getSize() > 10485760) { // 10 MB
                return redirect()->back()
                    ->withInput()
                    ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Lean Canvas file exceeds the 10 MB limit.']));
            }

            if (! in_array($leanCanvasFile->getMimeType(), $allowedMimes)) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Only PDF or Word (.docx) files are accepted for the Lean Canvas.']));
            }

            $newName = $leanCanvasFile->getRandomName();
            $leanCanvasFile->move(WRITEPATH . 'uploads/applications', $newName);
            $data['leanCanvasPath'] = 'uploads/applications/' . $newName;
        } else {
            // Lean Canvas is required
            return redirect()->back()
                ->withInput()
                ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Please upload your completed Lean Canvas (.docx or PDF).']));
        }

        // Save application
        if ($applicationModel->insert($data)) {
            // Send a copy of their responses via email
            $this->sendConfirmationEmail($data);

            return redirect()->to(site_url('apply/form/thank-you'))
                ->with('success', 'Your application has been submitted successfully!')
                ->with('application_submitted', true);
        }

        if ($applicationModel->isDuplicateEmailDbError()) {
            return $this->renderApplyFormResponse(
                $data,
                ['applicantEmail' => $applicationModel->duplicateEmailMessage()],
                null,
                422
            );
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Unable to submit application. Please try again.');
    }

    public function revalidateForm(string $token)
    {
        $app = $this->applicationModel->findByRevalidationToken($token);

        if (! $app || ! $this->applicationModel->isRevalidationLinkUsable($app)) {
            return $this->renderRevalidationUnavailable();
        }

        $data = $this->buildApplyFormViewData($app, [], null, [
            'isRevalidation' => true,
            'revalidationToken' => $token,
            'revalidationAction' => site_url('apply/revalidate/' . $token),
            'revalidationRemark' => $app['statusRemark'] ?? '',
            'revalidationExpiresAt' => $app['revalidationTokenExpiresAt'] ?? '',
            'existingTeamCvPath' => $app['teamCvPath'] ?? '',
            'existingLeanCanvasPath' => $app['leanCanvasPath'] ?? '',
        ]);

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/apply_form', $data)
            . view('templates/footer');
    }

    public function revalidateFormStore(string $token): \CodeIgniter\HTTP\ResponseInterface
    {
        $applicationModel = $this->applicationModel;
        $app = $applicationModel->findByRevalidationToken($token);

        if (! $app || ! $applicationModel->isRevalidationLinkUsable($app)) {
            return $this->renderRevalidationUnavailable();
        }

        $data = [
            'startupName'           => $this->request->getPost('startupName'),
            'startupDescription'    => $this->request->getPost('startupDescription'),
            'mainRisk'              => $this->request->getPost('mainRisk') ?? null,
            'shortTermGoals'        => $this->request->getPost('shortTermGoals') ?? null,
            'videoPresentationLink' => $this->request->getPost('videoPresentationLink'),
            'applicantName'         => $this->request->getPost('applicantName'),
            'applicantEmail'        => trim((string) $this->request->getPost('applicantEmail')),
            'contactNumber'         => $this->request->getPost('contactNumber'),
            'privacyAgreement'      => (string) ($this->request->getPost('privacyAgreement') ?? ''),
            'applicationStatus'     => IncubateeApplicationModel::STATUS_PENDING,
        ];

        if ($this->requestBodyExceededPhpLimits()) {
            return $this->renderRevalidationFormResponse($token, $app, $data, [], $this->uploadLimitExceededMessage(), 413);
        }

        if ($this->request->getPost('privacyAgreement') !== '1') {
            return redirect()->back()
                ->withInput()
                ->with('errors', ['privacyAgreement' => 'Please confirm your privacy consent before continuing.']);
        }

        $recaptcha = new RecaptchaVerifier();
        if (! $recaptcha->verifyRequest('application_revalidate')) {
            return $this->renderRevalidationFormResponse(
                $token,
                $app,
                $data,
                [],
                $recaptcha->failureMessage(),
                422
            );
        }

        if (! $applicationModel->validate($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $applicationModel->errors());
        }

        $teamCvUploadError = $this->multipleUploadErrorMessage(
            $this->request->getFileMultiple('teamCv') ?? [],
            'One or more CV files'
        );
        if ($teamCvUploadError !== null) {
            return $this->renderRevalidationFormResponse($token, $app, $data, [], $teamCvUploadError, 413);
        }

        $leanCanvasUploadError = $this->uploadErrorMessage(
            $this->request->getFile('leanCanvas'),
            'Your Lean Canvas file'
        );
        if ($leanCanvasUploadError !== null) {
            return $this->renderRevalidationFormResponse(
                $token,
                $app,
                $data,
                ['leanCanvas' => $leanCanvasUploadError],
                null,
                413
            );
        }

        $data['teamCvPath'] = $app['teamCvPath'] ?? null;
        $files = $this->request->getFileMultiple('teamCv') ?? [];
        if ($this->hasUploadedFile($files)) {
            $uploadedPaths = [];
            $seenFiles = [];

            if (count($files) > 10) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Only up to 10 CV files can be uploaded.');
            }

            foreach ($files as $file) {
                if ($file->isValid() && ! $file->hasMoved()) {
                    $fileKey = $file->getClientName() . '|' . $file->getSize();
                    if (isset($seenFiles[$fileKey])) {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Duplicate CV files are not allowed.');
                    }
                    $seenFiles[$fileKey] = true;

                    if ($file->getSize() > 104857600) {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'CV file exceeds 100 MB limit.');
                    }

                    if ($file->getMimeType() !== 'application/pdf') {
                        return redirect()->back()
                            ->withInput()
                            ->with('error', 'Only PDF files are accepted for CV.');
                    }

                    $newName = $file->getRandomName();
                    $file->move(WRITEPATH . 'uploads/applications', $newName);
                    $uploadedPaths[] = 'uploads/applications/' . $newName;
                }
            }

            if (! empty($uploadedPaths)) {
                $data['teamCvPath'] = implode(',', $uploadedPaths);
            }
        }

        $data['leanCanvasPath'] = $app['leanCanvasPath'] ?? null;
        $leanCanvasFile = $this->request->getFile('leanCanvas');
        if ($leanCanvasFile && $leanCanvasFile->isValid() && ! $leanCanvasFile->hasMoved()) {
            $allowedMimes = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if ($leanCanvasFile->getSize() > 10485760) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Lean Canvas file exceeds the 10 MB limit.']));
            }

            if (! in_array($leanCanvasFile->getMimeType(), $allowedMimes, true)) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Only PDF or Word (.docx) files are accepted for the Lean Canvas.']));
            }

            $newName = $leanCanvasFile->getRandomName();
            $leanCanvasFile->move(WRITEPATH . 'uploads/applications', $newName);
            $data['leanCanvasPath'] = 'uploads/applications/' . $newName;
        }

        if (empty($data['leanCanvasPath'])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', array_merge($applicationModel->errors(), ['leanCanvas' => 'Please upload your completed Lean Canvas (.docx or PDF).']));
        }

        $updateData = $data;
        unset($updateData['privacyAgreement']);
        $updateData['statusRemark'] = $app['statusRemark'] ?? null;
        $updateData['revalidationTokenHash'] = null;
        $updateData['revalidationTokenExpiresAt'] = null;
        $updateData['revalidatedAt'] = date('Y-m-d H:i:s');

        if ($applicationModel->update((int) $app['id'], $updateData)) {
            $this->sendConfirmationEmail($data, true);

            return redirect()->to(site_url('apply/form/thank-you'))
                ->with('success', 'Your application has been updated successfully!')
                ->with('application_submitted', true);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Unable to update application. Please try again.');
    }

    // ──────────────────────────────────────────────
    // EMAIL — send applicant a copy of their responses
    // ──────────────────────────────────────────────
    private function sendConfirmationEmail(array $data, bool $isUpdate = false): void
    {
        $body = view('emails/application_confirmation', [
            'applicantName'         => $data['applicantName'],
            'applicantEmail'        => $data['applicantEmail'],
            'contactNumber'         => $data['contactNumber'],
            'startupName'           => $data['startupName'],
            'startupDescription'    => $data['startupDescription'],
            'mainRisk'              => $data['mainRisk'] ?? '',
            'shortTermGoals'        => $data['shortTermGoals'] ?? '',
            'videoPresentationLink' => $data['videoPresentationLink'] ?? '',
            'isUpdate'              => $isUpdate,
        ]);

        $gmail = new GmailMailer();

        if (! $gmail->send($data['applicantEmail'], $isUpdate ? 'ASOG TBI - Updated Application Received' : 'ASOG TBI - Application Received', $body)) {
            log_message('error', 'Confirmation email failed via Gmail API.');
        } else {
            log_message('info', 'Confirmation email sent to: ' . $data['applicantEmail']);
        }
    }

    /**
     * AJAX endpoint — check if an applicant email already exists in the DB.
    * GET /apply/form/check-email?email=...
     */
    public function checkEmail(): \CodeIgniter\HTTP\ResponseInterface
    {
        $email = trim($this->request->getGet('email') ?? '');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['exists' => false]);
        }

        $exists = ! $this->allowDuplicateEmails() && $this->applicationModel->emailExists($email);

        return $this->response->setJSON(['exists' => $exists]);
    }

    public function applyFormThankYou()
    {
        if (! session()->getFlashdata('application_submitted')) {
            return redirect()->to(site_url('apply/form'));
        }

        $data = [
            'title' => 'Application Submitted - ASOG TBI',
        ];

        return view('templates/header', $data)
            . view('incubatees/apply_thank_you', $data)
            . view('templates/footer');
    }

    private function allowDuplicateEmails(): bool
    {
        $settings = new LandingSettingModel();
        $raw = trim((string) $settings->getValue(
            LandingSettingModel::KEY_APPLY_ALLOW_DUPLICATE_EMAILS,
            '0'
        ));

        return $raw === '1';
    }

    private function buildApplyFormViewData(array $formInput = [], array $formErrors = [], ?string $formError = null, array $extra = []): array
    {
        return array_merge([
            'title' => 'Application Form - ASOG TBI',
            'heroSubtitle' => 'Incubation Program',
            'heroTitle' => ! empty($extra['isRevalidation']) ? 'Update Application' : 'Application Form',
            'heroDesc' => ! empty($extra['isRevalidation'])
                ? 'Review the remarks from our team and update your existing ASOG TBI application.'
                : 'Fill out the form below to apply for incubation at ASOG TBI.',
            'allowDuplicateEmails' => $this->allowDuplicateEmails(),
            'serverPostMaxSize' => (string) ini_get('post_max_size'),
            'serverUploadMaxFilesize' => (string) ini_get('upload_max_filesize'),
            'formInput' => $formInput,
            'formErrors' => $formErrors,
            'formError' => $formError,
        ], $extra);
    }

    private function renderApplyFormResponse(
        array $formInput = [],
        array $formErrors = [],
        ?string $formError = null,
        int $statusCode = 200
    ): \CodeIgniter\HTTP\ResponseInterface {
        $data = $this->buildApplyFormViewData($formInput, $formErrors, $formError);
        $html = view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/apply_form', $data)
            . view('templates/footer');

        return $this->response
            ->setStatusCode($statusCode)
            ->setBody($html);
    }

    private function renderRevalidationFormResponse(
        string $token,
        array $app,
        array $formInput = [],
        array $formErrors = [],
        ?string $formError = null,
        int $statusCode = 200
    ): \CodeIgniter\HTTP\ResponseInterface {
        $data = $this->buildApplyFormViewData(array_merge($app, $formInput), $formErrors, $formError, [
            'isRevalidation' => true,
            'revalidationToken' => $token,
            'revalidationAction' => site_url('apply/revalidate/' . $token),
            'revalidationRemark' => $app['statusRemark'] ?? '',
            'revalidationExpiresAt' => $app['revalidationTokenExpiresAt'] ?? '',
            'existingTeamCvPath' => $app['teamCvPath'] ?? '',
            'existingLeanCanvasPath' => $app['leanCanvasPath'] ?? '',
        ]);

        $html = view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('incubatees/apply_form', $data)
            . view('templates/footer');

        return $this->response
            ->setStatusCode($statusCode)
            ->setBody($html);
    }

    private function renderRevalidationUnavailable(): \CodeIgniter\HTTP\ResponseInterface
    {
        $data = [
            'title' => 'Application Update Unavailable - ASOG TBI',
        ];

        $html = view('templates/header', $data)
            . view('incubatees/revalidation_unavailable', $data)
            . view('templates/footer');

        return $this->response
            ->setStatusCode(404)
            ->setBody($html);
    }

    private function hasUploadedFile(array $files): bool
    {
        foreach ($files as $file) {
            if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }

        return false;
    }

    private function requestBodyExceededPhpLimits(): bool
    {
        $contentLength = (int) ($this->request->getServer('CONTENT_LENGTH') ?? 0);
        if ($contentLength <= 0) {
            return false;
        }

        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        if (! str_contains($contentType, 'multipart/form-data')) {
            return false;
        }

        $limitBytes = $this->parseIniSizeToBytes((string) ini_get('post_max_size'));
        if ($limitBytes !== null && $contentLength > $limitBytes) {
            return true;
        }

        return empty($this->request->getPost()) && empty($this->request->getFiles());
    }

    private function uploadLimitExceededMessage(): string
    {
        $perFileLimit = $this->uploadMaxFilesizeLabel();
        $totalLimit = trim((string) ini_get('post_max_size'));
        $totalLimitText = $totalLimit !== '' ? $totalLimit : 'the current server limit';

        return 'Your submission was too large for the server to process. '
            . 'The current server limits are ' . $perFileLimit . ' per file and '
            . $totalLimitText . ' total per submission. '
            . 'Please upload fewer or smaller files, then try again.';
    }

    private function uploadErrorMessage($file, string $label): ?string
    {
        if ($file === null) {
            return null;
        }

        $error = $file->getError();
        if ($error === UPLOAD_ERR_OK || $error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return $label . ' exceeds the current server upload limit of '
                . $this->uploadMaxFilesizeLabel() . ' per file.';
        }

        return $label . ' could not be uploaded. Please try again.';
    }

    private function multipleUploadErrorMessage(array $files, string $label): ?string
    {
        foreach ($files as $file) {
            $message = $this->uploadErrorMessage($file, $label);
            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    private function uploadMaxFilesizeLabel(): string
    {
        $limit = trim((string) ini_get('upload_max_filesize'));

        return $limit !== '' ? $limit : 'the current server limit';
    }

    private function parseIniSizeToBytes(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(\d+)([KMG]?)$/i', $value, $matches)) {
            return null;
        }

        $bytes = (int) $matches[1];
        $unit = strtoupper($matches[2] ?? '');

        return match ($unit) {
            'G' => $bytes * 1024 * 1024 * 1024,
            'M' => $bytes * 1024 * 1024,
            'K' => $bytes * 1024,
            default => $bytes,
        };
    }

}
