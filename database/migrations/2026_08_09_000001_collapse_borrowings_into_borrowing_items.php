<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapse redundant borrowings into borrowing_items (single source of truth).
     */
    public function up(): void
    {
        Schema::table('borrowing_items', function (Blueprint $table) {
            $table->foreignId('checked_out_by')
                ->nullable()
                ->after('check_notes')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->after('checked_out_by')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::dropIfExists('borrowings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_item_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('borrower_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('borrow_date');
            $table->dateTime('expected_return_date')->nullable();
            $table->dateTime('actual_return_date')->nullable();
            $table->enum('condition_before', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->enum('condition_after', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->nullable();
            $table->boolean('is_damaged')->default(false);
            $table->text('damage_notes')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('checked_at')->nullable();
            $table->text('check_notes')->nullable();
            $table->enum('status', [
                'dipinjam',
                'dikembalikan',
                'terlambat',
                'hilang',
            ])->default('dipinjam');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['borrower_id', 'status']);
            $table->index('expected_return_date');
            $table->index('actual_return_date');
            $table->index('status');
        });

        Schema::table('borrowing_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropConstrainedForeignId('checked_out_by');
        });
    }
};
