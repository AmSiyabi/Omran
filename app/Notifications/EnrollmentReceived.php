<?php

namespace App\Notifications;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Support\Baisa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Status-appropriate Arabic confirmation for the participant.
 */
class EnrollmentReceived extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Enrollment $enrollment,
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
        $cohort = $this->enrollment->cohort()->with('course')->first();
        $title = $cohort->displayTitle();
        $date = $cohort->starts_at->timezone(config('app.display_timezone'))->translatedFormat('j F Y');

        $mail = (new MailMessage)
            ->greeting('أهلاً '.$this->enrollment->full_name_ar.'،');

        return match ($this->enrollment->status) {
            EnrollmentStatus::Confirmed => $mail
                ->subject('تأكيد التسجيل — '.$title)
                ->line("تم تأكيد تسجيلك في «{$title}».")
                ->line('تاريخ البداية: '.$date)
                ->line($this->enrollment->amount_due_baisa > 0
                    ? 'رسوم المشاركة: '.Baisa::format($this->enrollment->amount_due_baisa)
                    : 'المشاركة مجانية.')
                ->line('سنوافيك بتفاصيل المكان والجدول قبل الموعد.'),

            EnrollmentStatus::Waitlisted => $mail
                ->subject('قائمة الانتظار — '.$title)
                ->line("اكتملت مقاعد «{$title}» حالياً، وقد أُضفت إلى قائمة الانتظار.")
                ->line('سنتواصل معك فور توفر مقعد.'),

            default => $mail
                ->subject('استلمنا طلبك — '.$title)
                ->line("وصلنا طلب تسجيلك في «{$title}» وهو قيد المراجعة.")
                ->line('سنعود إليك بالتأكيد قريباً.'),
        };
    }
}
