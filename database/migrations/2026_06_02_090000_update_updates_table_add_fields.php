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
        Schema::table('updates', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->after('id');
            $table->string('title')->nullable()->after('campaign_id');
            $table->text('content')->nullable()->after('title');
            $table->string('image')->nullable()->after('content');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('updates', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'title', 'content', 'image']);
        });
    }
};
