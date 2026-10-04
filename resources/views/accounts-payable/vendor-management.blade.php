@extends('layouts.app')

@section('title', 'Vendor Management - Accounts Payable | FMS')
@section('module', 'ap')
@section('page', 'vendors')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Vendor Directory &amp; Supplier Master
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'addVendorModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Register New Vendor</span>
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

  @if($errors->any())
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <div class="flex items-center gap-2 mb-2 font-bold text-rose-700 dark:text-rose-300">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600"></i>
        <span>Please correct the errors below before saving:</span>
      </div>
      <ul class="list-disc list-inside space-y-1 pl-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Summary Cards Row -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Accredited Suppliers" 
      :value="$totalActiveVendors ?? 0" 
      :isCurrency="false" 
      icon="ph-buildings" 
      color="emerald" 
      subtitle="Ready for Purchase Orders"
      badge="Active"
    />

    <x-stat-card 
      title="Total AP Liability" 
      :value="$totalApLiability ?? 0" 
      icon="ph-receipt" 
      color="rose" 
      subtitle="Gross outstanding supplier payables"
    />

    <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition-all hover:shadow-md dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Standard Credit Terms</span>
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/20 dark:bg-blue-950/50 dark:text-blue-400">
          <i class="ph-bold ph-clock text-lg"></i>
        </span>
      </div>
      <div class="mt-4 flex items-baseline justify-between gap-2">
        <div class="kpi-value font-sans text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
          Net 30 Days
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
          Standard
        </span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Average settlement window post delivery</p>
    </div>

    <x-stat-card 
      title="EFT Settlement Ready" 
      :value="$vendors->whereNotNull('bank_account_number')->count()" 
      :isCurrency="false" 
      icon="ph-bank" 
      color="blue" 
      subtitle="Direct electronic bank transfer configured"
      badge="EFT Ready"
    />
  </div>

  <!-- Vendors Table Card -->
  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ap.vendors') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-funnel"></i>
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active Only</option>
            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive Only</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search vendor name, code, contact..." 
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
            <th scope="col" class="py-3.5 pl-5 pr-3 w-28">Vendor Code</th>
            <th scope="col" class="px-3 py-3.5">Supplier Legal Name</th>
            <th scope="col" class="px-3 py-3.5">Settlement Bank &amp; EFT</th>
            <th scope="col" class="px-3 py-3.5">Business Address</th>
            <th scope="col" class="px-3 py-3.5">Contact Person</th>
            <th scope="col" class="px-3 py-3.5">Phone / Email</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Current Balance</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($vendors as $vendor)
            @php
              $vData = [
                'id' => $vendor->id,
                'code' => $vendor->code,
                'name' => $vendor->name,
                'payment_terms' => $vendor->payment_terms,
                'bank_name' => $vendor->bank_name,
                'bank_account_number' => $vendor->bank_account_number,
                'bank_account_name' => $vendor->bank_account_name,
                'contact_person' => $vendor->contact_person,
                'phone' => $vendor->phone,
                'email' => $vendor->email,
                'registered_address' => $vendor->registered_address,
                'status' => $vendor->status,
                'default_atc_code' => $vendor->default_atc_code,
              ];
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3 font-mono font-bold text-slate-900 dark:text-white">
                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700">
                  {{ $vendor->code }}
                </span>
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $vendor->name }}</div>
                <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-500">
                  <span>{{ $vendor->payment_terms ?? 'Net 30' }} credit terms</span>
                  @if($vendor->default_atc_code)
                    <span>•</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700" title="BIR ATC Code">
                      {{ $vendor->default_atc_code }}
                    </span>
                  @endif
                </div>
              </td>
              <td class="px-3 py-3.5 text-xs">
                @if($vendor->bank_name)
                  <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                    <i class="ph-bold ph-bank text-blue-600 text-xs"></i>
                    <span>{{ $vendor->bank_name }}</span>
                  </div>
                  <div class="font-mono text-[11px] text-slate-500">{{ $vendor->bank_account_number ?: 'No Account #' }}</div>
                @else
                  <span class="text-slate-400 text-xs">—</span>
                @endif
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400 max-w-[200px] truncate" title="{{ $vendor->registered_address }}">
                {{ $vendor->registered_address ?: '—' }}
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-700 dark:text-slate-300">
                {{ $vendor->contact_person ?: '—' }}
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                <div>{{ $vendor->phone ?: '—' }}</div>
                <div class="text-[11px] truncate max-w-[150px]">{{ $vendor->email ?: '—' }}</div>
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
                ₱{{ number_format((float) ($vendor->current_balance ?? 0), 2) }}
              </td>
              <td class="px-3 py-3.5 text-center">
                <x-status-badge :status="$vendor->status === 'Active' ? 'ACTIVE' : 'INACTIVE'" :variant="$vendor->status === 'Active' ? 'emerald' : 'slate'" />
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button 
                    type="button" 
                    @click="$dispatch('open-modal', { id: 'editVendorModal', vendor: {{ json_encode($vData) }} })"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white transition-colors"
                    title="Edit Vendor Master"
                  >
                    <i class="ph-bold ph-pencil-simple text-sm"></i>
                  </button>
                  <form action="{{ route('ap.vendors.toggle-status', $vendor->id) }}" method="POST" class="inline">
                    @csrf
                    <button 
                      type="submit" 
                      class="rounded-lg p-1.5 {{ $vendor->status === 'Active' ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                      title="{{ $vendor->status === 'Active' ? 'Deactivate Supplier' : 'Activate Supplier' }}"
                    >
                      <i class="ph-bold {{ $vendor->status === 'Active' ? 'ph-power' : 'ph-check' }} text-sm"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-buildings text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No vendors found matching query criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    @if(method_exists($vendors, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $vendors->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal: Add New Vendor (Alpine.js) -->
<x-modal 
  id="addVendorModal" 
  title="Register New Accredited Supplier" 
  subtitle="Masterfile profile, credit terms, and bank disbursement details" 
  icon="ph-buildings" 
  iconVariant="emerald" 
  size="2xl" 
  formAction="{{ route('ap.vendors.store') }}" 
  formMethod="POST" 
  submitText="Save Vendor Master"
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <!-- Section 1: Identification & Business Address -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-identification-card text-emerald-600"></i>
        <span>Supplier Legal Identity</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Vendor Code</label>
          <input type="text" name="code" value="{{ old('code') }}" placeholder="Auto-generated (e.g. VND-0024)" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Supplier Legal Name <span class="text-rose-500">*</span></label>
          <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Metro Pharma Medical Supplies Inc." required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Registered Business Address</label>
          <input type="text" name="registered_address" value="{{ old('registered_address') }}" placeholder="Physical business address / office location" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Credit Payment Terms</label>
          <input type="text" name="payment_terms" value="{{ old('payment_terms', 'Net 30') }}" placeholder="e.g. Net 30 Days" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- Section 2: Bank Disbursement Details -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-bank text-blue-600"></i>
        <span>Bank Disbursement &amp; EFT Details</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Settlement Bank Name</label>
          <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. BDO, BPI, Landbank" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Number</label>
          <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="e.g. 0012-3456-7890" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Holder Name</label>
          <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" placeholder="e.g. Metro Pharma Med Inc." class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- Section 3: Contact Representative -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-user text-teal-600"></i>
        <span>Contact Representative</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Contact Person</label>
          <input type="text" name="contact_person" value="{{ old('contact_person') }}" placeholder="e.g. Maria Santos" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Phone Number</label>
          <input type="text" name="phone" value="{{ old('phone') }}" placeholder="e.g. +63 (02) 8842-1090" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. billing@supplier.ph" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>
  </div>
</x-modal>

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'addVendorModal' }));
  });
