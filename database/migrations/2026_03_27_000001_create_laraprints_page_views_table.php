<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('laraprints.database.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('laraprints_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('session_id')->index();
            $table->string('visit_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'unknown']);
            $table->string('country_code', 2)->nullable();
            $table->string('method')->nullable();
            $table->string('current_path')->index();
            $table->json('current_params')->nullable();
            $table->string('referrer_path')->nullable();
            $table->json('referrer_params')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('laraprints_page_views');
    }
};
