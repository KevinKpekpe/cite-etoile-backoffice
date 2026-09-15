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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id', 'fk_user_roles_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('role_id', 'fk_user_roles_role')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id', 'fk_role_permissions_role')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $table->foreign('permission_id', 'fk_role_permissions_permission')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
