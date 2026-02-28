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
        Schema::create('group_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('whatsapp_group_id')->constrained('whatsapp_groups')->onDelete('cascade');
            $table->foreignUuid('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['whatsapp_group_id', 'contact_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_contacts');
    }
};
