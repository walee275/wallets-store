<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('email_thumb_path')->nullable()->after('path');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrier')->nullable()->after('discount_code');
            $table->string('tracking_number')->nullable()->after('carrier');
            $table->string('tracking_url')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('email_thumb_path');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrier', 'tracking_number', 'tracking_url']);
        });
    }
};
