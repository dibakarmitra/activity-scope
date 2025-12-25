<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('activityscope.database.table', 'activities'), function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->nullableMorphs('subject');
            $table->string('log_name')->default('default')->index();
            $table->string('action');
            $table->string('status', 30)->default('success')->index();
            $table->string('category', 50)->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->string('method')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['log_name', 'created_at']);
            $table->index(['actor_type', 'actor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('activityscope.table_name', 'activities'));
    }
};