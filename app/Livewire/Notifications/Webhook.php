<?php

namespace App\Livewire\Notifications;

use App\Models\Team;
use App\Models\WebhookNotificationSettings;
use App\Notifications\Test;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Webhook extends Component
{
    public Team $team;

    public WebhookNotificationSettings $settings;

    #[Validate(['boolean'])]
    public bool $webhookEnabled = false;

    #[Validate(['url', 'nullable'])]
    public ?string $webhookUrl = null;

    #[Validate(['string', 'nullable'])]
    public ?string $webhookApiKey = null;

    #[Validate(['boolean'])]
    public bool $deploymentSuccessWebhookNotifications = false;

    #[Validate(['boolean'])]
    public bool $deploymentFailureWebhookNotifications = true;

    public function mount()
    {
        try {
            $this->team = auth()->user()->currentTeam();

            // Check if webhook notification settings exist, create if they don't
            if (! $this->team->webhookNotificationSettings) {
                $this->team->webhookNotificationSettings()->create([
                    'webhook_enabled' => false,
                    'deployment_success_webhook_notifications' => true,
                    'deployment_failure_webhook_notifications' => true,
                ]);
                // Refresh the relationship
                $this->team->refresh();
            }

            $this->settings = $this->team->webhookNotificationSettings;
            $this->syncData();
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function syncData(bool $toModel = false)
    {
        if ($toModel) {
            $this->validate();
            $this->settings->webhook_enabled = $this->webhookEnabled;
            $this->settings->webhook_url = $this->webhookUrl;
            $this->settings->webhook_api_key = $this->webhookApiKey;

            $this->settings->deployment_success_webhook_notifications = $this->deploymentSuccessWebhookNotifications;
            $this->settings->deployment_failure_webhook_notifications = $this->deploymentFailureWebhookNotifications;

            $this->settings->save();
            refreshSession();
        } else {
            $this->webhookEnabled = $this->settings->webhook_enabled;
            $this->webhookUrl = $this->settings->webhook_url;
            $this->webhookApiKey = $this->settings->webhook_api_key;

            $this->deploymentSuccessWebhookNotifications = $this->settings->deployment_success_webhook_notifications;
            $this->deploymentFailureWebhookNotifications = $this->settings->deployment_failure_webhook_notifications;
        }
    }

    public function instantSaveWebhookEnabled()
    {
        try {
            $this->validate([
                'webhookUrl' => 'required',
            ], [
                'webhookUrl.required' => 'Webhook URL is required.',
            ]);
            $this->saveModel();
        } catch (\Throwable $e) {
            $this->webhookEnabled = false;

            return handleError($e, $this);
        }
    }

    public function instantSave()
    {
        try {
            $this->syncData(true);
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function submit()
    {
        try {
            $this->resetErrorBag();
            $this->syncData(true);
            $this->saveModel();
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function saveModel()
    {
        $this->syncData(true);
        refreshSession();
        $this->dispatch('success', 'Settings saved.');
    }

    public function sendTestNotification()
    {
        try {
            $this->team->notify(new Test(channel: 'webhook'));
            $this->dispatch('success', 'Test notification sent.');
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }

    public function render()
    {
        return view('livewire.notifications.webhook');
    }
}
