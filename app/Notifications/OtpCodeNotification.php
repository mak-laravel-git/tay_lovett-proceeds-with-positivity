<?php

namespace App\Notifications;

use App\Enums\OtpPurpose;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OtpCodeNotification extends Notification
{
    public function __construct(
        public string $code,
        public OtpPurpose $purpose,
        public int $expiresInMinutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = (string) config('app.name');

        return (new MailMessage)
            ->subject("Your {$site} code: {$this->code}")
            ->greeting('Hello,')
            ->line('Use this code to '.$this->purpose->reason().':')
            ->line(new HtmlString('<p style="margin:16px 0;font-size:32px;font-weight:800;letter-spacing:10px;color:#0a1018;">'.e($this->code).'</p>'))
            ->line("The code works once and expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not ask for this, you can ignore this email. Nobody can use the code without access to your inbox. Never share it with anyone.')
            ->salutation("— {$site}");
    }
}
