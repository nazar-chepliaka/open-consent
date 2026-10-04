<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider', 32);
            $table->json('configuration')->nullable();
            $table->text('credentials')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 16)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name'], 'ai_connections_user_name_unique');
            $table->index(['user_id', 'is_enabled'], 'ai_connections_user_enabled_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('default_ai_connection_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('ai_connections')
                ->nullOnDelete();
        });

        $this->addCheckConstraints();
    }

    private function addCheckConstraints(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("alter table ai_connections add constraint ai_connections_provider_check check (provider in ('openai'))");
        DB::statement("alter table ai_connections add constraint ai_connections_last_test_status_check check (last_test_status is null or last_test_status in ('success', 'failed'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_ai_connection_id');
        });

        Schema::dropIfExists('ai_connections');
    }
};
