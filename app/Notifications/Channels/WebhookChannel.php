<?php

namespace App\Notifications\Channels;

use App\Jobs\SendMessageToWebhookJob;
use Illuminate\Notifications\Notification;

class WebhookChannel
{
    /**
     * Send the given notification.
     */
    public function send(SendsWebhook $notifiable, Notification $notification): void
    {
        try {
            \Log::info('WebhookChannel: Processing notification', [
                'notification_class' => get_class($notification),
                'notifiable_class' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
            ]);

            $message = $notification->toWebhook();
            $webhookSettings = $notifiable->webhookNotificationSettings;

            \Log::info('WebhookChannel: Settings check', [
                'settings_exists' => $webhookSettings ? 'yes' : 'no',
                'is_enabled' => $webhookSettings ? ($webhookSettings->isEnabled() ? 'yes' : 'no') : 'n/a',
                'has_url' => $webhookSettings ? ($webhookSettings->webhook_url ? 'yes' : 'no') : 'n/a',
            ]);

            if (! $webhookSettings || ! $webhookSettings->isEnabled() || ! $webhookSettings->webhook_url) {
                \Log::warning('WebhookChannel: Skipping notification - webhook not configured properly');

                return;
            }

            \Log::info('WebhookChannel: Dispatching webhook job', [
                'url' => $webhookSettings->webhook_url,
                'has_api_key' => ! empty($webhookSettings->webhook_api_key) ? 'yes' : 'no',
            ]);

            SendMessageToWebhookJob::dispatch(
                $message,
                $webhookSettings->webhook_url,
                $webhookSettings->webhook_api_key
            );
        } catch (\Throwable $e) {
            \Log::error('WebhookChannel: Error sending notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
