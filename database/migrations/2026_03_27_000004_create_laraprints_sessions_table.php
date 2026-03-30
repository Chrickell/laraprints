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
        Schema::connection($this->getConnection())->create('laraprints_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 36)->unique()->index();
            $table->char('country', 2)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('device', 16)->default('desktop');
            $table->string('entry_page', 255)->nullable();
            $table->string('referrer', 255)->nullable();
            $table->unsignedInteger('page_views')->default(0)->index();
            $table->unsignedInteger('clicks')->default(0)->index();
            $table->unsignedInteger('duration')->nullable()->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('laraprints_sessions');
    }
};
