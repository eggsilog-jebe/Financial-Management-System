<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PatientAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id_number',
        'patient_mrn',
        'full_name',
        'name',
        'date_of_birth',
        'gender',
        'admission_type',
        'patient_type',
        'is_nbb',
        'philhealth_member_type',
        'philhealth_id_number',
        'discount_category',
        'id_card_number',
        'hmo_provider',
        'phone',
        'email',
        'address',
        'home_address',
        'total_billed',
        'current_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_billed'    => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_nbb'          => 'boolean',
        ];
    }

    public function getPatientMrnAttribute(): string
    {
        return (string) ($this->attributes['patient_id_number'] ?? '');
    }

    public function setPatientMrnAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['patient_id_number'] = trim($value);
        }
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['full_name'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['full_name'] = trim($value);
        }
    }

    public function getHomeAddressAttribute(): ?string
    {
        return $this->attributes['address'] ?? null;
    }

    public function setHomeAddressAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['address'] = trim($value);
        }
    }

    public function getContactNumberAttribute(): ?string
    {
        return $this->attributes['phone'] ?? null;
    }

    public function setContactNumberAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['phone'] = trim($value);
        }
    }

    public function getEffectiveDiscountCategoryAttribute(): string
    {
        $profileDiscount = strtoupper((string) ($this->discount_category ?? 'NONE'));
        if (in_array($profileDiscount, ['SENIOR_CITIZEN', 'SENIOR', 'SENIOR_CITIZEN_DISCOUNT'], true)) {
            return 'SENIOR_CITIZEN';
        }
        if (in_array($profileDiscount, ['PWD', 'PWD_DISCOUNT'], true)) {
            return 'PWD';
        }
        if (in_array($profileDiscount, ['EMPLOYEE', 'EMPLOYEE_SUBSIDY'], true)) {
            return 'EMPLOYEE';
        }
        if (in_array($profileDiscount, ['CHARITY', 'CHARITY_SUBSIDY'], true)) {
            return 'CHARITY';
        }

        // Check active credit notes across patient invoices or direct credit notes
        $activeCNs = $this->invoices->flatMap->creditNotes->whereIn('status', ['POSTED', 'APPLIED']);
        if ($this->relationLoaded('creditNotes')) {
            $activeCNs = $activeCNs->concat($this->creditNotes->whereIn('status', ['POSTED', 'APPLIED']));
        }

        if ($activeCNs->whereIn('reason', ['PWD_DISCOUNT', 'PWD'])->isNotEmpty()) {
            return 'PWD';
        }

        if ($activeCNs->whereIn('reason', ['SENIOR_CITIZEN_DISCOUNT', 'SENIOR_CITIZEN', 'SENIOR'])->isNotEmpty()
            || $this->invoices->flatMap->statutoryDiscounts->isNotEmpty()) {
            return 'SENIOR_CITIZEN';
        }

        return $profileDiscount;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function guaranteeLetters(): HasMany
    {
        return $this->hasMany(GuaranteeLetter::class);
    }

    /** @param Builder<PatientAccount> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    /** @param Builder<PatientAccount> $query */
    public function scopeWithOutstandingBalance(Builder $query): Builder
    {
        return $query->where('current_balance', '>', 0);
    }
}
