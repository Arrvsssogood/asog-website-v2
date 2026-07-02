<?php

namespace App\Controllers;

use App\Libraries\GmailMailer;
use App\Libraries\RecaptchaVerifier;

class Contact extends BaseController
{
    private const MIN_MESSAGE_WORDS = 10;
    private const MAX_MESSAGE_WORDS = 5000;

    public function index(): string
    {
        $data = [
            'title'        => 'Contact - ASOG TBI',
            'heroSubtitle' => 'Reach Out',
            'heroTitle'    => 'Contact Us',
            'heroDesc'     => 'Have questions or want to collaborate? Get in touch with the ASOG TBI team.',
        ];

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('contact/index', $data)
            . view('templates/footer');
    }

    /**
     * Handle contact form submission.
     *
     * 1. Validate input
     * 2. Persist to `contact_messages` table
     * 3. Send email notification to admin (best-effort)
     */
    
    public function send()
    {
        $data = [
            'name'    => $this->request->getPost('name'),
            'email'   => $this->request->getPost('email'),
            'message' => $this->request->getPost('message'),
        ];

        $message = trim((string) $data['message']);
        $wordCount = $message === '' ? 0 : preg_match_all('/\S+/u', $message, $matches);

        if ($message === '') {
            setToast('error', 'Please fill in all fields correctly.');
            return redirect()->back()->withInput();
        }

        if ($wordCount !== false && $wordCount < self::MIN_MESSAGE_WORDS) {
            setToast('error', 'Message must be at least ' . self::MIN_MESSAGE_WORDS . ' words.');
            return redirect()->back()->withInput()->with('errors', [
                'message' => 'Message must be at least ' . self::MIN_MESSAGE_WORDS . ' words.'
            ]);
        }

        if ($wordCount !== false && $wordCount > self::MAX_MESSAGE_WORDS) {
            setToast('error', 'Message cannot exceed ' . self::MAX_MESSAGE_WORDS . ' words.');
            return redirect()->back()->withInput()->with('errors', [
                'message' => 'Message cannot exceed ' . self::MAX_MESSAGE_WORDS . ' words.'
            ]);
        }

        $data['message'] = $message;

        if (! $this->contactModel->validate($data)) {
            setToast('error', 'Please fill in all fields correctly.');
            return redirect()->back()->withInput();
        }

        $recaptcha = new RecaptchaVerifier();
        if (! $recaptcha->verifyRequest('contact_send')) {
            setToast('error', $recaptcha->failureMessage());
            return redirect()->back()->withInput();
        }

        if (! $this->contactModel->insert($data)) {
            log_message('error', 'Contact message DB insert failed.');
            setToast('error', 'Something went wrong. Please try again.');
            return redirect()->back()->withInput();
        }

        $this->notifyAdmin($data);

        setToast('success', 'Your message has been sent! We\'ll get back to you soon.');
        return redirect()->to(site_url('contact'));
    }

    // ──────────────────────────────────────────────
    // EMAIL — notify admin of new contact message
    // ──────────────────────────────────────────────
    private function notifyAdmin(array $data): void
    {
        $body = view('emails/contact_notification', [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'message' => $data['message'],
            'sentAt'  => date('F j, Y \a\t g:i A'),
        ]);

        $gmail = new GmailMailer();
        $config = config('GmailApi');
        $recipient = $config->adminRecipient !== '' ? $config->adminRecipient : $config->senderEmail;

        if ($recipient === '') {
            log_message('info', 'Contact notification skipped - Gmail API admin recipient is not configured.');
            return;
        }

        if (! $gmail->send($recipient, 'ASOG TBI - New Contact Message from ' . $data['name'], $body, [
            'email' => (string) $data['email'],
            'name' => (string) $data['name'],
        ])) {
            log_message('error', 'Contact notification email failed via Gmail API.');
        } else {
            log_message('info', 'Contact notification sent for: ' . $data['email']);
        }
    }
}
