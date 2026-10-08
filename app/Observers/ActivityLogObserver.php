<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

final class ActivityLogObserver
{
    /**
     * Sensitive attributes that must never be recorded in audit diffs.
     */
    private const SENSITIVE_ATTRIBUTES = [
        'password',
        'remember_token',
        'api_token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'card_number',
        'cvv',
    ];

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        // Don't audit ActivityLog itself to prevent infinite loops
        if ($model instanceof ActivityLog) {
            return;
        }

        $module = $this->resolveModule($model);
        $cleanAttributes = $this->sanitize($model->getAttributes());
        $reference = $this->resolveReference($model);

        ActivityLog::logModel(
            event: 'created',
            model: $model,
            module: $module,
            description: "Created new {$module} record [{$reference}]",
            oldValues: null,
            newValues: $cleanAttributes
        );
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        $changes = $this->sanitize($model->getChanges());

        // Ignore timestamps and activity heartbeat updates
        unset($changes['updated_at'], $changes['last_activity_at'], $changes['last_seen_at']);

        if (empty($changes)) {
            return;
        }

        $module = $this->resolveModule($model);
        $reference = $this->resolveReference($model);
        $changedKeys = array_keys($changes);

        // Fetch matching original values for changed keys only
        $originals = [];
        foreach ($changedKeys as $key) {
            $originals[$key] = $model->getOriginal($key);
        }
        $cleanOriginals = $this->sanitize($originals);

        ActivityLog::logModel(
            event: 'updated',
            model: $model,
            module: $module,
            description: "Modified {$module} record [{$reference}] (fields changed: " . implode(', ', $changedKeys) . ')',
            oldValues: $cleanOriginals,
            newValues: $changes
        );
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        $module = $this->resolveModule($model);
        $reference = $this->resolveReference($model);

        ActivityLog::logModel(
            event: 'deleted',
            model: $model,
            module: $module,
            description: "Removed {$module} record [{$reference}]",
            oldValues: $this->sanitize($model->getAttributes()),
            newValues: null
        );
    }

    /**
     * Map model class names to standard hospital financial modules.
     */
    private function resolveModule(Model $model): string
    {
        $class = class_basename($model);

        return match ($class) {
            'JournalEntry', 'JournalEntryLine', 'Account', 'FiscalPeriod' => 'General Ledger',
            'PatientAccount', 'BillItem', 'Invoice', 'InvoiceItem', 'CreditNote', 'DoctorProfile', 'StatutoryDiscount' => 'Patient Billing (AR)',
            'PurchaseBill', 'Vendor', 'ThreeWayMatch', 'Bir2307Certificate' => 'Accounts Payable (AP)',
            'DisbursementVoucher', 'CheckRegister', 'PettyCashExpense', 'PettyCashFund', 'PaymentRequest', 'PayrollRun', 'PayrollItem' => 'Disbursements',
            'CashierShift', 'Payment', 'OfficialReceipt', 'PaymentReceipt' => 'Cashier POS & Collections',
            'BudgetAllocation', 'BudgetEncumbrance', 'BudgetReallocation' => 'Fiscal Budgets',
            'BankAccount', 'BankDeposit'                  => 'Disbursements & Treasury',
            'GuaranteeLetter'                             => 'Malasakit & Subsidies',
            'HmoClaim', 'PhilhealthClaim'                 => 'Claims & Subsidies',
            'User', 'UserActiveSession', 'UserWorkstation' => 'User & Security',
            default => $class,
        };
    }

    /**
     * Extract a human-readable identifier from the model.
     */
    private function resolveReference(Model $model): string
    {
        $id = $model->getKey();

        foreach ([
            'entry_number',
            'invoice_number',
            'bill_number',
            'voucher_number',
            'receipt_number',
            'check_number',
            'account_number',
            'workstation_name',
            'period_name',
            'patient_name',
            'vendor_name',
            'code',
            'name',
            'title',
            'email',
        ] as $field) {
            if (! empty($model->getAttribute($field))) {
                return "{$field}: " . $model->getAttribute($field) . " (#{$id})";
            }
        }

        return "#{$id}";
    }

    /**
     * Strip out passwords, tokens, and binary hashes.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function sanitize(array $attributes): array
    {
        foreach (self::SENSITIVE_ATTRIBUTES as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = '[REDACTED]';
            }
        }

        return $attributes;
    }
}
