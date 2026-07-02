<?php

namespace App\Controllers;

use App\Libraries\GmailMailer;
use App\Models\AdminModel;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;

class Auth extends BaseController
{
    /**
     * Display the login form.
     */
    public function login()
    {
        if (session()->has('admin_id')) {
            return redirect()->to('/admin');
        }

        return view('admin/auth/login');
    }

    /**
     * Authenticate the admin user against the database.
     */
    public function authenticate()
    {
        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $adminModel = new AdminModel();
        $admin      = $adminModel->attempt($email, $password);

        if ($admin !== null) {
            $this->setAdminSession($admin);
            return redirect()->to('/admin');
        }

        return redirect()->back()->with('error', 'Invalid email or password.');
    }

    /**
     * Redirects to Google OAuth consent page.
     */
    public function google()
    {
        if (session()->has('admin_id')) {
            return redirect()->to('/admin');
        }

        $client = $this->buildGoogleClient();
        if ($client === null) {
            return redirect()->to('/asog-admin')->with('error', 'Google login is not configured yet.');
        }

        $state = bin2hex(random_bytes(16));
        session()->set('google_oauth_state', $state);

        $client->setState($state);

        return redirect()->to($client->createAuthUrl());
    }

    /**
     * Handles Google OAuth callback and logs in if account is authorized.
     */
    public function googleCallback()
    {
        $requestState = (string) $this->request->getGet('state');
        $sessionState = (string) session()->get('google_oauth_state');
        session()->remove('google_oauth_state');

        if ($sessionState === '' || $requestState === '' || ! hash_equals($sessionState, $requestState)) {
            return redirect()->to('/asog-admin')->with('error', 'Invalid Google login state. Please try again.');
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            return redirect()->to('/asog-admin')->with('error', 'Google login was cancelled or failed.');
        }

        $client = $this->buildGoogleClient();
        if ($client === null) {
            return redirect()->to('/asog-admin')->with('error', 'Google login is not configured yet.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (! is_array($token) || isset($token['error'])) {
            return redirect()->to('/asog-admin')->with('error', 'Google could not verify your account.');
        }

        $client->setAccessToken($token);

        $oauth2         = new Oauth2($client);
        $googleUser     = $oauth2->userinfo->get();
        $email          = strtolower(trim((string) ($googleUser->email ?? '')));
        $googleSub      = trim((string) ($googleUser->id ?? $googleUser->sub ?? ''));
        $googleName     = trim((string) ($googleUser->name ?? ''));
        $givenName      = trim((string) ($googleUser->givenName ?? $googleUser->given_name ?? ''));
        $familyName     = trim((string) ($googleUser->familyName ?? $googleUser->family_name ?? ''));
        $fallbackName   = trim($givenName . ' ' . $familyName);
        $fullName       = $googleName !== '' ? $googleName : $fallbackName;
        $isVerified     = (bool) ($googleUser->verifiedEmail ?? false);

        if ($email === '' || ! $isVerified) {
            return redirect()->to('/asog-admin')->with('error', 'Google email is not verified.');
        }

        if (! $this->isAllowedGoogleDomain($email)) {
            return redirect()->to('/asog-admin')->with('error', 'Google account domain is not authorized.');
        }

        $adminModel = new AdminModel();
        $admin      = $adminModel->findByGoogleAccount($email, $googleSub);

        if ($admin === null) {
            return redirect()->to('/asog-admin')->with('error', 'This Google account is not linked to an authorized admin profile.');
        }

        $updateData = [
            'googleEmail'  => $email,
            'googleSub'    => $googleSub !== '' ? $googleSub : ($admin['googleSub'] ?? null),
            'lastLoginAt' => date('Y-m-d H:i:s'),
        ];

        if ($fullName !== '') {
            $updateData['fullName'] = $fullName;
            $admin['fullName']      = $fullName;
        }

        $adminModel->update($admin['id'], $updateData);

        $this->setAdminSession($admin);

        return redirect()->to('/admin');
    }

    /**
     * Logout the admin user.
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/asog-admin')->with('success', 'Logged out successfully.');
    }

    /**
     * Display the forgot password form.
     */
    public function forgotPassword()
    {
        return view('admin/auth/forgot_password');
    }

    /**
     * Send the password reset link to the provided email.
     */
    public function sendResetLink()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Please enter a valid email address.');
        }

