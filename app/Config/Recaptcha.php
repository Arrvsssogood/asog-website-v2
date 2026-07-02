<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Recaptcha extends BaseConfig
{
    public bool $enabled = false;
    public string $projectId = '';
    public string $siteKey = '';
    public string $apiKey = '';
    public float $minimumScore = 0.5;
    public string $enforcement = 'block';
    public array $allowedHostnames = [];

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('recaptcha.enabled', false), FILTER_VALIDATE_BOOL);
        $this->projectId = trim((string) env('recaptcha.projectId', ''));
        $this->siteKey = trim((string) env('recaptcha.siteKey', ''));
        $this->apiKey = trim((string) env('recaptcha.apiKey', ''));
        $this->enforcement = strtolower(trim((string) env('recaptcha.enforcement', 'block')));

        $minimumScore = (float) env('recaptcha.minimumScore', $this->minimumScore);
        if ($minimumScore >= 0.0 && $minimumScore <= 1.0) {
            $this->minimumScore = $minimumScore;
        }

        $hostnames = trim((string) env('recaptcha.allowedHostnames', ''));
        if ($hostnames !== '') {
            $this->allowedHostnames = array_values(array_filter(array_map(
                static fn (string $host): string => strtolower(trim($host)),
                explode(',', $hostnames)
            )));
        }
    }
}
