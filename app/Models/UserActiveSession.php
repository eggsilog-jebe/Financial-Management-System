<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tracking active browser/workstation sessions for single active session enforcement
 * and immediate session displacement upon multi-login.
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property int|null $workstation_id
 * @property string|null $device_uuid
 * @property string|null $device_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $login_at
 * @property \Illuminate\Support\Carbon $last_activity_at
 * @property bool $is_terminated
 * @property string|null $termination_reason
 * @property \Illuminate\Support\Carbon|null $terminated_at
 */
final class UserActiveSession extends Model
{
    use HasFactory;

    public const REASON_DISPLACED          = 'displaced_by_new_login';
    public const REASON_ADMIN_REVOKED      = 'revoked_by_admin';
    public const REASON_MANUAL_LOGOUT      = 'manual_logout';
    public const REASON_WORKSTATION_REVOKED = 'workstation_revoked';
    public const REASON_IDLE_TIMEOUT        = 'idle_timeout';

    protected $fillable = [
        'user_id',
        'session_id',
        'workstation_id',
        'device_uuid',
        'device_name',
        'ip_address',
        'user_agent',
        'login_at',
        'last_activity_at',
        'is_terminated',
        'termination_reason',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'login_at'         => 'datetime',
            'last_activity_at' => 'datetime',
            'terminated_at'    => 'datetime',
            'is_terminated'    => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(UserWorkstation::class, 'workstation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_terminated', false);
    }

    public function terminate(string $reason): void
    {
        $this->update([
            'is_terminated'      => true,
            'termination_reason' => $reason,
            'terminated_at'      => now(),
        ]);
    }
}
