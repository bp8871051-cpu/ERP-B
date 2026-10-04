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
        // 1. HELP DOCUMENT CATEGORIES
        Schema::create('help_document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable()->default('BookOpen');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. HELP DOCUMENTS
        Schema::create('help_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('help_document_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->enum('status', ['published', 'draft'])->default('published');
            $table->integer('sort_order')->default(0);
            $table->string('read_time')->default('5 min');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'sort_order']);
            $table->index('slug');
        });

        // 3. HELP CHANGELOGS
        Schema::create('help_changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('release_date')->index();
            $table->string('tag')->default('Stable');
            $table->json('changes')->nullable(); // { features: [], improvements: [], bug_fixes: [], breaking_changes: [] }
            $table->enum('status', ['published', 'draft'])->default('published');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'release_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_changelogs');
        Schema::dropIfExists('help_documents');
        Schema::dropIfExists('help_document_categories');
    }
};
