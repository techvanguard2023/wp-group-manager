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
        Schema::table('whatsapp_groups', function (Blueprint $table) {
            // Se a migração anterior foi rodada, removemos a coluna 'category' temporária
            if (Schema::hasColumn('whatsapp_groups', 'category')) {
                $table->dropColumn('category');
            }
            
            $table->foreignId('category_id')->nullable()->after('name')->constrained('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_groups', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
