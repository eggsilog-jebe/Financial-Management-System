<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model representing an authorized or pending workstation computer bound to a hospital user account.
 * Enforces enterprise hardware-level access control (max 1–3 workstations per user).
 *
 * @property int $id
 * @property int $user_id
 * @property string $device_uuid
 * @property string $workstation_name
 * @property string|null $device_type
 * @property string|null $platform
 * @property string|null $browser
 * @property string|null $ip_address
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property int|null $revoked_by
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 */
final class UserWorkstation extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVOKED  = 'revoked';

    public const DEVICE_DESKTOP = 'Desktop';
    public const DEVICE_LAPTOP  = 'Laptop';
    public const DEVICE_MOBILE  = 'Mobile';
    public const DEVICE_TABLET  = 'Tablet';

    public const MAX_PER_USER = 3;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'workstation_name',
        'device_type',
        'platform',
        'browser',
        'ip_address',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'revoked_by',
        'revoked_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at'  => 'datetime',
            'rejected_at'  => 'datetime',
            'revoked_at'   => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * The hospital user this workstation is bound to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Super Admin / CFO who approved this workstation binding.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The Super Admin / CFO who revoked this workstation binding.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Active sessions originating from this workstation.
     */
    public function activeSessions(): HasMany
    {
        return $this->hasMany(UserActiveSession::class, 'workstation_id');
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REVOKED);
    }

    // Status Helpers
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /**
     * Return a clean workstation display name stripped of any legacy redundant IP suffix.
     */
    public function cleanWorkstationName(): string
    {
        $raw = (string) $this->workstation_name;
        $cleaned = (string) preg_replace('/\s*-\s*(?:\d{1,3}(?:\.\d{1,3}){3}|[0-9a-fA-F:]{2,})\s*$/', '', $raw);

        return $cleaned !== '' ? $cleaned : $raw;
    }

    /**
     * Resolve the normalized hardware form-factor category (Desktop, Laptop, Mobile, Tablet).
     */
    public function resolvedDeviceType(): string
    {
        if (! empty($this->device_type) && in_array($this->device_type, [self::DEVICE_DESKTOP, self::DEVICE_LAPTOP, self::DEVICE_MOBILE, self::DEVICE_TABLET], true)) {
            return $this->device_type;
        }

        $platformLower = strtolower((string) ($this->platform ?? ''));
        $nameLower     = strtolower((string) ($this->workstation_name ?? ''));

        return match (true) {
            str_contains($platformLower, 'mobile') || str_contains($platformLower, 'ios') || str_contains($platformLower, 'android') || str_contains($nameLower, 'phone') || str_contains($nameLower, 'mobile') => self::DEVICE_MOBILE,
            str_contains($platformLower, 'ipad') || str_contains($platformLower, 'tablet') || str_contains($nameLower, 'tablet') || str_contains($nameLower, 'ipad') => self::DEVICE_TABLET,
            str_contains($nameLower, 'laptop') || str_contains($nameLower, 'macbook') || str_contains($nameLower, 'notebook') => self::DEVICE_LAPTOP,
            default => self::DEVICE_DESKTOP,
        };
    }

    /**
     * Human-readable label for the hardware form factor (e.g. "Laptop", "Desktop", "Phone", "Tablet").
     */
    public function deviceTypeLabel(): string
    {
        return match ($this->resolvedDeviceType()) {
            self::DEVICE_MOBILE  => 'Phone',
            self::DEVICE_LAPTOP  => 'Laptop',
            self::DEVICE_TABLET  => 'Tablet',
            default              => 'Desktop',
        };
    }

    /**
     * Phosphor icon class representing the hardware form factor.
     */
    public function deviceTypeIcon(): string
    {
        return match ($this->resolvedDeviceType()) {
            self::DEVICE_MOBILE  => 'ph-device-mobile',
            self::DEVICE_LAPTOP  => 'ph-laptop',
            self::DEVICE_TABLET  => 'ph-device-tablet',
            default              => 'ph-desktop',
        };
    }
}
