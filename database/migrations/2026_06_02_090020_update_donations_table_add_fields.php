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
        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->after('id');
            $table->unsignedBigInteger('donor_id')->after('campaign_id');
            $table->double('amount')->after('donor_id');
            $table->text('message')->nullable()->after('amount');
            $table->string('payment_status')->default('pending')->after('message');
            $table->string('stripe_payment_id')->nullable()->after('payment_status');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('donor_id')->references('id')->on('donors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['donor_id']);
            $table->dropColumn(['campaign_id', 'donor_id', 'amount', 'message', 'payment_status', 'stripe_payment_id']);
        });
    }
};
