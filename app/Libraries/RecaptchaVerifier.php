<?php

namespace App\Libraries;

use Config\Recaptcha as RecaptchaConfig;
use Google\Client as GoogleClient;
use Google\Service\RecaptchaEnterprise;
use Google\Service\RecaptchaEnterprise\GoogleCloudRecaptchaenterpriseV1Assessment;
use Google\Service\RecaptchaEnterprise\GoogleCloudRecaptchaenterpriseV1Event;
use Throwable;

class RecaptchaVerifier
{
    public function __construct(
        private ?RecaptchaConfig $config = null
    ) {
        $this->config ??= config('Recaptcha');
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled;
    }

    public function isConfigured(): bool
    {
        return $this->config->enabled
            && $this->config->projectId !== ''
            && $this->config->siteKey !== ''
            && $this->config->apiKey !== ''
            && class_exists(GoogleClient::class)
            && class_exists(RecaptchaEnterprise::class);
    }

    public function verifyRequest(string $expectedAction): bool
    {
        if (! $this->config->enabled) {
            return true;
        }

        if (! $this->isConfigured()) {
            log_message('error', 'reCAPTCHA blocked - service is enabled but not configured.');
            return false;
        }

        $request = service('request');
        $token = trim((string) $request->getPost('recaptchaToken'));
        $postedAction = trim((string) $request->getPost('recaptchaAction'));

        if ($token === '' || $postedAction !== $expectedAction) {
            log_message('warning', 'reCAPTCHA blocked - missing token or action mismatch. expected={expected} posted={posted}', [
                'expected' => $expectedAction,
                'posted' => $postedAction,
            ]);
            return false;
        }

        try {
            $client = new GoogleClient();
            $client->setDeveloperKey($this->config->apiKey);

            $service = new RecaptchaEnterprise($client);
            $event = new GoogleCloudRecaptchaenterpriseV1Event();
            $event->setToken($token);
            $event->setSiteKey($this->config->siteKey);
            $event->setExpectedAction($expectedAction);
            $event->setUserAgent((string) $request->getUserAgent());
            $event->setUserIpAddress((string) $request->getIPAddress());

            $assessment = new GoogleCloudRecaptchaenterpriseV1Assessment();
            $assessment->setEvent($event);

            $response = $service->projects_assessments->create(
                'projects/' . $this->config->projectId,
                $assessment
            );

            return $this->passesAssessment($response, $expectedAction);
        } catch (Throwable $e) {
            log_message('error', 'reCAPTCHA assessment failed: ' . $this->safeError($e));
            return false;
        }
    }

    public function failureMessage(): string
    {
        return 'We could not verify your submission. Please refresh the page and try again.';
    }

    private function passesAssessment(GoogleCloudRecaptchaenterpriseV1Assessment $assessment, string $expectedAction): bool
    {
        $tokenProperties = $assessment->getTokenProperties();
        $riskAnalysis = $assessment->getRiskAnalysis();

        if ($tokenProperties === null || ! $tokenProperties->getValid()) {
            log_message('warning', 'reCAPTCHA blocked - invalid token. reason={reason}', [
                'reason' => $tokenProperties ? (string) $tokenProperties->getInvalidReason() : 'missing_token_properties',
            ]);
            return false;
        }

        $action = (string) $tokenProperties->getAction();
        $hostname = strtolower((string) $tokenProperties->getHostname());
        $score = $riskAnalysis ? (float) $riskAnalysis->getScore() : 0.0;

        if ($action !== $expectedAction) {
            log_message('warning', 'reCAPTCHA blocked - action mismatch. expected={expected} actual={actual}', [
                'expected' => $expectedAction,
                'actual' => $action,
            ]);
            return false;
        }

        if (! $this->hostnameAllowed($hostname)) {
            log_message('warning', 'reCAPTCHA blocked - hostname mismatch. hostname={hostname}', [
                'hostname' => $hostname,
            ]);
            return false;
        }

        if ($score < $this->config->minimumScore) {
            log_message('warning', 'reCAPTCHA blocked - score below threshold. action={action} hostname={hostname} score={score} threshold={threshold}', [
                'action' => $action,
                'hostname' => $hostname,
                'score' => $score,
                'threshold' => $this->config->minimumScore,
            ]);

            return $this->config->enforcement !== 'block';
        }

        log_message('info', 'reCAPTCHA passed. action={action} hostname={hostname} score={score}', [
            'action' => $action,
            'hostname' => $hostname,
            'score' => $score,
        ]);

        return true;
    }

    private function hostnameAllowed(string $hostname): bool
    {
        if ($hostname === '' || $this->config->allowedHostnames === []) {
            return true;
        }

        foreach ($this->config->allowedHostnames as $allowedHostname) {
            if ($hostname === $allowedHostname || str_ends_with($hostname, '.' . $allowedHostname)) {
                return true;
            }
        }

        return false;
    }

    private function safeError(Throwable $e): string
    {
        return preg_replace('/(key|api_key|token|secret)["\':=\s]+[^,\s"]+/i', '$1=[redacted]', $e->getMessage())
            ?? 'Unknown reCAPTCHA error';
    }
}
