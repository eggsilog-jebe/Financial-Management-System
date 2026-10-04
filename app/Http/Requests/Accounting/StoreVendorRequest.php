<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Parse string inputs like 'Net 30', 'Net 30 Days', '30 days' into integer
        $paymentTerms = $this->input('payment_terms') ?? $this->input('payment_terms_days');
        if (is_string($paymentTerms)) {
            preg_match('/\d+/', $paymentTerms, $matches);
            $days = ! empty($matches) ? (int) $matches[0] : 30;
            $this->merge([
                'payment_terms' => $days,
                'payment_terms_days' => $days,
            ]);
        } elseif (is_numeric($paymentTerms)) {
            $this->merge([
                'payment_terms' => (int) $paymentTerms,
                'payment_terms_days' => (int) $paymentTerms,
            ]);
        }

        // Clean empty string code so unique rule allows auto-generation
        if ($this->has('code') && trim((string) $this->input('code')) === '') {
            $this->merge(['code' => null]);
        }
        if ($this->has('vendor_code') && trim((string) $this->input('vendor_code')) === '') {
            $this->merge(['vendor_code' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'code'                => ['nullable', 'string', 'max:30', Rule::unique('vendors', 'code')],
            'vendor_code'         => ['nullable', 'string', 'max:30', Rule::unique('vendors', 'code')],
            'name'                => ['required', 'string', 'max:255'],
            'tin'                 => ['nullable', 'string', 'max:30'],
            'tax_type'            => ['nullable', 'string', 'in:VAT_REGISTERED,NON_VAT'],
            'default_ewt_rate'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_atc_code'    => ['nullable', 'string', 'max:20'],
            'contact_person'      => ['nullable', 'string', 'max:255'],
            'email'               => ['nullable', 'email', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'registered_address'  => ['nullable', 'string', 'max:500'],
            'bank_name'           => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'payment_terms_days'  => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_terms'       => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active'           => ['nullable'],
            'status'              => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }
}
