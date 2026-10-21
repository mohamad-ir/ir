<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paid membership, courses and live webinars, sold through Stripe Checkout.
 * Amounts are stored in minor units (cents) with an ISO currency code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->index()->after('last_login_at');
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('price');
            $table->char('currency', 3);
            $table->string('interval', 10); // month | year
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_price_fingerprint')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->string('status', 30)->index();
            $table->unsignedInteger('amount')->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('interval', 10)->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('type', 20); // course | webinar | membership
            $table->nullableMorphs('purchasable');
            $table->string('description');
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('tax')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->char('currency', 3);
            $table->string('status', 20)->default('pending')->index();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('receipt_url', 500)->nullable();
            $table->string('invoice_pdf_url', 500)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('type', 80)->index();
            $table->boolean('livemode')->default(false);
            $table->longText('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->json('outcomes')->nullable();
            $table->json('faqs')->nullable();
            $table->string('level', 20)->default('intermediate');
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('trailer_url', 500)->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->boolean('included_in_membership')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stripe_product_id')->nullable();
            $table->unsignedInteger('enrollments_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('video_provider', 20)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('provider_video_id')->nullable();
            $table->foreignId('video_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // purchase | membership | free | admin
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('last_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('starts_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('platform', 20)->default('zoom');
            $table->text('join_url')->nullable();
            $table->text('join_details')->nullable();
            $table->string('recording_url', 500)->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->boolean('free_for_members')->default(true);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('registration_open')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stripe_product_id')->nullable();
            $table->unsignedInteger('registrations_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('webinar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // free | purchase | membership | admin
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reminder_day_sent_at')->nullable();
            $table->timestamp('reminder_hour_sent_at')->nullable();
            $table->timestamp('recording_sent_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->unique(['webinar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinar_registrations');
        Schema::dropIfExists('webinars');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('membership_plans');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('stripe_customer_id'));
    }
};
