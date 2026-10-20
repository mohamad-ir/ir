<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('patient_age', 30)->nullable();
            $table->string('patient_sex', 20)->nullable();
            $table->string('eye', 10)->nullable();
            $table->string('presenting_complaint')->nullable();
            $table->longText('history')->nullable();
            $table->longText('findings')->nullable();
            $table->json('images')->nullable();
            $table->json('comparison')->nullable();
            $table->string('quiz_prompt')->nullable();
            $table->json('quiz_options')->nullable();
            $table->string('diagnosis')->nullable();
            $table->longText('diagnosis_explanation')->nullable();
            $table->json('differentials')->nullable();
            $table->longText('discussion')->nullable();
            $table->longText('management')->nullable();
            $table->json('teaching_points')->nullable();
            $table->json('references')->nullable();
            $table->string('difficulty', 20)->default('intermediate');
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_case_of_week')->default(false);
            $table->date('case_of_week_date')->nullable()->index();
            $table->boolean('is_featured')->default(false);
            $table->string('access_level', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('reveals_count')->default(0);
            $table->unsignedInteger('guesses_count')->default(0);
            $table->unsignedInteger('correct_guesses_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('clinical_case_modality', function (Blueprint $table) {
            $table->foreignId('clinical_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();
            $table->primary(['clinical_case_id', 'modality_id']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('reading_time')->nullable();
            $table->json('references')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('access_level', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('provider', 20)->default('youtube');
            $table->string('provider_video_id')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->foreignId('video_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('thumbnail_url', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('chapters')->nullable();
            $table->longText('transcript')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('level', 20)->default('intermediate');
            $table->boolean('is_featured')->default(false);
            $table->string('access_level', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedInteger('completions_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('exam_topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('exam', 40)->default('all');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('icon', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_topic_id')->nullable()->constrained('exam_topics')->nullOnDelete();
            $table->string('type', 20)->default('sba');
            $table->string('exam', 40)->default('all');
            $table->string('title');
            $table->longText('stem');
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('options')->nullable();
            $table->longText('explanation')->nullable();
            $table->longText('model_answer')->nullable();
            $table->json('key_points')->nullable();
            $table->json('references')->nullable();
            $table->string('difficulty', 20)->default('intermediate');
            $table->string('access_level', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('question_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 16)->nullable();
            $table->string('selected_key', 10)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'question_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });

        Schema::create('profile_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('location')->nullable();
            $table->string('start_year', 10)->nullable();
            $table->string('end_year', 10)->nullable();
            $table->string('authors', 1000)->nullable();
            $table->text('description')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('doi')->nullable();
            $table->string('pmid', 20)->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['profile_entries', 'bookmarks', 'question_attempts', 'questions', 'exam_topics', 'videos', 'articles', 'clinical_case_modality', 'clinical_cases'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
