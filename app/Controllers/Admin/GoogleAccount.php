<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class GoogleAccount extends BaseController
{
    public function index()
    {
        return redirect()->to(site_url('admin/settings'));
    }

    public function connect()
    {
        $client = $this->buildGoogleClient();
        if ($client === null) {
            setToast('error', 'Google login is not configured yet.');
            return redirect()->to(site_url('admin/settings'));
        }

        $state = bin2hex(random_bytes(16));
        session()->set('google_account_link_state', $state);
        $client->setState($state);

        return redirect()->to($client->createAuthUrl());
    }

    public function callback()
    {
        $requestState = (string) $this->request->getGet('state');
        $sessionState = (string) session()->get('google_account_link_state');
        session()->remove('google_account_link_state');

        if ($sessionState === '' || $requestState === '' || ! hash_equals($sessionState, $requestState)) {
            setToast('error', 'Invalid Google account link request. Please try again.');
            return redirect()->to(site_url('admin/settings'));
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            setToast('error', 'Google account linking was cancelled or failed.');
            return redirect()->to(site_url('admin/settings'));
        }

        $client = $this->buildGoogleClient();
        if ($client === null) {
            setToast('error', 'Google login is not configured yet.');
            return redirect()->to(site_url('admin/settings'));
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (! is_array($token) || isset($token['error'])) {
            setToast('error', 'Google could not verify your account.');
            return redirect()->to(site_url('admin/settings'));
        }

        $client->setAccessToken($token);
        $googleUser = (new Oauth2($client))->userinfo->get();
        $email = strtolower(trim((string) ($googleUser->email ?? '')));
        $googleSub = trim((string) ($googleUser->id ?? $googleUser->sub ?? ''));
        $googleName = trim((string) ($googleUser->name ?? ''));
        $isVerified = (bool) ($googleUser->verifiedEmail ?? false);
        $admin = $this->currentAdmin();

        if ($admin === null) {
            setToast('error', 'Account not found.');
            return redirect()->to(site_url('admin'));
        }

        if ($email === '' || ! $isVerified) {
            setToast('error', 'Google email is not verified.');
            return redirect()->to(site_url('admin/settings'));
        }

        if (! $this->isAllowedGoogleDomain($email)) {
            setToast('error', 'Google account domain is not authorized.');
            return redirect()->to(site_url('admin/settings'));
        }

        if ($this->googleIdentityBelongsToAnotherAdmin($email, $googleSub, (int) $admin['id'])) {
            setToast('error', 'That Google account is already linked to another admin.');
            return redirect()->to(site_url('admin/settings'));
        }

        $updateData = [
            'googleEmail' => $email,
            'googleSub' => $googleSub !== '' ? $googleSub : null,
            'lastLoginAt' => date('Y-m-d H:i:s'),
        ];

        if ($googleName !== '') {
            $updateData['fullName'] = $googleName;
            session()->set('admin_name', $googleName);
        }

        if (! $this->adminModel->update($admin['id'], $updateData)) {
            setToast('error', 'Unable to link Google account.');
            return redirect()->to(site_url('admin/settings'));
        }

        setToast('success', 'Google account linked.');
        return redirect()->to(site_url('admin/settings'));
    }

    public function unlink()
    {
        $admin = $this->currentAdmin();
        if ($admin === null) {
            setToast('error', 'Account not found.');
            return redirect()->to(site_url('admin'));
        }

        if (! $this->adminModel->update($admin['id'], [
            'googleEmail' => null,
            'googleSub' => null,
        ])) {
            setToast('error', 'Unable to unlink Google account.');
            return redirect()->to(site_url('admin/settings'));
        }

        setToast('success', 'Google account unlinked.');
        return redirect()->to(site_url('admin/settings'));
    }

    private function currentAdmin(): ?array
    {
        $adminId = (int) session()->get('admin_id');
        if ($adminId <= 0) {
            return null;
        }

        $admin = $this->adminModel->find($adminId);
        return is_array($admin) ? $admin : null;
    }

    private function buildGoogleClient(): ?GoogleClient
    {
        if (! class_exists(GoogleClient::class)) {
            return null;
        }

        $clientId = trim((string) env('googleOAuthClientId', ''));
        $clientSecret = trim((string) env('googleOAuthClientSecret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);

        $redirectUri = trim((string) env('googleOAuthAccountRedirectUri', ''));
        if ($redirectUri === '') {
            $redirectUri = site_url('admin/google-account/callback');
        }

        $client->setRedirectUri($redirectUri);
        $client->setAccessType('online');
        $client->setPrompt('select_account');
        $client->setIncludeGrantedScopes(true);
        $client->addScope('openid');
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }

    private function isAllowedGoogleDomain(string $email): bool
    {
        $rawAllowedDomains = trim((string) env('googleOAuthAllowedDomains', ''));
        if ($rawAllowedDomains === '') {
            return true;
        }

        $allowedDomains = array_values(array_filter(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            explode(',', $rawAllowedDomains)
        )));

        if ($allowedDomains === []) {
            return true;
        }

        $emailDomain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        return in_array($emailDomain, $allowedDomains, true);
    }

    private function googleIdentityBelongsToAnotherAdmin(string $email, string $googleSub, int $currentAdminId): bool
    {
        $builder = $this->adminModel->builder()
            ->where('id !=', $currentAdminId)
            ->groupStart()
                ->where('googleEmail', $email);

        if ($googleSub !== '') {
            $builder->orWhere('googleSub', $googleSub);
        }

        return $builder
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
