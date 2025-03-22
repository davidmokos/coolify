<div>
    <x-slot:title>
        Notifications | Coolify
    </x-slot>
    <x-notification.navbar />
    <form wire:submit='submit' class="flex flex-col gap-4 pb-4">
        <div class="flex items-center gap-2">
            <h2>Custom Webhook</h2>
            <x-forms.button type="submit">
                Save
            </x-forms.button>
            @if ($webhookEnabled)
                <x-forms.button class="normal-case dark:text-white btn btn-xs no-animation btn-primary"
                    wire:click="sendTestNotification">
                    Send Test Notification
                </x-forms.button>
            @else
                <x-forms.button disabled class="normal-case dark:text-white btn btn-xs no-animation btn-primary">
                    Send Test Notification
                </x-forms.button>
            @endif
        </div>
        <div class="w-32">
            <x-forms.checkbox instantSave="instantSaveWebhookEnabled" id="webhookEnabled" label="Enabled" />
        </div>
        <x-forms.input type="url"
            helper="Enter the URL that will receive the webhook notifications." required
            id="webhookUrl" label="Webhook URL" />
        <x-forms.input type="password"
            helper="Optional API key that will be sent in the X-API-Key header."
            id="webhookApiKey" label="API Key" />
    </form>
    <h2 class="mt-4">Notification Settings</h2>
    <p class="mb-4">
        Select events for which you would like to receive webhook notifications.
    </p>
    <div class="flex flex-col gap-4 max-w-2xl">
        <div class="border dark:border-coolgray-300 p-4 rounded-lg">
            <h3 class="font-medium mb-3">Deployments</h3>
            <div class="flex flex-col gap-1.5 pl-1">
                <x-forms.checkbox instantSave="saveModel" id="deploymentSuccessWebhookNotifications"
                    label="Deployment Success" />
                <x-forms.checkbox instantSave="saveModel" id="deploymentFailureWebhookNotifications"
                    label="Deployment Failure" />
            </div>
        </div>
    </div>
</div> 