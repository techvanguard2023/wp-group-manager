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
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name');
            $table->text('wa_group_id');
            $table->string('wa_group_id_hash')->unique();
            $table->text('invite_link')->nullable();
            $table->unsignedInteger('current_members')->default(0);
            $table->unsignedInteger('max_members')->default(1024);
            $table->boolean('is_active')->default(true);
            $table->index(['is_active', 'current_members']);
            $table->index('wa_group_id_hash');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_groups');
    }
};
