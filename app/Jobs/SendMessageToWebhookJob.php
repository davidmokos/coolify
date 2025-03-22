<?php

namespace App\Jobs;

use App\Notifications\Dto\WebhookMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendMessageToWebhookJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    public $backoff = 10;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 5;

    public function __construct(
        public WebhookMessage $message,
        public string $webhookUrl,
        public ?string $apiKey = null
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($this->apiKey) {
                $headers['X-API-Key'] = $this->apiKey;
            }

            \Log::info('Sending webhook request', [
                'url' => $this->webhookUrl,
                'has_api_key' => ! empty($this->apiKey) ? 'yes' : 'no',
            ]);

            $payload = $this->message->toPayload();
            \Log::debug('Webhook payload', ['payload' => $payload]);

            $response = Http::withHeaders($headers)
                ->post($this->webhookUrl, $payload);

            if (! $response->successful()) {
                \Log::error('Webhook request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception("Webhook request failed with status {$response->status()}: {$response->body()}");
            }

            \Log::info('Webhook sent successfully', [
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error sending webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
