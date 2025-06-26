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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('date_of_birth');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('password_reset_token')->nullable()->after('last_login_ip');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
            $table->string('google_id')->nullable()->after('password_reset_expires_at');
            $table->string('facebook_id')->nullable()->after('google_id');
            $table->string('github_id')->nullable()->after('facebook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'avatar',
                'last_login_at',
                'last_login_ip',
                'password_reset_token',
                'password_reset_expires_at',
                'google_id',
                'facebook_id',
                'github_id'
            ]);
        });
    }
};