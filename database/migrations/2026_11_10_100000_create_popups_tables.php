<?php

use App\Services\Popups\PopupService;
use App\Support\Permissions;
use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * v1.5: Popups & announcements (modal popups, corner slide-ins and announcement bars)
 * with display rules, frequency limits and daily statistics.
 * The old single “announcement bar” setting is moved into the new module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 12)->default('modal'); // modal | slide_in | bar
            $table->string('status', 12)->default('draft')->index(); // draft | published | paused
            $table->smallInteger('priority')->default(0);
            $table->string('template', 40)->nullable();

            // Content
            $table->string('eyebrow', 80)->nullable();
            $table->string('title', 160)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('image_style', 12)->default('top'); // top | side | background
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->boolean('cta_new_tab')->default(false);
            $table->string('dismiss_label', 60)->nullable();
            $table->string('form', 20)->default('none'); // none | newsletter

            // Design
            $table->string('position', 16)->default('center'); // center | bottom_right | bottom_left | top | bottom
            $table->string('size', 4)->default('md'); // sm | md | lg
            $table->string('color_scheme', 10)->default('default'); // default | brand | accent | highlight | custom
            $table->string('bg_color', 7)->nullable();
            $table->string('text_color', 7)->nullable();
            $table->string('button_color', 7)->nullable();
            $table->string('animation', 8)->default('fade'); // fade | slide | zoom | none
            $table->boolean('show_overlay')->default(true);
            $table->boolean('close_on_overlay')->default(true);
            $table->boolean('show_close')->default(true);
            $table->unsignedSmallInteger('auto_close')->nullable(); // seconds

            // When to show
            $table->string('trigger', 12)->default('delay'); // load | delay | scroll | exit | idle | pageviews | click
            $table->unsignedSmallInteger('trigger_value')->nullable();

            // Where and to whom
            $table->json('include_paths')->nullable();
            $table->json('exclude_paths')->nullable();
            $table->string('audience', 12)->default('everyone'); // everyone | guests | signed_in | members | non_members
            $table->json('devices')->nullable(); // [desktop, tablet, mobile]; null = all
            $table->string('visitors', 10)->default('all'); // all | new | returning
            $table->string('source_match', 120)->nullable(); // referrer host / utm_source / utm_campaign contains

            // Schedule
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('days')->nullable(); // ISO weekdays 1..7; null = every day
            $table->string('time_from', 5)->nullable(); // HH:MM site time
            $table->string('time_to', 5)->nullable();

            // Frequency per visitor and campaign limit
            $table->string('frequency', 12)->default('days'); // page | session | days | once
            $table->unsignedSmallInteger('frequency_days')->nullable();
            $table->unsignedSmallInteger('after_close_days')->nullable();
            $table->boolean('hide_after_conversion')->default(true);
            $table->unsignedSmallInteger('max_views')->nullable();
            $table->unsignedInteger('max_impressions')->nullable();
            $table->unsignedSmallInteger('revision')->default(1);

            // Totals (daily figures live in popup_stats)
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('closes_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->timestamp('stats_reset_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });

        Schema::create('popup_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('device', 8); // desktop | tablet | mobile
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('closes')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->unique(['popup_id', 'date', 'device']);
        });

        $this->moveOldAnnouncement();

        if (Schema::hasTable('roles') && Role::query()->exists()) {
            Permissions::sync();
            Role::where('name', 'editor')->first()?->givePermissionTo('popups.manage');
        }

        // Forget cached settings (the old announcement keys) and popups.
        Settings::flush();
        PopupService::flush();
    }

    /** The former Header & footer › Announcement bar becomes a bar in the new module (same text, link and on/off state). */
    protected function moveOldAnnouncement(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $get = fn (string $key) => ($row = DB::table('settings')->where('key', $key)->first()) ? json_decode((string) $row->value, true) : null;
        $message = trim((string) $get('header.announcement'));

        if ($message !== '') {
            DB::table('popups')->insert([
                'name' => 'Announcement bar',
                'type' => 'bar',
                'status' => $get('header.announcement_enabled') ? 'published' : 'draft',
                'title' => mb_substr($message, 0, 160),
                'cta_url' => $get('header.announcement_url') ?: null,
                'cta_label' => $get('header.announcement_url') ? 'Learn more' : null,
                'position' => 'top',
                'color_scheme' => 'brand',
                'animation' => 'none',
                'show_overlay' => false,
                'trigger' => 'load',
                'frequency' => 'page',
                'after_close_days' => 1,
                'hide_after_conversion' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->whereIn('key', ['header.announcement', 'header.announcement_url', 'header.announcement_enabled'])->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_stats');
        Schema::dropIfExists('popups');
    }
};
