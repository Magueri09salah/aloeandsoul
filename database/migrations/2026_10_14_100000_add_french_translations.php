<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * French versions of customer-facing text. English stays in the original
 * columns; an empty French column falls back to the English text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_fr', 100)->nullable()->after('name');
            $table->string('tagline_fr', 160)->nullable()->after('tagline');
            $table->text('description_fr')->nullable()->after('description');
            $table->string('seo_title_fr', 70)->nullable()->after('seo_title');
            $table->string('seo_description_fr', 170)->nullable()->after('seo_description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_fr', 160)->nullable()->after('name');
            $table->string('summary_fr', 255)->nullable()->after('summary');
            $table->text('description_fr')->nullable()->after('description');
            $table->text('benefits_fr')->nullable()->after('benefits');
            $table->text('usage_fr')->nullable()->after('usage');
            $table->text('ingredients_fr')->nullable()->after('ingredients');
            $table->text('nutrition_fr')->nullable()->after('nutrition');
            $table->string('option_label_fr', 40)->nullable()->after('option_label');
            $table->string('seo_title_fr', 70)->nullable()->after('seo_title');
            $table->string('seo_description_fr', 170)->nullable()->after('seo_description');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('name_fr', 80)->nullable()->after('name');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('alt_fr', 160)->nullable()->after('alt');
        });

        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->string('name_fr', 80)->nullable()->after('name');
            $table->string('estimate_fr', 80)->nullable()->after('estimate');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_fr', 120)->nullable()->after('title');
            $table->longText('body_fr')->nullable()->after('body');
            $table->string('seo_title_fr', 70)->nullable()->after('seo_title');
            $table->string('seo_description_fr', 170)->nullable()->after('seo_description');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question_fr', 255)->nullable()->after('question');
            $table->text('answer_fr')->nullable()->after('answer');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_fr', 160)->nullable()->after('title');
            $table->string('excerpt_fr', 300)->nullable()->after('excerpt');
            $table->longText('body_fr')->nullable()->after('body');
            $table->string('cover_alt_fr', 160)->nullable()->after('cover_alt');
            $table->string('seo_title_fr', 70)->nullable()->after('seo_title');
            $table->string('seo_description_fr', 170)->nullable()->after('seo_description');
        });

        Schema::table('collaborators', function (Blueprint $table) {
            $table->string('description_fr', 255)->nullable()->after('description');
        });

        // The language the customer ordered in, so the team replies in it.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('locale', 5)->default('en')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('locale'));
        Schema::table('collaborators', fn (Blueprint $table) => $table->dropColumn('description_fr'));
        Schema::table('blog_posts', fn (Blueprint $table) => $table->dropColumn(['title_fr', 'excerpt_fr', 'body_fr', 'cover_alt_fr', 'seo_title_fr', 'seo_description_fr']));
        Schema::table('faqs', fn (Blueprint $table) => $table->dropColumn(['question_fr', 'answer_fr']));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn(['title_fr', 'body_fr', 'seo_title_fr', 'seo_description_fr']));
        Schema::table('shipping_zones', fn (Blueprint $table) => $table->dropColumn(['name_fr', 'estimate_fr']));
        Schema::table('product_images', fn (Blueprint $table) => $table->dropColumn('alt_fr'));
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('name_fr'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn([
            'name_fr', 'summary_fr', 'description_fr', 'benefits_fr', 'usage_fr', 'ingredients_fr',
            'nutrition_fr', 'option_label_fr', 'seo_title_fr', 'seo_description_fr',
        ]));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn(['name_fr', 'tagline_fr', 'description_fr', 'seo_title_fr', 'seo_description_fr']));
    }
};
