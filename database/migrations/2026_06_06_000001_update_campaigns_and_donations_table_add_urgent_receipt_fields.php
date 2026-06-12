<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('image');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('stripe_payment_id')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn('receipt_number');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('is_urgent');
        });
    }
};
