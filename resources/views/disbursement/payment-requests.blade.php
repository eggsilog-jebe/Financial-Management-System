@extends('layouts.app')

@section('title', 'Disbursement Vouchers & Requests - Disbursement | FMS')
@section('module', 'disbursement')
@section('page', 'payment-requests')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Disbursement Vouchers &amp; Payment Requisitions
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        id="btnEncodePayroll"
        @click="$dispatch('open-modal', 'encodePayrollModal')"
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-users-three text-emerald-600"></i>
        <span>Encode Payroll Cutoff</span>
      </button>
      <button 
        type="button" 
        id="btnCreateRequest"
        @click="$dispatch('open-modal', 'createRequestModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>New Payment Request</span>
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
    <div class="rounded-xl bg-rose-50 p-4 text-xs text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <div class="flex items-start gap-2">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600 mt-0.5 flex-shrink-0"></i>
        <div>
          <strong class="block font-semibold mb-1">Validation Errors Encountered:</strong>
          <ul class="list-disc list-inside space-y-0.5 text-xs">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total Requisitions" 
      :value="$totalRequests ?? 0" 
      :isCurrency="false"
      icon="ph-file-text" 
      color="slate" 
      subtitle="Cumulative disbursement vouchers"
    />
    <x-stat-card 
      title="Pending Audit / Approval" 
      :value="$pendingApproval ?? 0" 
      icon="ph-clock" 
      color="amber" 
      subtitle="Awaiting internal audit or release"
    />
    <x-stat-card 
      title="Approved for Release" 
      :value="$approvedAmount ?? 0" 
      icon="ph-stamp" 
      color="blue" 
      subtitle="Authorized for check / EFT release"
    />
    <x-stat-card 
      title="Total Released Payments" 
      :value="$totalReleased ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Fully cleared bank disbursements"
    />
  </div>


  <!-- Requisitions Data Table Card -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('disbursement.payment-requests') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            onchange="this.form.submit()"
          >
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="PREPARED" {{ request('status') === 'PREPARED' ? 'selected' : '' }}>Prepared (Pending Audit)</option>
            <option value="AUDITED" {{ request('status') === 'AUDITED' ? 'selected' : '' }}>Audited (Ready for Approval)</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved (Ready for Release)</option>
            <option value="RELEASED" {{ request('status') === 'RELEASED' ? 'selected' : '' }}>Released / Disbursed</option>
            <option value="VOIDED" {{ request('status') === 'VOIDED' ? 'selected' : '' }}>Voided</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search voucher #, payee, desc..." 
            value="{{ request('search') }}"
          >
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Voucher Ref #</th>
            <th class="px-4 py-3.5">Payee &amp; Particulars</th>
            <th class="px-4 py-3.5">Bank Account</th>
            <th class="px-4 py-3.5">Payment Method</th>
            <th class="px-4 py-3.5">Voucher Date</th>
            <th class="px-4 py-3.5 text-right">Amount (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5">Preparer</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($vouchers as $v)
          @php
            $amt = (float) $v->net_disbursed_amount;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3">
              <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $v->voucher_number }}</span>
              @if($v->check_or_eft_ref)
                <div class="text-[11px] text-slate-400 font-mono">Ref: {{ $v->check_or_eft_ref }}</div>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $v->payee_name }}</div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ $v->description ?? ($v->purchaseBill ? "Bill {$v->purchaseBill->bill_number}" : 'Departmental Requisition') }}
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white">{{ $v->bankAccount?->bank_name ?? 'Operating Bank' }}</div>
              <div class="text-[11px] text-slate-400 font-mono">{{ $v->bankAccount?->account_number ?? 'Acc' }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-mono font-medium bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                {{ str_replace('_', ' ', $v->payment_method) }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ $v->voucher_date ? $v->voucher_date->format('M d, Y') : '—' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
              ₱{{ number_format($amt, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              @if($v->status === 'RELEASED')
                <x-status-badge status="active" label="RELEASED" size="sm" />
              @elseif($v->status === 'APPROVED')
                <x-status-badge status="blue" label="APPROVED" size="sm" />
              @elseif($v->status === 'AUDITED')
                <x-status-badge status="indigo" label="AUDITED" size="sm" />
              @elseif($v->status === 'VOIDED')
                <x-status-badge status="inactive" label="VOIDED" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $v->status }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-[11px]">
              {{ $v->preparer?->name ?? 'Staff' }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                @if($v->status === 'PREPARED' || $v->status === 'DRAFT')
                  <form method="POST" action="{{ route('disbursement.payment-requests.audit', $v->id) }}" onsubmit="return confirm('Audit and verify voucher {{ $v->voucher_number }}?');" class="inline">
                    @csrf
                    <button 
                      type="submit" 
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 cursor-pointer" 
                      title="Internal Audit Verification"
                    >
                      <i class="ph-bold ph-magnifying-glass text-emerald-600"></i>
                      <span>Audit</span>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('disbursement.payment-requests.void', $v->id) }}" onsubmit="return confirm('Void voucher {{ $v->voucher_number }}?');" class="inline">
                    @csrf
                    <button 
                      type="submit" 
                      class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-800 dark:text-rose-400 dark:hover:bg-rose-950/30 cursor-pointer" 
                      title="Void Request"
                    >
                      <i class="ph-bold ph-x"></i>
                    </button>
                  </form>
                @elseif($v->status === 'AUDITED' || $v->status === 'APPROVED')
                  <a 
                    href="{{ route('disbursement.disbursement-approval') }}" 
                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
                  >
                    <i class="ph-bold ph-shield-check"></i>
                    <span>Workstation</span>
                  </a>
                @else
                  <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                    <i class="ph-bold ph-check-circle text-emerald-500"></i> Disbursed
                  </span>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-receipt text-3xl mb-2 text-slate-400"></i>
              <p>No disbursement vouchers found matching current filter.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $vouchers->firstItem() ?? 0 }} - {{ $vouchers->lastItem() ?? 0 }} of {{ $vouchers->total() }} Requisitions</span>
      <div>
        {{ $vouchers->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal: Create Payment Request -->
<x-modal 
    id="createRequestModal" 
    title="Create Payment Request / Voucher"
    subtitle="Requisition payment for operating expenses, professional honorariums, or supplier bills."
    icon="ph-receipt"
    iconVariant="emerald"
    size="lg"
    :scrollable="true"
    :centered="true"
    formAction="{{ route('disbursement.payment-requests.store') }}"
    formMethod="POST"
    submitText="Submit Payment Voucher"
    submitIcon="ph-check"
>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Disbursing Bank Account <span class="text-rose-500">*</span>
        </label>
        <select 
          name="bank_account_id" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          <option value="">-- Choose Operating Bank --</option>
          @foreach($bankAccounts as $b)
            <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number }}) - Bal: ₱{{ number_format((float) $b->balance, 2) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Payment Method <span class="text-rose-500">*</span>
        </label>
        <select 
          name="payment_method" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          <option value="CHECK" selected>Bank Check (Standard)</option>
          <option value="PESONET_EFT">PESONet Electronic Bank Transfer</option>
          <option value="INSTAPAY">InstaPay Real-Time Transfer</option>
          <option value="TELEGRAPHIC_TRANSFER">Telegraphic Transfer (TT / Wire)</option>
          <option value="PETTY_CASH">Petty Cash Voucher</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Payee Legal Name <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          name="payee_name" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. Metro Medical Supplies Inc" 
          required
        >
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Voucher Date <span class="text-rose-500">*</span>
        </label>
        <input 
          type="date" 
          name="voucher_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}" 
          required
        >
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Gross Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            name="gross_amount" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            placeholder="0.00" 
            required
          >
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Withheld Tax Amount (EWT / 1601-C)
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            name="withheld_tax_amount" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            placeholder="0.00" 
            value="0.00"
          >
        </div>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Particulars / Payment Purpose
      </label>
      <input 
        type="text" 
        name="description" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Biomedical equipment quarterly maintenance payment"
      >
    </div>

    <!-- Link to AP Purchase Bill or Payroll Run -->
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
        <i class="ph-bold ph-link text-emerald-600"></i> Link to AP Bill or Payroll (Optional)
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Link Purchase Bill</label>
          <select 
            name="purchase_bill_id" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <option value="">-- None (Manual Request) --</option>
            @foreach($openBills as $ob)
              <option value="{{ $ob->id }}">{{ $ob->bill_number }} - {{ $ob->vendor?->name }} (Due: ₱{{ number_format((float) $ob->balance_due, 2) }})</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Link Payroll Run</label>
          <select 
            name="payroll_run_id" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <option value="">-- None --</option>
            @foreach($openPayrolls as $pr)
              <option value="{{ $pr->id }}">{{ $pr->payroll_run_number }} (Net Pay: ₱{{ number_format((float) $pr->total_net_pay, 2) }})</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Encode Payroll Cutoff Run -->
<x-modal 
    id="encodePayrollModal" 
    title="Encode Employee Payroll Cutoff Run"
    subtitle="Hospital compensation entry with automated statutory deductions (SSS, PhilHealth, Pag-IBIG, 1601-C) & PFRS GL posting."
    icon="ph-users-three"
    iconVariant="emerald"
    size="xl"
    :scrollable="true"
    :centered="true"
    formAction="{{ route('disbursement.payroll.store') }}"
    formId="formEncodePayroll"
    formMethod="POST"
    submitText="Post &amp; Disburse Payroll Run"
    submitIcon="ph-check-circle"
>
  <div class="space-y-4">
    <!-- Cutoff Period & Disbursing Bank -->
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
      <div class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5 pb-2 border-b border-slate-200 dark:border-slate-700">
        <i class="ph-bold ph-calendar-check text-emerald-600"></i> Payroll Cutoff Schedule &amp; Disbursing Bank
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Cutoff Start <span class="text-rose-500">*</span></label>
          <input 
            type="date" 
            name="cutoff_start" 
            id="payrollCutoffStart" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            value="{{ old('cutoff_start', date('Y-m-01')) }}" 
            required
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Cutoff End <span class="text-rose-500">*</span></label>
          <input 
            type="date" 
            name="cutoff_end" 
            id="payrollCutoffEnd" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            value="{{ old('cutoff_end', date('Y-m-15')) }}" 
            required
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Payout Date <span class="text-rose-500">*</span></label>
          <input 
            type="date" 
            name="payout_date" 
            id="payrollPayoutDate" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            value="{{ old('payout_date', date('Y-m-15')) }}" 
            required
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Disbursing Bank Account <span class="text-rose-500">*</span></label>
          <select 
            name="disbursement_bank_account_id" 
            id="payrollBankSelect" 
            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            required 
            onchange="updatePayrollBankBalance()"
          >
            <option value="">-- Choose Bank Account --</option>
            @foreach($bankAccounts as $b)
              <option value="{{ $b->id }}" data-balance="{{ $b->balance }}" {{ old('disbursement_bank_account_id') == $b->id ? 'selected' : ($loop->first ? 'selected' : '') }}>
                {{ $b->bank_name }} ({{ $b->account_number }}) - ₱{{ number_format((float) $b->balance, 2) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <!-- Statutory Deduction Notice -->
    <div class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900 ring-1 ring-blue-300/70 dark:bg-blue-950/40 dark:text-blue-200 dark:ring-blue-800 flex items-start gap-2.5">
      <i class="ph-bold ph-info text-blue-600 text-lg flex-shrink-0 mt-0.5"></i>
      <div>
        <strong>Automated Double-Entry Posting:</strong> Submitting this run automatically calculates Philippine statutory contributions (SSS EE/ER, PhilHealth EE/ER, Pag-IBIG EE/ER), BIR Form 1601-C withholding tax, and posts balanced journal lines (Salaries Expense <code class="font-mono bg-blue-100 dark:bg-blue-900/60 px-1 py-0.5 rounded">6010</code>, Statutory Payables <code class="font-mono bg-blue-100 dark:bg-blue-900/60 px-1 py-0.5 rounded">2120-2150</code>, Cash in Bank <code class="font-mono bg-blue-100 dark:bg-blue-900/60 px-1 py-0.5 rounded">1020</code>).
      </div>
    </div>

    <!-- Employees Table Section -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
          <i class="ph-bold ph-user-list text-emerald-600"></i> Personnel Compensation Roster
        </h3>
        <div class="flex items-center gap-2">
          <button 
            type="button" 
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer" 
            onclick="loadHospitalSampleRoster()"
          >
            <i class="ph-bold ph-arrows-clockwise text-emerald-600"></i> Reset Sample Roster
          </button>
          <button 
            type="button" 
            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 cursor-pointer" 
            onclick="addPayrollEmployeeRow()"
          >
            <i class="ph-bold ph-user-plus"></i> Add Personnel Line
          </button>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800" style="max-height: 380px;">
        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300" id="payrollEmployeesTable">
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 sticky top-0 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
            <tr>
              <th class="px-2.5 py-2.5 text-center w-10">#</th>
              <th class="px-2.5 py-2.5 min-w-[130px]">Employee ID <span class="text-rose-500">*</span></th>
              <th class="px-2.5 py-2.5 min-w-[170px]">Employee Full Name <span class="text-rose-500">*</span></th>
              <th class="px-2.5 py-2.5 min-w-[150px]">Department <span class="text-rose-500">*</span></th>
              <th class="px-2.5 py-2.5 min-w-[110px] text-right">Basic (₱) <span class="text-rose-500">*</span></th>
              <th class="px-2.5 py-2.5 min-w-[90px] text-right">Overtime</th>
              <th class="px-2.5 py-2.5 min-w-[90px] text-right">Allowances</th>
              <th class="px-2.5 py-2.5 min-w-[110px] text-right">Est. Gross (₱)</th>
              <th class="px-2.5 py-2.5 min-w-[120px]">Bank / Ref</th>
              <th class="px-2.5 py-2.5 text-center w-12">Action</th>
            </tr>
          </thead>
          <tbody id="payrollEmployeesBody" class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <!-- Dynamically populated via JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payroll Run Financial Summary -->
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center sm:text-left">
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-0.5">Personnel Count</span>
          <span class="font-mono font-bold text-slate-900 dark:text-white text-base" id="displayPersonnelCount">0</span>
          <span class="text-xs text-slate-400 ml-1">staff</span>
        </div>
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-0.5">Total Basic Salary</span>
          <span class="font-mono font-bold text-slate-900 dark:text-white text-base tabular-nums" id="displayTotalBasic">₱0.00</span>
        </div>
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-0.5">Total Gross Comp.</span>
          <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base tabular-nums" id="displayTotalGross">₱0.00</span>
        </div>
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-0.5">Bank Available Bal.</span>
          <span class="font-mono font-bold text-slate-900 dark:text-white text-base tabular-nums" id="displayBankBalance">₱0.00</span>
        </div>
      </div>
      <div id="payrollBalanceWarning" class="rounded-lg bg-rose-50 p-2.5 text-xs text-rose-800 ring-1 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-300 mt-3 hidden">
        <i class="ph-bold ph-warning-circle text-rose-600 mr-1"></i> <strong>Warning:</strong> Total gross payroll exceeds the available balance of the selected bank account!
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
const standardHospitalStaff = [
  {
    id: 'EMP-2026-001',
    name: 'Dr. Roberto Mendoza, MD',
    dept: 'Medical / Physicians',
    basic: 65000.00,
    ot: 5000.00,
    allowances: 10000.00,
    bank: 'BDO-001293847'
  },
  {
    id: 'EMP-2026-002',
    name: 'Ma. Elena Reyes, RN',
    dept: 'Nursing',
    basic: 32000.00,
    ot: 3500.00,
    allowances: 2000.00,
    bank: 'BPI-992384712'
  },
  {
    id: 'EMP-2026-003',
    name: 'Juan Carlos Santos, RPh',
    dept: 'Pharmacy',
    basic: 28000.00,
    ot: 1200.00,
    allowances: 1500.00,
    bank: 'MBTC-382910482'
  }
];

const hospitalDepts = [
  'Medical / Physicians',
  'Nursing',
  'Pharmacy',
  'Laboratory',
  'Radiology & Imaging',
  'Emergency Medicine',
  'Administration & Finance',
  'Dietary & Nutrition',
  'Biomedical Engineering'
];

function buildDeptOptions(selectedDept = '') {
  return hospitalDepts.map(dept => {
    const isSelected = (dept === selectedDept) ? 'selected' : '';
    return `<option value="${dept}" ${isSelected}>${dept}</option>`;
  }).join('');
}

function addPayrollEmployeeRow(data = null) {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (!tbody) return;

  const rowIndex = tbody.children.length;
  const d = data || {
    id: `EMP-${String(rowIndex + 1).padStart(3, '0')}`,
    name: '',
    dept: 'Nursing',
    basic: 25000.00,
    ot: 0.00,
    allowances: 0.00,
    bank: ''
  };

  const tr = document.createElement('tr');
  tr.className = 'payroll-emp-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors';
  tr.innerHTML = `
    <td class="text-center text-slate-400 font-mono text-[11px] row-num py-2 px-2">${rowIndex + 1}</td>
    <td class="p-1.5">
      <input type="text" name="employees[${rowIndex}][employee_id_number]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-id-input" value="${d.id}" placeholder="EMP-001" required>
    </td>
    <td class="p-1.5">
      <input type="text" name="employees[${rowIndex}][employee_name]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-name-input" value="${d.name}" placeholder="Full Name" required>
    </td>
    <td class="p-1.5">
      <select name="employees[${rowIndex}][department]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-dept-input" required>
        ${buildDeptOptions(d.dept)}
      </select>
    </td>
    <td class="p-1.5">
      <input type="number" step="0.01" min="0" name="employees[${rowIndex}][basic_salary]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-mono text-right font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-basic-input" value="${Number(d.basic).toFixed(2)}" required oninput="calcPayrollTotals()">
    </td>
    <td class="p-1.5">
      <input type="number" step="0.01" min="0" name="employees[${rowIndex}][overtime_pay]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-mono text-right font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-ot-input" value="${Number(d.ot || 0).toFixed(2)}" oninput="calcPayrollTotals()">
    </td>
    <td class="p-1.5">
      <input type="number" step="0.01" min="0" name="employees[${rowIndex}][allowances]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-mono text-right font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-allowance-input" value="${Number(d.allowances || 0).toFixed(2)}" oninput="calcPayrollTotals()">
    </td>
    <td class="text-right font-mono font-bold text-xs tabular-nums text-slate-900 dark:text-white px-2 emp-gross-cell">
      ₱0.00
    </td>
    <td class="p-1.5">
      <input type="text" name="employees[${rowIndex}][bank_account_number]" class="w-full rounded-lg border-slate-200 bg-white py-1 px-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 emp-bank-input" value="${d.bank || ''}" placeholder="Account / Ref">
    </td>
    <td class="text-center p-1.5">
      <button type="button" class="text-rose-500 hover:text-rose-700 transition-colors cursor-pointer" onclick="removePayrollEmployeeRow(this)" title="Remove line">
        <i class="ph-bold ph-trash text-base"></i>
      </button>
    </td>
  `;

  tbody.appendChild(tr);
  reindexPayrollRows();
  calcPayrollTotals();
}

function removePayrollEmployeeRow(btn) {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (!tbody) return;
  if (tbody.children.length <= 1) {
    alert('At least one employee line item is required for a payroll run.');
    return;
  }
  const tr = btn.closest('tr');
  tr.remove();
  reindexPayrollRows();
  calcPayrollTotals();
}

function reindexPayrollRows() {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr.payroll-emp-row');
  rows.forEach((row, idx) => {
    const numCell = row.querySelector('.row-num');
    if (numCell) numCell.textContent = idx + 1;

    const idInput = row.querySelector('.emp-id-input');
    if (idInput) idInput.name = `employees[${idx}][employee_id_number]`;

    const nameInput = row.querySelector('.emp-name-input');
    if (nameInput) nameInput.name = `employees[${idx}][employee_name]`;

    const deptInput = row.querySelector('.emp-dept-input');
    if (deptInput) deptInput.name = `employees[${idx}][department]`;

    const basicInput = row.querySelector('.emp-basic-input');
    if (basicInput) basicInput.name = `employees[${idx}][basic_salary]`;

    const otInput = row.querySelector('.emp-ot-input');
    if (otInput) otInput.name = `employees[${idx}][overtime_pay]`;

    const allowInput = row.querySelector('.emp-allowance-input');
    if (allowInput) allowInput.name = `employees[${idx}][allowances]`;

    const bankInput = row.querySelector('.emp-bank-input');
    if (bankInput) bankInput.name = `employees[${idx}][bank_account_number]`;
  });
}

function calcPayrollTotals() {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr.payroll-emp-row');
  let totalBasic = 0;
  let totalGross = 0;

  rows.forEach(row => {
    const basic = parseFloat(row.querySelector('.emp-basic-input')?.value) || 0;
    const ot = parseFloat(row.querySelector('.emp-ot-input')?.value) || 0;
    const allow = parseFloat(row.querySelector('.emp-allowance-input')?.value) || 0;
    const gross = basic + ot + allow;

    const grossCell = row.querySelector('.emp-gross-cell');
    if (grossCell) {
      grossCell.textContent = '₱' + gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    totalBasic += basic;
    totalGross += gross;
  });

  const countDisplay = document.getElementById('displayPersonnelCount');
  if (countDisplay) countDisplay.textContent = rows.length;

  const basicDisplay = document.getElementById('displayTotalBasic');
  if (basicDisplay) {
    basicDisplay.textContent = '₱' + totalBasic.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  const grossDisplay = document.getElementById('displayTotalGross');
  if (grossDisplay) {
    grossDisplay.textContent = '₱' + totalGross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  updatePayrollBankBalance(totalGross);
}

function updatePayrollBankBalance(totalGross = null) {
  const bankSelect = document.getElementById('payrollBankSelect');
  const bankDisplay = document.getElementById('displayBankBalance');
  const warning = document.getElementById('payrollBalanceWarning');

  if (!bankSelect || !bankDisplay) return;

  const opt = bankSelect.options[bankSelect.selectedIndex];
  const balance = opt ? (parseFloat(opt.getAttribute('data-balance')) || 0) : 0;

  bankDisplay.textContent = '₱' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  if (totalGross === null) {
    const grossText = document.getElementById('displayTotalGross')?.textContent || '0';
    totalGross = parseFloat(grossText.replace(/[^0-9.-]+/g, '')) || 0;
  }

  if (warning) {
    if (balance > 0 && totalGross > balance) {
      warning.classList.remove('hidden');
    } else {
      warning.classList.add('hidden');
    }
  }
}

function loadHospitalSampleRoster() {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  standardHospitalStaff.forEach(staff => addPayrollEmployeeRow(staff));
}

document.addEventListener('DOMContentLoaded', function () {
  const tbody = document.getElementById('payrollEmployeesBody');
  if (tbody && tbody.children.length === 0) {
    loadHospitalSampleRoster();
  }
  updatePayrollBankBalance();
});
</script>
@endpush
