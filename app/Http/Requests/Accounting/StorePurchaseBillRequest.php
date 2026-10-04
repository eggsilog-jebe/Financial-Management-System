<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'             => ['required', 'exists:vendors,id'],
            'doctor_id'             => ['nullable', 'exists:doctor_profiles,id'],
            'bill_date'             => ['required', 'date'],
            'due_date'              => ['required', 'date', 'after_or_equal:bill_date'],
            'po_number'             => ['nullable', 'string', 'max:50'],
            'grn_number'            => ['nullable', 'string', 'max:50'],
            'vendor_invoice_number' => ['nullable', 'string', 'max:50'],
            'po_amount'             => ['nullable', 'numeric', 'min:0'],
            'grn_amount'            => ['nullable', 'numeric', 'min:0'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.item_code'     => ['nullable', 'string', 'max:50'],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.expense_type'  => ['nullable', 'string', 'in:GOODS_INVENTORY,SERVICES_MAINTENANCE,DOCTOR_PROFESSIONAL_FEE,CAPEX_EQUIPMENT,UTILITIES,SPACE_RENTAL,EXEMPT'],
            'items.*.quantity'      => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.atc_code'      => ['nullable', 'string', 'max:20'],
        ];
    }
}
