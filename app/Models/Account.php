<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

final class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'normal_balance',
        'department',
        'is_active',
        'type', // alias for category
    ];

    public function setTypeAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['category'] = $value;
            if (! isset($this->attributes['normal_balance'])) {
                $this->attributes['normal_balance'] = in_array(strtoupper($value), ['ASSET', 'EXPENSE'], true) ? 'DEBIT' : 'CREDIT';
            }
        }
    }

    protected function casts(): array
    {
        return [
            'code'      => 'string',
            'is_active' => 'boolean',
        ];
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Compute running balance from posted journal entry lines.
     * For DEBIT-normal accounts: balance = total debits - total credits.
     * For CREDIT-normal accounts: balance = total credits - total debits.
     */
    public function getCurrentBalanceAttribute(): string
    {
        $totalDebit  = $this->journalEntryLines->sum('debit');
        $totalCredit = $this->journalEntryLines->sum('credit');

        $balance = strtoupper((string) $this->normal_balance) === 'DEBIT'
            ? bcsub((string) $totalDebit, (string) $totalCredit, 4)
            : bcsub((string) $totalCredit, (string) $totalDebit, 4);

        return $balance;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->attributes['category'] ?? null;
    }

    public function canBeDeactivated(): bool
    {
        return ! $this->journalEntryLines()->exists();
    }

    /** @param Builder<Account> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<Account> $query */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', strtoupper($category));
    }
}
