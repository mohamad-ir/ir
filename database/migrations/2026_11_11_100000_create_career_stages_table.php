<?php

use App\Models\CareerStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Career stages become a list managed in the dashboard (Audience › Career stages) instead of a fixed
 * list in code. Members keep their stored value: users.career_stage still holds the stage key, and the
 * six original keys are kept. New stages for an international audience are added.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Members are counted and filtered by stage.
        if (! collect(Schema::getIndexes('users'))->contains(fn ($i) => $i['columns'] === ['career_stage'])) {
            Schema::table('users', fn (Blueprint $table) => $table->index('career_stage'));
        }

        Schema::create('career_stages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 80);
            $table->string('color', 12)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = collect(CareerStage::DEFAULTS)->values()->map(fn ($stage, $i) => [
            'key' => $stage[0], 'name' => $stage[1], 'color' => $stage[2], 'sort_order' => ($i + 1) * 10,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('career_stages')->insert($rows->all());

        // Any other value already stored on a member (should not exist) is kept as an inactive stage, so nothing is lost.
        $known = $rows->pluck('key')->all();
        DB::table('users')->whereNotNull('career_stage')->whereNotIn('career_stage', $known)->distinct()->pluck('career_stage')
            ->each(fn ($key, $i) => DB::table('career_stages')->insert([
                'key' => mb_substr($key, 0, 40), 'name' => ucfirst(str_replace(['_', '-'], ' ', $key)), 'color' => 'gray',
                'sort_order' => 900 + $i, 'is_active' => false, 'created_at' => $now, 'updated_at' => $now,
            ]));

        CareerStage::flush();
    }

    public function down(): void
    {
        // Earlier versions only know the six original stages: members on any other stage become “other”,
        // so their pages keep working after a rollback.
        DB::table('users')->whereNotNull('career_stage')
            ->whereNotIn('career_stage', ['student', 'resident', 'fellow', 'ophthalmologist', 'optometrist', 'other'])
            ->update(['career_stage' => 'other']);

        Schema::dropIfExists('career_stages');
        if (collect(Schema::getIndexes('users'))->contains(fn ($i) => $i['columns'] === ['career_stage'])) {
            Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['career_stage']));
        }
        CareerStage::flush();
    }
};
