<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('remember_token');
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'category')) {
                $table->string('category')->nullable()->after('description');
            }

            if (! Schema::hasColumn('campaigns', 'image_url')) {
                $table->string('image_url')->nullable()->after('category');
            }

            if (! Schema::hasColumn('campaigns', 'montant_collecte')) {
                $table->integer('montant_collecte')->default(0)->after('image_url');
            }

            if (! Schema::hasColumn('campaigns', 'montant_objectif')) {
                $table->integer('montant_objectif')->default(0)->after('montant_collecte');
            }
        });

        Schema::table('donations', function (Blueprint $table) {
            if (! Schema::hasColumn('donations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('donations', 'status')) {
                $table->string('status')->default('succeeded')->after('amount');
            }

            if (! Schema::hasColumn('donations', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('donations', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('stripe_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (Schema::hasColumn('donations', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            $columns = array_filter(['status', 'stripe_id', 'is_recurring'], fn ($column) => Schema::hasColumn('donations', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $columns = array_filter([
                'category',
                'image_url',
                'montant_collecte',
                'montant_objectif',
            ], fn ($column) => Schema::hasColumn('campaigns', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
