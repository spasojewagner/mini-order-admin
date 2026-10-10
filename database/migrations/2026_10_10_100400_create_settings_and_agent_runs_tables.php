<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company details for invoices and thresholds for the order monitor,
        // editable by an administrator instead of hard-coded.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->string('agent', 80);
            $table->string('trigger', 20)->default('schedule');   // schedule, manual
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('running');    // running, completed, failed
            $table->json('actions')->nullable();                  // what the tools did
            $table->text('response')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('sender')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('settings');
    }
};
