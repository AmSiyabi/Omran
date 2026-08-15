<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NewDeviceLogin extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ?string $ip,
        public readonly Carbon $time,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $localTime = $this->time
            ->copy()
            ->timezone(config('app.display_timezone', 'Asia/Muscat'))
            ->format('Y-m-d H:i');

        return (new MailMessage)
            ->subject(__('auth.new_device_subject'))
            ->greeting(__('common.welcome'))
            ->line(__('auth.new_device_line1'))
            ->line(__('auth.new_device_ip', ['ip' => $this->ip ?? '—']))
            ->line(__('auth.new_device_time', ['time' => $localTime]))
            ->line(__('auth.new_device_warning'));
    }
}
