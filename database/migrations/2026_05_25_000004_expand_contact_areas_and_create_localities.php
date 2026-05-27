<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_areas', function (Blueprint $table) {
            $table->string('notification_email', 150)->nullable()->after('email');
            $table->string('backup_contact_name', 120)->nullable()->after('notification_email');
            $table->string('backup_email', 150)->nullable()->after('backup_contact_name');
            $table->string('backup_phone', 40)->nullable()->after('backup_email');
        });

        Schema::create('municipality_localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 40)->default('bairro');
            $table->string('zone', 80)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['municipality_id', 'name']);
            $table->index(['municipality_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_localities');

        Schema::table('contact_areas', function (Blueprint $table) {
            $table->dropColumn([
                'notification_email',
                'backup_contact_name',
                'backup_email',
                'backup_phone',
            ]);
        });
    }
};
