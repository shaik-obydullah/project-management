<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key', 30)->index();
            $table->enum('type', ['epic', 'story', 'task', 'bug', 'subtask'])->default('task');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('column_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('issues')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('estimate', 8, 2)->nullable();
            $table->date('due_at')->nullable();
            $table->unsignedBigInteger('sprint_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('issue_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->unique(['issue_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_label');
        Schema::dropIfExists('issues');
    }
};