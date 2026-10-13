<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('tagline', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('tint', 7)->default('#EDE5D8');
            $table->string('motif', 20)->default('leaf');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->string('option_label', 40)->nullable();
            $table->string('status', 12)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id', 'sort_order']);
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_bestseller']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80)->nullable();
            $table->string('sku', 64)->nullable()->unique();
            $table->unsignedInteger('price_minor')->nullable();
            $table->unsignedInteger('sale_price_minor')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->unsignedSmallInteger('low_stock_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->json('widths');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('alt', 160)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
