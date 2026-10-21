<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** v1.2: photo galleries, lesson quizzes, completion certificates and prices in several currencies. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('layout', 20)->default('justified');
            $table->boolean('dark_viewer')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('access_level', 20)->default('public');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->text('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['gallery_id', 'sort_order']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->json('quiz')->nullable()->after('attachments');
        });

        Schema::create('lesson_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->boolean('passed');
            $table->json('answers');
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'lesson_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('certificate_enabled')->default(true)->after('included_in_membership');
            $table->decimal('cpd_hours', 5, 1)->nullable()->after('certificate_enabled');
            $table->json('prices')->nullable()->after('currency');
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('course_title');
            $table->decimal('hours', 5, 1)->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::table('webinars', function (Blueprint $table) {
            $table->json('prices')->nullable()->after('currency');
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->json('prices')->nullable()->after('currency');
            $table->json('stripe_prices')->nullable()->after('stripe_price_id');
        });

        // Carry the single Stripe price of 1.1 over to the per-currency map.
        foreach (DB::table('membership_plans')->whereNotNull('stripe_price_id')->get() as $plan) {
            DB::table('membership_plans')->where('id', $plan->id)->update(['stripe_prices' => json_encode([
                strtoupper($plan->currency) => ['id' => $plan->stripe_price_id, 'fingerprint' => $plan->stripe_price_fingerprint],
            ])]);
        }
    }

    public function down(): void
    {
        Schema::table('membership_plans', fn (Blueprint $table) => $table->dropColumn(['prices', 'stripe_prices']));
        Schema::table('webinars', fn (Blueprint $table) => $table->dropColumn('prices'));
        Schema::dropIfExists('certificates');
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn(['certificate_enabled', 'cpd_hours', 'prices']));
        Schema::dropIfExists('lesson_quiz_attempts');
        Schema::table('lessons', fn (Blueprint $table) => $table->dropColumn('quiz'));
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('galleries');
    }
};
