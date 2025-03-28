<?php

namespace App\Notifications\Application;

use App\Models\Application;
use App\Models\ApplicationPreview;
use App\Notifications\CustomEmailNotification;
use App\Notifications\Dto\DiscordMessage;
use App\Notifications\Dto\PushoverMessage;
use App\Notifications\Dto\SlackMessage;
use App\Notifications\Dto\WebhookMessage;
use Illuminate\Notifications\Messages\MailMessage;

class DeploymentSuccess extends CustomEmailNotification
{
    public Application $application;

    public ?ApplicationPreview $preview = null;

    public string $deployment_uuid;

    public string $application_name;

    public string $project_uuid;

    public string $environment_uuid;

    public string $environment_name;

    public ?string $deployment_url = null;

    public ?string $fqdn;

    public function __construct(Application $application, string $deployment_uuid, ?ApplicationPreview $preview = null)
    {
        $this->onQueue('high');
        $this->application = $application;
        $this->deployment_uuid = $deployment_uuid;
        $this->preview = $preview;
        $this->application_name = data_get($application, 'name');
        $this->project_uuid = data_get($application, 'environment.project.uuid');
        $this->environment_uuid = data_get($application, 'environment.uuid');
        $this->environment_name = data_get($application, 'environment.name');
        $this->fqdn = data_get($application, 'fqdn');
        if (str($this->fqdn)->explode(',')->count() > 1) {
            $this->fqdn = str($this->fqdn)->explode(',')->first();
        }
        $this->deployment_url = base_url()."/project/{$this->project_uuid}/environment/{$this->environment_uuid}/application/{$this->application->uuid}/deployment/{$this->deployment_uuid}";
    }

    public function via(object $notifiable): array
    {
        $channels = $notifiable->getEnabledChannels('deployment_success');

        // Add debug logs to check each channel individually
        \Log::info('Checking webhook channel for deployment_success', [
            'webhook_settings_exists' => $notifiable->webhookNotificationSettings ? 'yes' : 'no',
        ]);

        if ($notifiable->webhookNotificationSettings) {
            \Log::info('Webhook settings details', [
                'webhook_enabled' => $notifiable->webhookNotificationSettings->webhook_enabled ? 'yes' : 'no',
                'deployment_success_webhook_notifications' => $notifiable->webhookNotificationSettings->deployment_success_webhook_notifications ? 'yes' : 'no',
                'webhook_url_set' => ! empty($notifiable->webhookNotificationSettings->webhook_url) ? 'yes' : 'no',
            ]);
        }

        \Log::info('Enabled notification channels for deployment success:', [
            'notifiable' => get_class($notifiable),
            'notifiable_id' => $notifiable->id ?? null,
            'channels' => $channels,
        ]);

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $mail = new MailMessage;
        $pull_request_id = data_get($this->preview, 'pull_request_id', 0);
        $fqdn = $this->fqdn;
        if ($pull_request_id === 0) {
            $mail->subject("Coolify: New version is deployed of {$this->application_name}");
        } else {
            $fqdn = $this->preview->fqdn;
            $mail->subject("Coolify: Pull request #{$pull_request_id} of {$this->application_name} deployed successfully");
        }
        $mail->view('emails.application-deployment-success', [
            'name' => $this->application_name,
            'fqdn' => $fqdn,
            'deployment_url' => $this->deployment_url,
            'pull_request_id' => $pull_request_id,
        ]);

        return $mail;
    }

    public function toDiscord(): DiscordMessage
    {
        if ($this->preview) {
            $message = new DiscordMessage(
                title: ':white_check_mark: Preview deployment successful',
                description: 'Pull request: '.$this->preview->pull_request_id,
                color: DiscordMessage::successColor(),
            );

            if ($this->preview->fqdn) {
                $message->addField('Application', '[Link]('.$this->preview->fqdn.')');
            }

            $message->addField('Project', data_get($this->application, 'environment.project.name'), true);
            $message->addField('Environment', $this->environment_name, true);
            $message->addField('Name', $this->application_name, true);
            $message->addField('Deployment logs', '[Link]('.$this->deployment_url.')');
        } else {
            if ($this->fqdn) {
                $description = '[Open application]('.$this->fqdn.')';
            } else {
                $description = '';
            }
            $message = new DiscordMessage(
                title: ':white_check_mark: New version successfully deployed',
                description: $description,
                color: DiscordMessage::successColor(),
            );
            $message->addField('Project', data_get($this->application, 'environment.project.name'), true);
            $message->addField('Environment', $this->environment_name, true);
            $message->addField('Name', $this->application_name, true);

            $message->addField('Deployment logs', '[Link]('.$this->deployment_url.')');
        }

        return $message;
    }

