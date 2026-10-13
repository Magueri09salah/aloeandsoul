<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 300)->nullable();
            $table->longText('body');
            $table->json('cover')->nullable();
            $table->string('cover_alt', 160)->nullable();
            $table->string('status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->json('image')->nullable();
            $table->string('image_style', 10)->default('photo');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborators');
        Schema::dropIfExists('blog_posts');
    }
};
