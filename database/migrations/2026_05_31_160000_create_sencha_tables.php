<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. fs_documents table (NoSQL fallback shim for small collections)
        Schema::create('fs_documents', function (Blueprint $table) {
            $table->string('path')->primary();
            $table->string('parent_path')->index();
            $table->json('data');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('fs_documents', function (Blueprint $table) {
            $table->index(['parent_path', 'updated_at']);
        });

        // 2. hrd_users table
        Schema::create('hrd_users', function (Blueprint $table) {
            $table->string('username')->primary();
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('hrd_users')->insertOrIgnore([
            'username' => 'admin',
            'password_hash' => '0208788aa2035cd5be6697efbd285df1afa881c8fd25e4bd5bbb247c29c58454', // admin123*
            'created_at' => now()
        ]);

        // 3. app_sessions table
        Schema::create('app_sessions', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->string('role'); 
            $table->json('payload');
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. Normalized applicants table (EXTREMELY LIGHT & FAST!)
        Schema::create('applicants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama', 100)->nullable()->index();
            $table->string('nama_normalized', 100)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('whatsapp_normalized', 30)->nullable()->index();
            $table->string('tanggal_lahir', 50)->nullable();
            $table->integer('umur_saat_input')->nullable();
            $table->boolean('masih_bekerja')->default(false);
            $table->string('posisi', 100)->nullable()->index();
            $table->json('posisi_list')->nullable();
            $table->string('status', 50)->default('baru')->index();
            $table->string('source', 50)->nullable();
            $table->json('undangan_by_posisi')->nullable();
            $table->string('access_token', 100)->nullable();
            $table->string('cv_mode', 50)->nullable();
            $table->text('catatan')->nullable();
            $table->json('catatan_list')->nullable();
            $table->timestamps();
        });

        // 5. Separate table for heavy base64 resume payloads
        Schema::create('applicant_files', function (Blueprint $table) {
            $table->string('applicant_id')->primary();
            $table->longText('cv_file')->nullable(); // Large Base64 string
            $table->json('photos')->nullable();

            $table->foreign('applicant_id')
                  ->references('id')
                  ->on('applicants')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_files');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('app_sessions');
        Schema::dropIfExists('hrd_users');
        Schema::dropIfExists('fs_documents');
    }
};
