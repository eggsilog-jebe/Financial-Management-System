<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

final class PrepareDisbursementVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $billId = $this->input('purchase_bill_id');
        $bill = $billId ? \App\Models\PurchaseBill::with('vendor')->find($billId) : null;

        // Auto-assign bank_account_id if omitted
        if (! $this->has('bank_account_id') || empty($this->input('bank_account_id'))) {
            $defaultBankId = \App\Models\BankAccount::where('status', 'Active')->value('id')
                ?? \App\Models\BankAccount::value('id');
            if ($defaultBankId) {
                $this->merge(['bank_account_id' => $defaultBankId]);
            }
        }

        // Auto-assign amount if omitted (uncommitted balance of the bill)
        if ((! $this->has('amount') || empty($this->input('amount'))) && $bill) {
            $committed = (string) \App\Models\DisbursementVoucher::where('purchase_bill_id', $bill->id)
                ->whereIn('status', ['DRAFT', 'AUDITED', 'APPROVED', 'RELEASED'])
                ->sum('net_disbursed_amount');
            $unpaid = bcsub((string) $bill->total_amount, $committed, 4);
            $amount = bccomp($unpaid, '0.0000', 4) > 0 ? $unpaid : '0.0000';
            $this->merge(['amount' => (float) $amount]);
        }

        // Auto-assign payment_method if omitted
        if (! $this->has('payment_method') || empty($this->input('payment_method'))) {
            $this->merge(['payment_method' => 'PESONET_EFT']);
        }

        // Auto-assign payee_name if omitted
        if ((! $this->has('payee_name') || empty($this->input('payee_name'))) && $bill) {
            $this->merge(['payee_name' => $bill->vendor?->name ?? 'Vendor']);
        }

        // Auto-assign voucher_date if omitted
        if (! $this->has('voucher_date') || empty($this->input('voucher_date'))) {
            $this->merge(['voucher_date' => date('Y-m-d')]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $billId = $this->input('purchase_bill_id');
            if ($billId) {
                $bill = \App\Models\PurchaseBill::find($billId);
                if ($bill) {
                    $committed = (string) \App\Models\DisbursementVoucher::where('purchase_bill_id', $bill->id)
                        ->whereIn('status', ['DRAFT', 'AUDITED', 'APPROVED', 'RELEASED'])
                        ->sum('net_disbursed_amount');
                    $available = bcsub((string) $bill->total_amount, $committed, 4);
                    if (bccomp($available, '0.0000', 4) <= 0) {
                        $validator->errors()->add('purchase_bill_id', "Purchase Bill [{$bill->bill_number}] already has active disbursement vouchers covering its full balance (₱" . number_format((float) $bill->total_amount, 2) . "). Please authorize or release in Payment Approvals.");
                    }
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'purchase_bill_id' => ['required', 'exists:purchase_bills,id'],
            'bank_account_id'  => ['required', 'exists:bank_accounts,id'],
            'voucher_date'     => ['nullable', 'date'],
            'amount'           => ['required', 'numeric', 'gt:0'],
            'payment_method'   => ['required', 'string', 'in:CHECK,PESONET_EFT,INSTAPAY,PETTY_CASH,TELEGRAPHIC_TRANSFER'],
            'payee_name'       => ['nullable', 'string', 'max:255'],
            'check_or_eft_ref' => ['nullable', 'string', 'max:50'],
        ];
    }
}
