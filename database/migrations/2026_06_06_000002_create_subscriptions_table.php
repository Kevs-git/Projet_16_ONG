<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donor_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('stripe_subscription_id')->unique();
            $table->double('amount')->nullable();
            $table->string('interval')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('next_payment_at')->nullable();
            $table->timestamps();

            $table->foreign('donor_id')->references('id')->on('donors')->onDelete('set null');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
