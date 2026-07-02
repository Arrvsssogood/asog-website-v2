<?php

namespace App\Libraries;

use Config\GmailApi;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Throwable;

class GmailMailer
{
    private const CACHE_KEY = 'gmail_api_access_token';

    public function __construct(
        private ?GmailApi $config = null
    ) {
        $this->config ??= config('GmailApi');
    }

    public function isConfigured(): bool
    {
        return $this->config->enabled
            && $this->config->senderEmail !== ''
            && $this->config->clientId !== ''
            && $this->config->clientSecret !== ''
            && $this->config->refreshToken !== ''
            && class_exists(GoogleClient::class);
    }

    public function send(string $to, string $subject, string $htmlBody, ?array $replyTo = null): bool
    {
        if (! $this->isConfigured()) {
            log_message('info', 'Gmail API email skipped - Gmail API is not configured.');
            return false;
        }

        try {
            return $this->sendWithRetry($to, $subject, $htmlBody, $replyTo, false);
        } catch (Throwable $e) {
            log_message('error', 'Gmail API email failed: ' . $this->safeError($e));
            return false;
        }
    }

    private function sendWithRetry(string $to, string $subject, string $htmlBody, ?array $replyTo, bool $didRetry): bool
    {
        try {
            $client = $this->authenticatedClient(false);
            $service = new Gmail($client);
            $message = new Message();
            $message->setRaw($this->base64UrlEncode($this->buildMime($to, $subject, $htmlBody, $replyTo)));

            $service->users_messages->send('me', $message);
            return true;
        } catch (Throwable $e) {
            if (! $didRetry && $this->shouldRefreshToken($e)) {
                cache()->delete(self::CACHE_KEY);
                $client = $this->authenticatedClient(true);
                $service = new Gmail($client);
                $message = new Message();
                $message->setRaw($this->base64UrlEncode($this->buildMime($to, $subject, $htmlBody, $replyTo)));
                $service->users_messages->send('me', $message);

                return true;
            }

            throw $e;
        }
    }

    private function authenticatedClient(bool $forceRefresh): GoogleClient
    {
        $client = $this->baseClient();
        $token = $forceRefresh ? null : $this->cachedAccessToken();

        if ($token === null) {
            $token = $this->refreshAccessToken($client);
        }

        $client->setAccessToken([
            'access_token' => $token,
            'expires_in' => $this->config->accessTokenCacheTtl,
            'created' => time(),
        ]);

        return $client;
    }

    private function baseClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId($this->config->clientId);
        $client->setClientSecret($this->config->clientSecret);
        $client->setAccessType('offline');
        $client->addScope($this->config->scope);

        if ($this->config->redirectUri !== '') {
            $client->setRedirectUri($this->config->redirectUri);
        }

        return $client;
    }

    private function cachedAccessToken(): ?string
    {
        $cached = cache()->get(self::CACHE_KEY);

        if (! is_array($cached)
            || empty($cached['access_token'])
            || empty($cached['expires_at'])
            || ! is_string($cached['access_token'])
            || (int) $cached['expires_at'] <= time() + 60
        ) {
            return null;
        }

        return $cached['access_token'];
    }

    private function refreshAccessToken(GoogleClient $client): string
    {
        $token = $client->fetchAccessTokenWithRefreshToken($this->config->refreshToken);

        if (! is_array($token) || isset($token['error']) || empty($token['access_token'])) {
            $error = is_array($token) ? (string) ($token['error'] ?? 'unknown_error') : 'invalid_token_response';
            throw new \RuntimeException('Unable to refresh Gmail API access token: ' . $error);
        }

        $ttl = max(60, min($this->config->accessTokenCacheTtl, ((int) ($token['expires_in'] ?? 3600)) - 60));
        cache()->save(self::CACHE_KEY, [
            'access_token' => (string) $token['access_token'],
            'expires_at' => time() + $ttl,
        ], $ttl);

        return (string) $token['access_token'];
    }

    private function buildMime(string $to, string $subject, string $htmlBody, ?array $replyTo): string
    {
        $headers = [
            'From: ' . $this->formatAddress($this->config->senderEmail, $this->config->senderName),
            'To: ' . $this->formatAddress($to),
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];

        if ($replyTo !== null && ! empty($replyTo['email'])) {
            $headers[] = 'Reply-To: ' . $this->formatAddress((string) $replyTo['email'], (string) ($replyTo['name'] ?? ''));
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . quoted_printable_encode($htmlBody);
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        $email = $this->sanitizeHeader($email);
        $name = $this->sanitizeHeader($name);

        if ($name === '') {
            return '<' . $email . '>';
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        $value = $this->sanitizeHeader($value);

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function sanitizeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function shouldRefreshToken(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'invalid')
            || str_contains($message, 'unauthorized')
            || str_contains($message, '401')
            || str_contains($message, '403');
    }

    private function safeError(Throwable $e): string
    {
        return preg_replace('/(access_token|refresh_token|client_secret)["\':=\s]+[^,\s"]+/i', '$1=[redacted]', $e->getMessage()) ?? 'Unknown Gmail API error';
    }
}
