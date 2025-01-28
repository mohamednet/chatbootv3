<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialInstructions extends Mailable
{
    use Queueable, SerializesModels;

    public $template;
    public $device;
    public $app;
    public $username;
    public $password;
    public $sourceUrl;

    public function __construct($template)
    {
        $this->template = $template;
        $this->device = $template['device'] ?? 'your device';
        $this->app = $template['app'] ?? 'the app';
        $this->username = '[Your Username]';
        $this->password = '[Your Password]';
        $this->sourceUrl = '[IPTV Source URL]';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your IPTV Setup Instructions',
        );
    }

    public function content(): Content
    {
        try {
            return new Content(
                view: 'emails.trial-instructions',
                with: [
                    'device' => $this->device,
                    'app' => $this->app,
                    'username' => $this->username,
                    'password' => $this->password,
                    'sourceUrl' => $this->sourceUrl
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to render trial instructions email', [
                'error' => $e->getMessage(),
                'template' => $this->template
            ]);
            throw $e;
        }
    }

    public function attachments(): array
    {
        return [];
    }
}