</script>
@endif

<!-- Modal: Edit Vendor Master (Alpine.js) -->
<x-modal 
  id="editVendorModal" 
  title="Edit Vendor Master Record" 
  subtitle="Update accreditation parameters, credit terms, and banking details" 
  icon="ph-pencil-simple" 
  iconVariant="blue" 
  size="2xl" 
  formAction="#" 
  formMethod="POST" 
  submitText="Update Vendor Record"
  submitIcon="ph-check"
  submitVariant="blue"
>
  <div 
    x-data="{
      vendor: {},
      updateAction(v) {
        this.vendor = v || {};
        const form = document.querySelector('#editVendorModal form');
        if (form && this.vendor.id) {
          form.action = '{{ url('/accounts-payable/vendors') }}/' + this.vendor.id;
        }
      }
    }"
    @open-modal.window="if ($event.detail.id === 'editVendorModal') { updateAction($event.detail.vendor); }"
    class="space-y-4"
  >
    <input type="hidden" name="_method" value="PUT">

    <!-- Section 1: Identification & Business Address -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-identification-card text-blue-600"></i>
        <span>Supplier Legal Identity</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Vendor Code</label>
          <input type="text" name="code" x-model="vendor.code" readonly class="w-full rounded-xl border-0 bg-slate-100 py-2 px-3 text-xs font-mono font-bold text-slate-500 ring-1 ring-inset ring-slate-200 dark:bg-slate-800 dark:text-slate-400">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Supplier Name <span class="text-rose-500">*</span></label>
          <input type="text" name="name" x-model="vendor.name" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-8">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Registered Business Address</label>
          <input type="text" name="registered_address" x-model="vendor.registered_address" placeholder="Physical business address / office location" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Credit Payment Terms</label>
          <input type="text" name="payment_terms" x-model="vendor.payment_terms" placeholder="e.g. Net 30 Days" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- Section 2: Bank Disbursement Details -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-bank text-blue-600"></i>
        <span>Bank Disbursement &amp; EFT Details</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Settlement Bank Name</label>
          <input type="text" name="bank_name" x-model="vendor.bank_name" placeholder="e.g. BDO, BPI, Landbank" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Number</label>
          <input type="text" name="bank_account_number" x-model="vendor.bank_account_number" placeholder="e.g. 0012-3456-7890" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Holder Name</label>
          <input type="text" name="bank_account_name" x-model="vendor.bank_account_name" placeholder="e.g. Metro Pharma Med Inc." class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>

    <!-- Section 3: Contact Representative -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
        <i class="ph-bold ph-user text-teal-600"></i>
        <span>Contact Representative</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Contact Person</label>
          <input type="text" name="contact_person" x-model="vendor.contact_person" placeholder="e.g. Maria Santos" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Phone</label>
          <input type="text" name="phone" x-model="vendor.phone" placeholder="e.g. +63 (02) 8842-1090" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Email</label>
          <input type="email" name="email" x-model="vendor.email" placeholder="e.g. billing@supplier.ph" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>
    </div>
  </div>
</x-modal>
@endsection