        $resetAttempts = session()->get('reset_attempts') ?? [];
        $resetAttempts = array_values(array_filter($resetAttempts, static fn (int $t): bool => $t > time() - 3600));
        if (count($resetAttempts) >= 5) {
            return redirect()->back()->with('error', 'Too many reset requests. Please try again later.');
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $adminModel = new AdminModel();
        $admin = $adminModel->findByEmail($email);

        if ($admin !== null && ! empty($admin['isActive'])) {
            $adminModel->update($admin['id'], [
                'resetToken'          => $tokenHash,
                'resetTokenExpiresAt' => $expiresAt,
            ]);

            $resetUrl = site_url('asog-admin/reset-password/' . $token);

            $gmail = new GmailMailer();
            $sent = $gmail->send($email, 'Password Reset - ASOG TBI Admin', view('emails/password_reset', [
                'resetUrl' => $resetUrl,
                'adminName' => $admin['fullName'],
            ]));

            if (! $sent) {
                log_message('error', 'Password reset email failed via Gmail API for admin #' . $admin['id'] . '.');
            }
        }

        $resetAttempts[] = time();
        session()->set('reset_attempts', $resetAttempts);

        return redirect()->to('/asog-admin/forgot-password')->with('success', 'If an account with that email exists, a password reset link has been sent.');
    }

    /**
     * Display the reset password form.
     */
    public function resetPassword(string $token)
    {
        $adminModel = new AdminModel();
        $admin = $adminModel->findByResetToken($token);

        if ($admin === null) {
            return redirect()->to('/asog-admin')->with('error', 'This password reset link is invalid or has expired.');
        }

        return view('admin/auth/reset_password', ['token' => $token]);
    }

    /**
     * Update the password using the reset token.
     */
    public function updateForgottenPassword()
    {
        $token = trim((string) $this->request->getPost('token'));
        $password = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if ($token === '') {
            return redirect()->to('/asog-admin')->with('error', 'Invalid password reset request.');
        }

        if ($password === '' || strlen($password) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters.')->withInput();
        }

        if ($password !== $passwordConfirm) {
            return redirect()->back()->with('error', 'Passwords do not match.')->withInput();
        }

        $updateAttempts = session()->get('reset_update_attempts') ?? [];
        $updateAttempts = array_values(array_filter($updateAttempts, static fn (int $t): bool => $t > time() - 600));
        if (count($updateAttempts) >= 10) {
            return redirect()->back()->with('error', 'Too many attempts. Please try again later.')->withInput();
        }

        $adminModel = new AdminModel();
        $admin = $adminModel->findByResetToken($token);

        if ($admin === null) {
            $updateAttempts[] = time();
            session()->set('reset_update_attempts', $updateAttempts);
            return redirect()->to('/asog-admin')->with('error', 'This password reset link is invalid or has expired.');
        }

        $adminModel->update($admin['id'], [
            'password' => $password,
        ]);
        $adminModel->clearResetToken($admin['id']);

        session()->remove('reset_update_attempts');

        return redirect()->to('/asog-admin')->with('success', 'Password updated successfully. You can now sign in.');
    }

    private function buildGoogleClient(): ?GoogleClient
    {
        if (! class_exists(GoogleClient::class)) {
            return null;
        }

        $clientId     = trim((string) env('googleOAuthClientId', ''));
        $clientSecret = trim((string) env('googleOAuthClientSecret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $redirectUri = trim((string) env('googleOAuthRedirectUri', ''));
        if ($redirectUri === '') {
            $redirectUri = site_url('asog-admin/google/callback');
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

    private function setAdminSession(array $admin): void
    {
        session()->set([
            'admin_id'    => $admin['id'],
            'admin_name'  => $admin['fullName'],
            'admin_email' => $admin['email'],
            'admin_role'  => $admin['role'],
            'logged_in'   => true,
        ]);
    }

    private function isAllowedGoogleDomain(string $email): bool
    {
        $rawAllowedDomains = trim((string) env('googleOAuthAllowedDomains', ''));

        if ($rawAllowedDomains === '') {
            return true;
        }

        $allowedDomains = array_values(array_filter(array_map(
            static fn(string $domain): string => strtolower(trim($domain)),
            explode(',', $rawAllowedDomains)
        )));

        if ($allowedDomains === []) {
            return true;
        }

        $emailDomain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        return in_array($emailDomain, $allowedDomains, true);
    }
}
