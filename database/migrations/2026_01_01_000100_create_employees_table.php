<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('position');
            $table->string('department')->nullable();
            $table->date('join_date');
            $table->string('employment_status')->default('permanent');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->text('nik')->nullable();
            $table->text('npwp')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
