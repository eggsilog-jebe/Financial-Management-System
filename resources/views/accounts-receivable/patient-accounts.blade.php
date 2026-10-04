@extends('layouts.app')

@section('title', 'Patient & Customer Accounts - Accounts Receivable | FMS')
@section('module', 'ar')
@section('page', 'customers')

@section('content')
@php
  $patientAccounts = $patientAccounts ?? $accounts ?? collect();
@endphp
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Patient &amp; HMO Payor Directory
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'createPatientModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-user-plus"></i>
        <span>Register Patient Account</span>
      </button>
    </div>
  </div>

  <!-- Session Alerts -->
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-lg text-emerald-600"></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600"></i>
        <span>{{ session('error') }}</span>
      </div>
    </div>
  @endif

  <!-- Primary Executive Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
    <x-stat-card 
      title="Active Payor Accounts" 
      :value="$totalActive ?? 0" 
      :isCurrency="false" 
      icon="ph-users" 
      color="blue" 
      subtitle="Registered active inpatients & OPD"
      badge="Active"
    />

    <x-stat-card 
      title="Open Patient Receivable (AR)" 
      :value="$totalReceivable ?? 0" 
      icon="ph-receipt" 
      color="rose" 
      subtitle="Total out-of-pocket patient copay balances"
    />

    <x-stat-card 
      title="HMO Guaranteed Portfolio" 
      :value="$hmoGuarantees ?? 0" 
      icon="ph-shield-check" 
      color="teal" 
      subtitle="Approved LOA coverage from private HMOs"
      badge="HMO Claims"
    />
  </div>

  <!-- Accounts Table Container -->
  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ar.patients.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
            <input 
              type="checkbox" 
              name="outstanding_only" 
              value="1" 
              {{ request('outstanding_only') ? 'checked' : '' }} 
              onchange="this.form.submit()"
              class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800"
            >
            <span>Show Accounts With Open Balances Only</span>
          </label>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search MRN, full name, HMO..." 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>
      </form>
    </div>

    <!-- Responsive Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th scope="col" class="py-3.5 pl-5 pr-3 w-32">MRN / ID</th>
            <th scope="col" class="px-3 py-3.5">Patient Full Name</th>
            <th scope="col" class="px-3 py-3.5">Care Setting</th>
            <th scope="col" class="px-3 py-3.5">Statutory Class</th>
            <th scope="col" class="px-3 py-3.5">Insurance / HMO</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Current AR Balance</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($patientAccounts as $acc)
            @php
              $balance = (float) $acc->current_balance;
              $classUpper = strtoupper((string) ($acc->effective_discount_category ?? $acc->discount_category ?? $acc->classification ?? 'GENERAL'));
              $admissionUpper = strtoupper((string) ($acc->admission_type ?? 'INPATIENT'));
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3 font-mono font-bold text-slate-900 dark:text-white text-xs">
                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {{ $acc->patient_id_number }}
                </span>
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $acc->full_name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono flex items-center gap-2 mt-0.5">
                  @if($acc->phone || $acc->contact_number)
                    <span><i class="ph ph-phone text-slate-400"></i> {{ $acc->phone ?: $acc->contact_number }}</span>
                  @endif
                  @if($acc->email)
                    <span class="truncate max-w-[140px]"><i class="ph ph-envelope text-slate-400"></i> {{ $acc->email }}</span>
                  @endif
                </div>
                @if($acc->address)
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-xs mt-0.5 flex items-center gap-1" title="{{ $acc->address }}">
                    <i class="ph ph-map-pin text-slate-400 flex-shrink-0"></i>
                    <span class="truncate">{{ $acc->address }}</span>
                  </div>
                @endif
              </td>
              <td class="px-3 py-3.5 text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold {{ $admissionUpper === 'INPATIENT' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300' : ($admissionUpper === 'EMERGENCY' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300') }}">
                  {{ $admissionUpper }}
                </span>
                @if($acc->room_number)
                  <div class="text-[10px] text-slate-500 font-mono mt-0.5">Room {{ $acc->room_number }}</div>
                @endif
              </td>
              <td class="px-3 py-3.5 text-xs">
                @if($classUpper === 'SENIOR_CITIZEN' || $classUpper === 'SENIOR')
                  <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-purple-600/20 dark:bg-purple-950/40 dark:text-purple-300">
                    <i class="ph-bold ph-identification-card"></i> Senior 20%
                  </span>
                  <div class="text-[10px] text-purple-600 dark:text-purple-400 mt-0.5 font-medium">Senior (RA 9994)</div>
                @elseif($classUpper === 'PWD')
                  <span class="inline-flex items-center gap-1 rounded bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300">
                    <i class="ph-bold ph-wheelchair"></i> PWD 20%
                  </span>
                  <div class="text-[10px] text-teal-600 dark:text-teal-400 mt-0.5 font-medium">PWD (RA 10754)</div>
                @else
                  <span class="text-xs text-slate-500">Regular Non-Exempt</span>
                @endif
              </td>
              <td class="px-3 py-3.5 text-xs">
                <div class="flex items-center gap-1.5 flex-wrap">
                  @if($acc->philhealth_number)
                    <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                      <i class="ph-bold ph-heartbeat"></i> PhilHealth
                    </span>
                  @endif
                  @if($acc->hmo_provider)
                    <span class="inline-flex items-center gap-1 rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                      <i class="ph-bold ph-shield-check"></i> {{ $acc->hmo_provider }}
                    </span>
                  @endif
                </div>
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums {{ $balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                ₱{{ number_format($balance, 2) }}
              </td>
              <td class="px-3 py-3.5 text-center">
                <x-status-badge :status="$balance > 0 ? 'OPEN' : 'SETTLED'" :variant="$balance > 0 ? 'amber' : 'emerald'" />
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <a 
                    href="{{ route('ar.statements', ['patient_id' => $acc->id]) }}" 
                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                    title="View Statement of Account"
                  >
                    <i class="ph-bold ph-file-text"></i>
                    <span>SOA</span>
                  </a>
                  @if($balance > 0)
                    <a 
                      href="{{ route('collection.cashier-desk', ['search' => $acc->patient_id_number]) }}" 
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all"
                      title="Direct Settlement at Cashier POS"
                    >
                      <i class="ph-bold ph-hand-coins"></i>
                      <span>Settle</span>
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-users text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No patient accounts found matching your search.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    @if(method_exists($patientAccounts, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $patientAccounts->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal: Register Patient Account (Alpine.js) -->
<x-modal 
  id="createPatientModal" 
  title="Register New Patient Account" 
  subtitle="Master Patient Index (MPI), statutory discounts & insurance entitlements" 
  icon="ph-user-plus" 
  iconVariant="emerald" 
  size="2xl" 
  formAction="{{ route('ar.patients.store') }}" 
  formMethod="POST" 
  submitText="Register Patient"
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <!-- Demographics -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-identification-badge text-emerald-600"></i>
        <span>Clinical Demographics &amp; Admission</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Patient MRN / ID <span class="text-rose-500">*</span></label>
          <input type="text" name="patient_id_number" placeholder="MRN-2026-0001" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Full Legal Name <span class="text-rose-500">*</span></label>
          <input type="text" name="full_name" placeholder="Last Name, First Name Middle Name" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Admission Type <span class="text-rose-500">*</span></label>
          <select name="admission_type" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
            <option value="INPATIENT">Inpatient (IPD)</option>
            <option value="OUTPATIENT">Outpatient (OPD)</option>
            <option value="EMERGENCY">Emergency (ER)</option>
          </select>
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Room / Bed #</label>
          <input type="text" name="room_number" placeholder="e.g. 402-A" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Contact Phone</label>
          <input type="text" name="phone" placeholder="+63 900 000 0000" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
          <input type="email" name="email" placeholder="patient@example.com" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Residential / Billing Address</label>
          <input type="text" name="address" placeholder="Unit, Street, Barangay, City / Municipality, Province" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- Statutory Classification -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-scales text-purple-600"></i>
        <span>Philippine Statutory Discounts (RA 9994 / RA 10754)</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Statutory Classification <span class="text-rose-500">*</span></label>
          <select name="classification" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
            <option value="GENERAL">Regular Patient (Non-Exempt)</option>
            <option value="SENIOR_CITIZEN">Senior Citizen (RA 9994: 20% Discount + VAT Exemption)</option>
            <option value="PWD">Person with Disability (RA 10754: 20% Discount + VAT Exemption)</option>
          </select>
        </div>
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">OSCA / PWD ID Number</label>
          <input type="text" name="senior_pwd_id" placeholder="OSCA/PWD ID for audit verification" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- PhilHealth & Private HMO -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-shield-check text-blue-600"></i>
        <span>Third-Party Payers (PhilHealth &amp; Private HMO)</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">PhilHealth Identification # (PIN)</label>
          <input type="text" name="philhealth_number" placeholder="12-digit PIN" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Private HMO Provider</label>
          <input type="text" name="hmo_provider" placeholder="Maxicare, Intellicare, Medicard..." class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">HMO Card / Policy Number</label>
          <input type="text" name="hmo_policy_number" placeholder="Policy or LOA number" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Approved HMO LOA Limit (₱)</label>
          <input type="number" step="0.01" name="hmo_approval_limit" placeholder="0.00" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>
  </div>
</x-modal>
@endsection
