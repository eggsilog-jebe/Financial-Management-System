<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add hardware device_type classification (Desktop, Laptop, Mobile, Tablet)
     * to user_workstations and strip redundant trailing IP addresses from existing labels.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('user_workstations', 'device_type')) {
            Schema::table('user_workstations', function (Blueprint $table): void {
                $table->string('device_type', 20)->nullable()->after('workstation_name')->index();
            });
        }

        // Clean existing workstation_name records that contain redundant " - <IP_ADDRESS>" suffixes
        // and backfill device_type based on platform/name heuristics.
        DB::table('user_workstations')
            ->orderBy('id')
            ->chunkById(100, function ($workstations): void {
                foreach ($workstations as $ws) {
                    $cleanName = (string) preg_replace('/\s*-\s*(?:\d{1,3}(?:\.\d{1,3}){3}|[0-9a-fA-F:]{2,})\s*$/', '', (string) $ws->workstation_name);

                    $platformLower = strtolower((string) ($ws->platform ?? ''));
                    $nameLower     = strtolower($cleanName);

                    $deviceType = match (true) {
                        str_contains($platformLower, 'mobile') || str_contains($platformLower, 'ios') || str_contains($platformLower, 'android') || str_contains($nameLower, 'phone') || str_contains($nameLower, 'mobile') => 'Mobile',
                        str_contains($platformLower, 'ipad') || str_contains($platformLower, 'tablet') || str_contains($nameLower, 'tablet') || str_contains($nameLower, 'ipad') => 'Tablet',
                        str_contains($nameLower, 'laptop') || str_contains($nameLower, 'macbook') || str_contains($nameLower, 'notebook') => 'Laptop',
                        default => 'Desktop',
                    };

                    DB::table('user_workstations')
                        ->where('id', $ws->id)
                        ->update([
                            'workstation_name' => $cleanName !== '' ? $cleanName : $ws->workstation_name,
                            'device_type'      => $deviceType,
                        ]);
                }
            });

        // Clean redundant IP suffixes from user_active_sessions.device_name as well
        if (Schema::hasTable('user_active_sessions')) {
            DB::table('user_active_sessions')
                ->whereNotNull('device_name')
                ->orderBy('id')
                ->chunkById(100, function ($sessions): void {
                    foreach ($sessions as $session) {
                        $cleanDeviceName = (string) preg_replace('/\s*-\s*(?:\d{1,3}(?:\.\d{1,3}){3}|[0-9a-fA-F:]{2,})\s*$/', '', (string) $session->device_name);
                        if ($cleanDeviceName !== '' && $cleanDeviceName !== $session->device_name) {
                            DB::table('user_active_sessions')
                                ->where('id', $session->id)
                                ->update(['device_name' => $cleanDeviceName]);
                        }
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_workstations', 'device_type')) {
            Schema::table('user_workstations', function (Blueprint $table): void {
                $table->dropIndex(['device_type']);
                $table->dropColumn('device_type');
            });
        }
    }
};

