<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class WebhookNotificationSettings extends Model
{
    use Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'webhook_enabled',
        'webhook_url',
        'webhook_api_key',
        'deployment_success_webhook_notifications',
        'deployment_failure_webhook_notifications',
    ];

    protected $casts = [
        'webhook_enabled' => 'boolean',
        'webhook_url' => 'encrypted',
        'webhook_api_key' => 'encrypted',
        'deployment_success_webhook_notifications' => 'boolean',
        'deployment_failure_webhook_notifications' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function isEnabled()
    {
        return $this->webhook_enabled;
    }
}
