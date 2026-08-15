<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ContactMessage $contactMessage,
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
        return (new MailMessage)
            ->subject('رسالة جديدة من موقع عمران — '.($this->contactMessage->subject ?? $this->contactMessage->name))
            ->greeting('رسالة تواصل جديدة')
            ->line('الاسم: '.$this->contactMessage->name)
            ->line('البريد: '.$this->contactMessage->email)
            ->line('الهاتف: '.($this->contactMessage->phone ?? '—'))
            ->line('الموضوع: '.($this->contactMessage->subject ?? '—'))
            ->line('الرسالة:')
            ->line($this->contactMessage->message);
    }
}