    public function toTelegram(): array
    {
        if ($this->preview) {
            $message = 'Coolify: New PR'.$this->preview->pull_request_id.' version successfully deployed of '.$this->application_name.'';
            if ($this->preview->fqdn) {
                $buttons[] = [
                    'text' => 'Open Application',
                    'url' => $this->preview->fqdn,
                ];
            }
        } else {
            $message = '✅ New version successfully deployed of '.$this->application_name.'';
            if ($this->fqdn) {
                $buttons[] = [
                    'text' => 'Open Application',
                    'url' => $this->fqdn,
                ];
            }
        }
        $buttons[] = [
            'text' => 'Deployment logs',
            'url' => $this->deployment_url,
        ];

        return [
            'message' => $message,
            'buttons' => [
                ...$buttons,
            ],
        ];
    }

    public function toPushover(): PushoverMessage
    {
        if ($this->preview) {
            $title = "Pull request #{$this->preview->pull_request_id} successfully deployed";
            $message = 'New PR'.$this->preview->pull_request_id.' version successfully deployed of '.$this->application_name.'';
            if ($this->preview->fqdn) {
                $buttons[] = [
                    'text' => 'Open Application',
                    'url' => $this->preview->fqdn,
                ];
            }
        } else {
            $title = 'New version successfully deployed';
            $message = 'New version successfully deployed of '.$this->application_name.'';
            if ($this->fqdn) {
                $buttons[] = [
                    'text' => 'Open Application',
                    'url' => $this->fqdn,
                ];
            }
        }
        $buttons[] = [
            'text' => 'Deployment logs',
            'url' => $this->deployment_url,
        ];

        return new PushoverMessage(
            title: $title,
            level: 'success',
            message: $message,
            buttons: [
                ...$buttons,
            ],
        );
    }

    public function toSlack(): SlackMessage
    {
        if ($this->preview) {
            $title = "Pull request #{$this->preview->pull_request_id} successfully deployed";
            $description = "New version successfully deployed for {$this->application_name}";
            if ($this->preview->fqdn) {
                $description .= "\nPreview URL: {$this->preview->fqdn}";
            }
        } else {
            $title = 'New version successfully deployed';
            $description = "New version successfully deployed for {$this->application_name}";
            if ($this->fqdn) {
                $description .= "\nApplication URL: {$this->fqdn}";
            }
        }

        $description .= "\n\n*Project:* ".data_get($this->application, 'environment.project.name');
        $description .= "\n*Environment:* {$this->environment_name}";
        $description .= "\n*<{$this->deployment_url}|Deployment Logs>*";

        return new SlackMessage(
            title: $title,
            description: $description,
            color: SlackMessage::successColor()
        );
    }

    public function toWebhook(): WebhookMessage
    {
        $metadata = [
            'project_uuid' => $this->project_uuid,
            'environment_uuid' => $this->environment_uuid,
            'application_uuid' => $this->application->uuid,
            'deployment_uuid' => $this->deployment_uuid,

            'project' => data_get($this->application, 'environment.project.name'),
            'environment' => $this->environment_name,
            'application' => $this->application_name,

            'git_repository' => $this->application->git_repository,
            'git_branch' => $this->application->git_branch,
            'git_full_url' => $this->application->git_full_url,
        ];

        // Try to get the actual deployment commit details
        $deploymentQueue = \App\Models\ApplicationDeploymentQueue::where('deployment_uuid', $this->deployment_uuid)->first();
        \Log::info('WebhookNotification: Looking up deployment details', [
            'deployment_uuid' => $this->deployment_uuid,
            'found_deployment' => $deploymentQueue ? 'yes' : 'no',
            'commit' => $deploymentQueue->commit ?? 'not found',
            'commit_message' => $deploymentQueue->commit_message ?? 'not found',
        ]);

        $metadata['commit_sha'] = $deploymentQueue->commit ?? null;
        $metadata['commit_id'] = $deploymentQueue->commit ? substr($deploymentQueue->commit, 0, 7) : null;
        $metadata['commit_message'] = $deploymentQueue->commit_message ?? null;

        if ($this->preview) {
            $title = "Pull request #{$this->preview->pull_request_id} successfully deployed";
            $description = "Pull request deployment successful for {$this->application_name}";

            if ($this->preview->fqdn) {
                $metadata['preview_url'] = $this->preview->fqdn;
            }

            $metadata['pull_request_id'] = $this->preview->pull_request_id;
        } else {
            $title = 'Deployment successful';
            $description = "New version successfully deployed for {$this->application_name}";

            if ($this->fqdn) {
                $metadata['application_url'] = $this->fqdn;
            }
        }

        $message = new WebhookMessage(
            title: $title,
            description: $description,
            status: WebhookMessage::success(),
            metadata: $metadata
        );

        $message->addField('deployment_logs_url', $this->deployment_url);
        $message->addField('timestamp', now()->toIso8601String());

        return $message;
    }
}
