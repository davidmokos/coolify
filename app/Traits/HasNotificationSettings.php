<?php

namespace App\Traits;

use App\Notifications\Channels\DiscordChannel;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\PushoverChannel;
use App\Notifications\Channels\SlackChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Database\Eloquent\Model;

trait HasNotificationSettings
{
    protected $alwaysSendEvents = [
        'server_force_enabled',
        'server_force_disabled',
        'general',
        'test',
        'ssl_certificate_renewal',
    ];

    /**
     * Get settings model for specific channel
     */
    public function getNotificationSettings(string $channel): ?Model
    {
        return match ($channel) {
            'email' => $this->emailNotificationSettings,
            'discord' => $this->discordNotificationSettings,
            'telegram' => $this->telegramNotificationSettings,
            'slack' => $this->slackNotificationSettings,
            'pushover' => $this->pushoverNotificationSettings,
            'webhook' => $this->webhookNotificationSettings,
            default => null,
        };
    }

    /**
     * Check if a notification channel is enabled
     */
    public function isNotificationEnabled(string $channel): bool
    {
        $settings = $this->getNotificationSettings($channel);

        return $settings?->isEnabled() ?? false;
    }

    /**
     * Check if a specific notification type is enabled for a channel
     */
    public function isNotificationTypeEnabled(string $channel, string $event): bool
    {
        $settings = $this->getNotificationSettings($channel);

        \Log::debug("Checking if notification type '$event' is enabled for channel '$channel'", [
            'team_id' => $this->id ?? null,
            'settings_exists' => $settings ? 'yes' : 'no',
            'notification_enabled' => $settings && $this->isNotificationEnabled($channel) ? 'yes' : 'no',
            'in_always_send' => in_array($event, $this->alwaysSendEvents) ? 'yes' : 'no',
        ]);

        if (! $settings || ! $this->isNotificationEnabled($channel)) {
            return false;
        }

        if (in_array($event, $this->alwaysSendEvents)) {
            return true;
        }

        $settingKey = "{$event}_{$channel}_notifications";
        $isEnabled = isset($settings->$settingKey) ? (bool) $settings->$settingKey : false;

        \Log::debug('Notification type check result', [
            'setting_key' => $settingKey,
            'exists' => isset($settings->$settingKey) ? 'yes' : 'no',
            'is_enabled' => $isEnabled ? 'yes' : 'no',
        ]);

        return $isEnabled;
    }

    /**
     * Get all enabled notification channels for an event
     */
    public function getEnabledChannels(string $event): array
    {
        $channels = [];

        $channelMap = [
            'email' => EmailChannel::class,
            'discord' => DiscordChannel::class,
            'telegram' => TelegramChannel::class,
            'slack' => SlackChannel::class,
            'pushover' => PushoverChannel::class,
            'webhook' => WebhookChannel::class,
        ];

        \Log::debug("Getting enabled channels for event '$event'", [
            'team_id' => $this->id ?? null,
            'always_send' => in_array($event, $this->alwaysSendEvents) ? 'yes' : 'no',
        ]);

        if ($event === 'general') {
            unset($channelMap['email']);
        }

        foreach ($channelMap as $channel => $channelClass) {
            $isEnabled = $this->isNotificationEnabled($channel) && $this->isNotificationTypeEnabled($channel, $event);

            \Log::debug("Channel '$channel' status for event '$event'", [
                'is_enabled' => $isEnabled ? 'yes' : 'no',
                'notification_enabled' => $this->isNotificationEnabled($channel) ? 'yes' : 'no',
                'notification_type_enabled' => $this->isNotificationTypeEnabled($channel, $event) ? 'yes' : 'no',
            ]);

            if ($isEnabled) {
                $channels[] = $channelClass;
            }
        }

        return $channels;
    }
}
