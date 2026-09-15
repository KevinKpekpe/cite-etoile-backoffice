<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'active', 'commercializable', 'completed', 'suspended'])->default('active');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index('status', 'idx_neighborhoods_status');
        });

        Schema::create('avenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neighborhood_id');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'active', 'suspended'])->default('active');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['neighborhood_id', 'code'], 'uq_avenues_neighborhood_code');
            $table->unique(['neighborhood_id', 'name'], 'uq_avenues_neighborhood_name');
            $table->index('neighborhood_id', 'idx_avenues_neighborhood');
            $table->foreign('neighborhood_id', 'fk_avenues_neighborhood')->references('id')->on('neighborhoods')->restrictOnDelete();
        });

        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('plot_number', 50);
            $table->string('reference', 100)->unique();
            $table->foreignId('avenue_id');
            $table->decimal('surface_area', 12, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->string('cadastral_reference', 150)->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->enum('commercial_status', ['available', 'reserved', 'subscribed', 'blocked', 'unavailable'])->default('available');
            $table->enum('financial_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->enum('administrative_status', ['not_started', 'in_progress', 'validated', 'allocated', 'dispute'])->default('not_started');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['avenue_id', 'plot_number'], 'uq_plots_avenue_number');
            $table->index('avenue_id', 'idx_plots_avenue');
            $table->index('commercial_status', 'idx_plots_commercial_status');
            $table->index('financial_status', 'idx_plots_financial_status');
            $table->foreign('avenue_id', 'fk_plots_avenue')->references('id')->on('avenues')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE plots ADD CONSTRAINT chk_plots_surface_area CHECK (surface_area IS NULL OR surface_area > 0)');
        DB::statement('ALTER TABLE plots ADD CONSTRAINT chk_plots_width CHECK (width IS NULL OR width > 0)');
        DB::statement('ALTER TABLE plots ADD CONSTRAINT chk_plots_length CHECK (length IS NULL OR length > 0)');
        DB::statement('ALTER TABLE plots ADD CONSTRAINT chk_plots_base_price CHECK (base_price IS NULL OR base_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plots');
        Schema::dropIfExists('avenues');
        Schema::dropIfExists('neighborhoods');
    }
};
