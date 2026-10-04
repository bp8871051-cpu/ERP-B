<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. UI Demo Records Table (for Data Table showcase)
        if (!Schema::hasTable('ui_demo_records')) {
            Schema::create('ui_demo_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('role')->default('Staff');
                $table->string('department')->default('Operations');
                $table->string('status')->default('active'); // active, inactive, pending, suspended
                $table->decimal('salary', 12, 2)->default(50000.00);
                $table->date('joined_date')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. UI Drag & Drop Items Table (for Dragula kanban persistence)
        if (!Schema::hasTable('ui_drag_drop_items')) {
            Schema::create('ui_drag_drop_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('todo'); // todo, in_progress, review, done
                $table->string('priority')->default('medium'); // low, medium, high, urgent
                $table->integer('order_index')->default(0);
                $table->string('assigned_to')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. UI Table & User Preferences Table
        if (!Schema::hasTable('ui_table_preferences')) {
            Schema::create('ui_table_preferences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('table_key')->default('ui_data_table');
                $table->json('visible_columns')->nullable();
                $table->string('density')->default('comfortable'); // compact, comfortable, spacious
                $table->integer('per_page')->default(10);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ui_table_preferences');
        Schema::dropIfExists('ui_drag_drop_items');
        Schema::dropIfExists('ui_demo_records');
    }
};
