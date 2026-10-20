<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('abbreviation', 40)->nullable();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->string('color', 20)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('topicables', function (Blueprint $table) {
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->morphs('topicable');
            $table->primary(['topic_id', 'topicable_id', 'topicable_type']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });

        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('abbreviation', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
        });

        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        foreach (['seo_meta', 'taggables', 'tags', 'modalities', 'categories', 'topicables', 'topics'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
