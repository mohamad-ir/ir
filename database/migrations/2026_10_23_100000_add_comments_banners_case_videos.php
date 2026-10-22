<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** v1.3: comments, banner sliders and videos on OCT cases. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index(); // pending | approved | spam | rejected
            $table->string('moderation_note')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['commentable_type', 'commentable_id', 'status']);
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['comment_id', 'user_id']);
        });

        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);
            $table->timestamp('created_at')->nullable();
            $table->unique(['comment_id', 'user_id']);
        });

        foreach (['clinical_cases', 'articles', 'videos'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('comments_enabled')->default(true);
                $t->unsignedInteger('comments_count')->default(0);
            });
        }

        Schema::table('clinical_cases', fn (Blueprint $t) => $t->json('videos')->nullable()->after('comparison'));

        Schema::table('users', fn (Blueprint $t) => $t->boolean('notify_replies')->default(true)->after('is_active'));

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('placement', 40)->nullable()->index();
            $table->string('height', 20)->default('standard'); // compact | standard | tall
            $table->string('transition', 10)->default('slide'); // slide | fade
            $table->boolean('autoplay')->default(true);
            $table->unsignedSmallInteger('interval')->default(6);
            $table->boolean('show_arrows')->default(true);
            $table->boolean('show_dots')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('banner_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('mobile_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->text('text')->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->boolean('new_tab')->default(false);
            $table->string('text_position', 10)->default('left'); // left | center | right
            $table->string('overlay', 10)->default('dark'); // none | light | dark
            $table->string('alt')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_slides');
        Schema::dropIfExists('banners');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('notify_replies'));
        Schema::table('clinical_cases', fn (Blueprint $t) => $t->dropColumn('videos'));
        foreach (['clinical_cases', 'articles', 'videos'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['comments_enabled', 'comments_count']));
        }
        Schema::dropIfExists('comment_reports');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comments');
    }
};
