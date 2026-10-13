<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product page sections next to the description, each in Markdown:
 * benefits, how to use, ingredients and nutrition information.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('benefits')->nullable()->after('description');
            $table->text('usage')->nullable()->after('benefits');
            $table->text('ingredients')->nullable()->after('usage');
            $table->text('nutrition')->nullable()->after('ingredients');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['benefits', 'usage', 'ingredients', 'nutrition']);
        });
    }
};
