<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);
            $table->nullableMorphs('viewable');
            $table->char('visitor_hash', 16);
            $table->boolean('is_unique')->default(false);
            $table->string('referrer_host')->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('device', 10)->nullable();
            $table->string('browser', 30)->nullable();
            $table->string('os', 30)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->index();
            $table->index(['visitor_hash', 'created_at']);
            $table->index(['path', 'created_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->nullableMorphs('subject');
            $table->char('visitor_hash', 16)->nullable();
            $table->string('path', 500)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at');
            $table->index(['name', 'created_at']);
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query', 200);
            $table->unsignedInteger('results_count')->default(0);
            $table->char('visitor_hash', 16)->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('search_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('searchable');
            $table->string('type', 30)->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->text('keywords')->nullable();
            $table->string('url', 500);
            $table->string('image_url', 500)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['searchable_type', 'searchable_id']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE search_documents ADD FULLTEXT search_documents_fulltext (title, keywords, content)');
        }

        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 100)->unique();
            $table->text('synonyms');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['search_synonyms', 'search_documents', 'search_queries', 'analytics_events', 'page_views'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
