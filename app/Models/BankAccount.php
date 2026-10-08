<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'gl_code',
        'gl_account_id',
        'purpose',
        'currency',
        'opening_balance',
        'balance',
        'minimum_balance',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'balance'         => 'decimal:4',
            'minimum_balance' => 'decimal:4',
            'is_active'       => 'boolean',
        ];
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class, 'bank_account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active')->where('is_active', true);
    }
}
