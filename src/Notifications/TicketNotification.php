<?php

namespace Padmission\Tickets\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Padmission\Tickets\Models\Ticket;
use Padmission\Tickets\Services\TicketActivityService;
use Padmission\Tickets\Services\TicketUrlService;

class TicketNotification extends Notification
{
    public $notificationType;

    public function __construct(
        protected Ticket $ticket,
        protected $event,
    ) {
        $this->notificationType = str($this->event::class)
            ->afterLast('\\')
            ->replace('Ticket', '')
            ->replace('Event', '')
            ->lower()
            ->toString();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend($notifiable): bool
    {
        /*
         * A created event is an acknowledgement of the ticket itself, so it
         * must not be gated on unread activity: the ticket may not have any
         * activities yet at creation time (or the notification may run before
         * they are written when dispatched synchronously). A closed event
         * likewise always deserves a distinct email, even when a debounced
         * activity notification already consumed the closing activity.
         */
        if (in_array($this->notificationType, ['created', 'closed'], true)) {
            return true;
        }

        $activityService = resolve(TicketActivityService::class);
        $maxEvents = config('padmission-tickets.notification-max-events', 10);

        $activities = $activityService->getUnreadActivities(
            $this->ticket,
            $notifiable,
            $maxEvents,
        );

        return $activities->isNotEmpty();
    }

    public function toMail($notifiable): MailMessage
    {
        $activityService = resolve(TicketActivityService::class);
        $urlService = resolve(TicketUrlService::class);

        $maxEvents = config('padmission-tickets.notification-max-events', 10);

        $activities = $activityService->getUnreadActivities(
            $this->ticket,
            $notifiable,
            $maxEvents,
        );

        $latestActivity = $activities->last();

        if ($latestActivity) {
            $activityService->markAsSent($this->ticket, $notifiable, $latestActivity->id);
        }

        $hasMoreActivities = $activities->count() > $maxEvents;

        /*
         * Intentional: the fetch is newest-first, so on overflow the email renders
         * only the latest $maxEvents activities while markAsSent (above, keyed on
         * the newest id) marks EVERY unread activity as notified — including ones
         * never fetched. Activities older than the rendered window are deliberately
         * never emailed; the "more activities" banner directs the recipient to the
         * site for full history. Do not "fix" by re-slicing or by marking only
         * rendered activities as sent.
         */
        if ($hasMoreActivities) {
            $activities = $activities->slice(1, $maxEvents);
        }

        return (new MailMessage)
            ->subject($this->getEmailSubject())
            ->markdown($this->getView(), [
                'notification' => $this,
                'notificationType' => $this->notificationType,
                'ticket' => $this->ticket,
                'actionUrl' => $urlService->getActionUrl($this->ticket),
                'activities' => $activities,
                'hasMoreActivities' => $hasMoreActivities,
                'maxEvents' => $maxEvents,
            ]);
    }

    public function getView(): string
    {
        return 'padmission-tickets::mails.ticket-history';
    }

    protected function getEmailSubject(): string
    {
        $key = "padmission-tickets::notifications.ticket-{$this->notificationType}.subject";

        return __($key, [
            'subject' => $this->ticket->subject,
            'ticket_id' => $this->ticket->id,
        ]);
    }
}
