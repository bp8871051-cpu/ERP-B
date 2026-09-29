<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. User Layout Preferences Table
        Schema::create('user_layout_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('sidebar_mode')->default('default'); // 'default', 'mini', 'hover', 'hidden'
            $table->string('menu_behavior')->default('click');   // 'click', 'hover'
            $table->string('content_width')->default('default'); // 'default', 'full'
            $table->string('direction')->default('ltr');         // 'ltr', 'rtl'
            $table->string('sidebar_visibility')->default('visible'); // 'visible', 'hidden'
            $table->string('sidebar_state')->default('expanded');     // 'expanded', 'collapsed'
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['sidebar_mode', 'direction']);
        });

        // 2. System Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // 'string', 'boolean', 'json', 'integer'
            $table->string('group')->default('general');
            $table->timestamps();

            $table->index(['group']);
        });

        // Seed default system layout settings
        DB::table('system_settings')->insert([
            [
                'key' => 'layout.default_sidebar_mode',
                'value' => 'default',
                'type' => 'string',
                'group' => 'layout',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'layout.default_menu_behavior',
                'value' => 'click',
                'type' => 'string',
                'group' => 'layout',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'layout.default_content_width',
                'value' => 'default',
                'type' => 'string',
                'group' => 'layout',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'layout.default_direction',
                'value' => 'ltr',
                'type' => 'string',
                'group' => 'layout',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_layout_preferences');
        Schema::dropIfExists('system_settings');
    }
};
