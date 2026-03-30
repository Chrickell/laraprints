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
        Schema::connection($this->getConnection())->create('laraprints_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->nullable()->index();
            $table->string('session_id')->index();
            $table->string('visit_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('element');
            $table->string('element_class')->nullable();
            $table->string('element_id')->nullable();
            $table->text('element_style')->nullable();
            $table->string('path');
            $table->timestamp('clicked_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('laraprints_clicks');
    }
};
