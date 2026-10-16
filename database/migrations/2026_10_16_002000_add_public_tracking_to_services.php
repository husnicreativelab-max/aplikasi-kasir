<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->char('tracking_code_hash', 64)->nullable()->index();
            $table->text('tracking_code_encrypted')->nullable();
        });

        Schema::table('service_status_logs', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->index();
        });

        DB::table('service_status_logs')
            ->where('note', 'like', 'Estimasi diperbarui dari Rp %')
            ->update(['is_public' => false]);

        DB::table('services')->whereNull('tracking_code_hash')->orderBy('id')->chunkById(100, function ($services): void {
            foreach ($services as $service) {
                $code = Str::upper(Str::random(24));
                DB::table('services')->where('id', $service->id)->update([
                    'tracking_code_hash' => hash('sha256', $code),
                    'tracking_code_encrypted' => Crypt::encryptString($code),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_status_logs', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['tracking_code_hash', 'tracking_code_encrypted']);
        });
    }
};
