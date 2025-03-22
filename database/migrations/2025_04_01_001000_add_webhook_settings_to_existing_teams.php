<?php

use App\Models\Team;
use App\Models\WebhookNotificationSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find all teams
        $teams = Team::all();

        // For each team, check if webhook notification settings exist
        foreach ($teams as $team) {
            try {
                // Check if webhook settings already exist
                $settings = $team->webhookNotificationSettings;

                // If settings don't exist, create them
                if (! $settings) {
                    Log::info("Creating webhook notification settings for team {$team->id}");
                    WebhookNotificationSettings::create([
                        'team_id' => $team->id,
                        'webhook_enabled' => false,
                        'deployment_success_webhook_notifications' => true,
                        'deployment_failure_webhook_notifications' => true,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to create webhook settings for team {$team->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as removing settings for existing teams could be destructive
    }
};
