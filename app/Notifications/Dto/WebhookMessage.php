<?php

namespace App\Notifications\Dto;

class WebhookMessage
{
    private array $data = [];

    public function __construct(
        public string $title,
        public string $description,
        public string $status,
        public array $metadata = [],
        public bool $isCritical = false,
    ) {}

    public static function success(): string
    {
        return 'success';
    }

    public static function failure(): string
    {
        return 'failure';
    }

    public function addField(string $name, string $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function toPayload(): array
    {
        $payload = [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $this->metadata,
            'data' => $this->data,
            'critical' => $this->isCritical,
        ];

        if (isCloud()) {
            $payload['source'] = 'Coolify Cloud';
        } else {
            $payload['source'] = 'Coolify v'.config('constants.coolify.version');
        }

        return $payload;
    }
}
