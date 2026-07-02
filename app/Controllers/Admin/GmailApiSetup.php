<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\GmailApi;
use Google\Client as GoogleClient;

class GmailApiSetup extends BaseController
{
    public function connect()
    {
        $config = config('GmailApi');
        if (! $this->setupAllowed($config)) {
            return $this->response->setStatusCode(404)->setBody('Gmail API setup is disabled.');
        }

        $client = $this->client($config);
        if ($client === null) {
            return $this->response->setStatusCode(500)->setBody('Gmail API OAuth client is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session()->set('gmail_api_oauth_state', $state);
        $client->setState($state);

        return redirect()->to($client->createAuthUrl());
    }

    public function callback()
    {
        $config = config('GmailApi');
        if (! $this->setupAllowed($config)) {
            return $this->response->setStatusCode(404)->setBody('Gmail API setup is disabled.');
        }

        $requestState = (string) $this->request->getGet('state');
        $sessionState = (string) session()->get('gmail_api_oauth_state');
        session()->remove('gmail_api_oauth_state');

        if ($sessionState === '' || $requestState === '' || ! hash_equals($sessionState, $requestState)) {
            return $this->response->setStatusCode(400)->setBody('Invalid Gmail API OAuth state. Please try again.');
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            return $this->response->setStatusCode(400)->setBody('Gmail API authorization was cancelled or failed.');
        }

        $client = $this->client($config);
        if ($client === null) {
            return $this->response->setStatusCode(500)->setBody('Gmail API OAuth client is not configured.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (! is_array($token) || isset($token['error'])) {
            $error = is_array($token) ? (string) ($token['error'] ?? 'unknown_error') : 'invalid_token_response';
            return $this->response->setStatusCode(400)->setBody('Unable to fetch Gmail API token: ' . esc($error));
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');
        if ($refreshToken === '') {
            return $this->response->setStatusCode(400)->setBody('Google did not return a refresh token. Revoke app access for this Gmail account, then try the connect route again with prompt=consent.');
        }

        return view('admin/gmail_api/token', [
            'refreshToken' => $refreshToken,
        ]);
    }

    private function setupAllowed(GmailApi $config): bool
    {
        return $config->setupEnabled && class_exists(GoogleClient::class);
    }

    private function client(GmailApi $config): ?GoogleClient
    {
        if ($config->clientId === '' || $config->clientSecret === '') {
            return null;
        }

        $client = new GoogleClient();
        $client->setClientId($config->clientId);
        $client->setClientSecret($config->clientSecret);
        $client->setAccessType('offline');
        $client->setPrompt('consent select_account');
        $client->setIncludeGrantedScopes(false);
        $client->addScope($config->scope);
        $client->setRedirectUri($config->redirectUri !== '' ? $config->redirectUri : site_url('asog-admin/gmail-api/callback'));

        return $client;
    }
}
