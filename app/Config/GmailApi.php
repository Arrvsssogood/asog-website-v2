<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class GmailApi extends BaseConfig
{
    public bool $enabled = false;
    public bool $setupEnabled = false;

    public string $senderEmail = '';
    public string $senderName = 'ASOG-TBI';
    public string $adminRecipient = '';

    public string $clientId = '';
    public string $clientSecret = '';
    public string $refreshToken = '';
    public string $redirectUri = '';

    public string $scope = 'https://www.googleapis.com/auth/gmail.send';
    public int $accessTokenCacheTtl = 3300;

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('gmailApi.enabled', false), FILTER_VALIDATE_BOOL);
        $this->setupEnabled = filter_var(env('gmailApi.setupEnabled', false), FILTER_VALIDATE_BOOL);

        $this->senderEmail = trim((string) env('gmailApi.senderEmail', ''));
        $this->senderName = trim((string) env('gmailApi.senderName', 'ASOG-TBI'));
        $this->adminRecipient = trim((string) env('gmailApi.adminRecipient', ''));

        $this->clientId = trim((string) env('gmailApi.clientId', ''));
        $this->clientSecret = trim((string) env('gmailApi.clientSecret', ''));
        $this->refreshToken = trim((string) env('gmailApi.refreshToken', ''));
        $this->redirectUri = trim((string) env('gmailApi.redirectUri', ''));

        $cacheTtl = (int) env('gmailApi.accessTokenCacheTtl', $this->accessTokenCacheTtl);
        if ($cacheTtl > 0) {
            $this->accessTokenCacheTtl = $cacheTtl;
        }
    }
}
