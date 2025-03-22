<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();

            $table->boolean('webhook_enabled')->default(false);
            $table->text('webhook_url')->nullable();
            $table->text('webhook_api_key')->nullable();

            // We only need deployment success and failure for custom webhook
            $table->boolean('deployment_success_webhook_notifications')->default(true);
            $table->boolean('deployment_failure_webhook_notifications')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_notification_settings');
    }
};
