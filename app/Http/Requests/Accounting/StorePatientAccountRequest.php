<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePatientAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('contact_number') && ! $this->filled('phone')) {
            $merge['phone'] = $this->input('contact_number');
        }

        if ($this->filled('classification') && ! $this->filled('discount_category')) {
            $class = strtoupper(trim((string) $this->input('classification')));
            $merge['discount_category'] = $class === 'GENERAL' ? 'NONE' : $class;
        }

        if ($this->filled('senior_pwd_id') && ! $this->filled('id_card_number')) {
            $merge['id_card_number'] = $this->input('senior_pwd_id');
        }

        if ($this->filled('admission_type')) {
            $rawType = strtoupper(trim((string) $this->input('admission_type')));
            $merge['admission_type'] = match ($rawType) {
                'OUTPATIENT' => 'Outpatient',
                'EMERGENCY'  => 'Emergency',
                default      => 'Inpatient',
            };
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'patient_mrn'       => ['nullable', 'string', 'max:30', Rule::unique('patient_accounts', 'patient_id_number')],
            'patient_id_number' => ['nullable', 'string', 'max:30', Rule::unique('patient_accounts', 'patient_id_number')],
            'full_name'         => ['required', 'string', 'max:255'],
            'admission_type'    => ['required', 'string', 'in:Inpatient,Outpatient,Emergency'],
            'discount_category' => ['nullable', 'string', 'in:NONE,SENIOR_CITIZEN,PWD,EMPLOYEE_SUBSIDY,EMPLOYEE,CHARITY'],
            'classification'    => ['nullable', 'string', 'max:50'],
            'id_card_number'    => ['nullable', 'string', 'max:50'],
            'senior_pwd_id'     => ['nullable', 'string', 'max:50'],
            'hmo_provider'      => ['nullable', 'string', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'contact_number'    => ['nullable', 'string', 'max:50'],
            'email'             => ['nullable', 'email', 'max:100'],
            'address'           => ['nullable', 'string', 'max:255'],
            'room_number'       => ['nullable', 'string', 'max:50'],
            'philhealth_number' => ['nullable', 'string', 'max:50'],
            'hmo_policy_number' => ['nullable', 'string', 'max:50'],
            'hmo_approval_limit'=> ['nullable', 'numeric', 'min:0'],
            'status'            => ['nullable', 'string', 'in:Active,Inactive,Discharged'],
        ];
    }
}
