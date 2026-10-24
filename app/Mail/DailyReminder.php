<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Lembrete diário (J5-B10). Só o primeiro nome, a meta e os links: nada do
 * material nem das respostas do aluno (IAS-04).
 */
class DailyReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $goalMinutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Seus {$this->goalMinutes} minutos de hoje");
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.daily-reminder', with: [
            'name' => explode(' ', trim($this->user->name))[0],
            'goal' => $this->goalMinutes,
            'todayUrl' => route('today'),
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ]);
    }

    private function unsubscribeUrl(): string
    {
        return URL::signedRoute('reminders.unsubscribe', ['user' => $this->user->ulid]);
    }
}
