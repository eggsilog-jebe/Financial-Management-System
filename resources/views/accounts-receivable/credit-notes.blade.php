@extends('layouts.app')

@section('title', 'Credit Notes & Discounts - Accounts Receivable | FMS')
@section('module', 'ar')
@section('page', 'credit-notes')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Credit Notes &amp; Statutory Discounts
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'createCreditNoteModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Issue Credit Adjustment</span>
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

  <!-- Executive Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-stat-card 
      title="Total Approved &amp; Posted Adjustments" 
      :value="$totalCreditValue ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Cumulative credit notes posted to General Ledger"
    />
    <x-stat-card 
      title="Pending Management Approval" 
      :value="$totalPendingApproval ?? 0" 
      icon="ph-hourglass" 
      color="amber" 
      subtitle="Draft credit adjustments awaiting authorization"
    />
  </div>

  

  <!-- Credit Notes Data Table Card -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ar.credit-notes') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
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
            <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft (Pending Approval)</option>
            <option value="POSTED" {{ request('status') === 'POSTED' ? 'selected' : '' }}>Posted / Applied</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
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
            placeholder="Search CN #, invoice, patient..." 
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
            <th class="px-4 py-3.5">Credit Note #</th>
            <th class="px-4 py-3.5">Applied Invoice &amp; Patient</th>
            <th class="px-4 py-3.5">Issue Date</th>
            <th class="px-4 py-3.5">Adjustment Reason</th>
            <th class="px-4 py-3.5 text-right">Credit Amount</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5">Approved By</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($creditNotes as $cn)
          @php
            $amt = (float) $cn->amount;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-semibold text-purple-600 dark:text-purple-400">
              {{ $cn->credit_note_number }}
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">
                {{ $cn->patientAccount?->full_name ?? ($cn->invoice?->patientAccount?->full_name ?? 'Patient') }}
              </div>
              <div class="text-[11px] text-slate-500 font-mono dark:text-slate-400">
                Invoice: {{ $cn->invoice?->invoice_number ?? 'N/A' }}
              </div>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ $cn->issue_date ? $cn->issue_date->format('M d, Y') : '—' }}
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                {{ $cn->reason }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">
              ₱{{ number_format($amt, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              @if(in_array($cn->status, ['POSTED', 'APPLIED', 'APPROVED']))
                <x-status-badge status="active" label="{{ $cn->status }}" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $cn->status }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-[11px]">
              {{ $cn->approver?->name ?? '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              @if($cn->status === 'DRAFT')
                <form method="POST" action="{{ route('ar.credit-notes.approve', $cn->id) }}" onsubmit="return confirm('Authorize and post credit note {{ $cn->credit_note_number }} to General Ledger?');" class="inline">
                  @csrf
                  <button 
                    type="submit" 
                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
                    title="Finance Manager Approval"
                  >
                    <i class="ph-bold ph-stamp"></i>
                    <span>Approve &amp; Post</span>
                  </button>
                </form>
              @else
                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                  <i class="ph-bold ph-check-circle text-emerald-500"></i> Settled
                </span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-receipt-x text-3xl mb-2 text-slate-400"></i>
              <p>No credit notes found matching current filter.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $creditNotes->firstItem() ?? 0 }} - {{ $creditNotes->lastItem() ?? 0 }} of {{ $creditNotes->total() }} Records</span>
      <div>
        {{ $creditNotes->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal: Issue Credit Note -->
<x-modal 
  id="createCreditNoteModal" 
  title="Issue Credit Note & Statutory Discount" 
  subtitle="Apply Senior Citizen / PWD 20% discount, charity relief, or balance write-offs" 
  icon="ph-receipt-x" 
  iconVariant="emerald" 
  size="lg" 
  :scrollable="true" 
  :centered="true" 
  formAction="{{ route('ar.credit-notes.store') }}" 
  formId="creditNoteForm" 
  formMethod="POST" 
  submitId="submitCreditNoteBtn"
  submitText="Submit Credit Adjustment" 
  submitIcon="ph-check-circle"
>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-7">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" for="target_invoice_select">
          Target Open Invoice <span class="text-rose-500">*</span>
        </label>
        <select 
          name="invoice_id" 
          id="target_invoice_select" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          <option value="" data-gross="0" data-balance="0" data-has-statutory="0" data-statutory-type="" data-statutory-ref="">-- Choose Open Patient Invoice --</option>
          @foreach($openInvoices as $inv)
            @php
              $existingStatCN = $inv->creditNotes->whereIn('reason', ['SENIOR_CITIZEN_DISCOUNT', 'PWD_DISCOUNT', 'SENIOR_CITIZEN', 'PWD'])->whereIn('status', ['POSTED', 'APPLIED', 'DRAFT'])->first();
              $hasStatutory = $inv->statutoryDiscounts->isNotEmpty() || $existingStatCN !== null;
              $statutoryType = $existingStatCN ? $existingStatCN->reason : ($inv->statutoryDiscounts->isNotEmpty() ? $inv->statutoryDiscounts->first()->discount_type : '');
              $statutoryRef = $existingStatCN ? $existingStatCN->credit_note_number : ($inv->statutoryDiscounts->isNotEmpty() ? 'Intake RA 9994/10754' : '');
            @endphp
            <option value="{{ $inv->id }}" 
                    data-gross="{{ $inv->gross_total }}" 
                    data-balance="{{ $inv->patient_copay_balance }}"
                    data-has-statutory="{{ $hasStatutory ? '1' : '0' }}"
                    data-statutory-type="{{ $statutoryType }}"
                    data-statutory-ref="{{ $statutoryRef }}">
                {{ $inv->invoice_number }} — {{ $inv->patient_name }} (Open Copay: ₱{{ number_format((float) $inv->patient_copay_balance, 2) }}){{ $hasStatutory ? ' [Statutory Applied]' : '' }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="sm:col-span-5">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" for="credit_reason_select">
          Adjustment Reason / Type <span class="text-rose-500">*</span>
        </label>
        <select 
          name="reason" 
          id="credit_reason_select" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          <option value="SENIOR_CITIZEN_DISCOUNT">Statutory Senior Citizen Discount (20%)</option>
          <option value="PWD_DISCOUNT">Person with Disability (PWD) Discount (20%)</option>
          <option value="CHARITY_SUBSIDY">Medical Social Service Charity Subsidy</option>
          <option value="EMPLOYEE_SUBSIDY">Hospital Employee &amp; Dependent Subsidy</option>
          <option value="BILLING_ADJUSTMENT">Disputed Item / Procedure Cancellation</option>
        </select>
      </div>
    </div>

    <!-- Statutory Discount Alert Banner -->
    <div 
      class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900 ring-1 ring-amber-300/70 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800 flex items-start gap-2.5" 
      id="statutoryWarningAlert" 
      style="display: none;"
    >
      <i class="ph-bold ph-warning-circle text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
      <div>
        <strong>Statutory Discount Already Applied:</strong> A statutory discount is already recorded on this invoice (<span id="statutoryRefDisplay" class="font-mono font-bold"></span>). To re-apply statutory relief, void/reverse the existing credit note first. Non-statutory charity or billing adjustments are still allowed.
      </div>
    </div>

    <!-- Dynamic Discount Calculation Card -->
    <div 
      class="rounded-2xl bg-purple-50/60 p-4 ring-1 ring-purple-200/80 dark:bg-purple-950/20 dark:ring-purple-900/50" 
      id="discountCalculationCard" 
      style="display: none;"
    >
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
          <i class="ph-bold ph-calculator text-sm"></i> Dynamic Discount Calculation &amp; Balance Forecast
        </span>
        <span class="rounded-lg bg-purple-100 px-2 py-0.5 text-[11px] font-mono font-semibold text-purple-800 dark:bg-purple-900/60 dark:text-purple-200" id="invoiceTag">INV</span>
      </div>

      <div class="grid grid-cols-3 gap-2.5 mb-3 text-center">
        <div class="rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200/60 dark:bg-slate-900 dark:ring-slate-800">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-0.5">Gross Billed</span>
          <strong class="font-mono font-bold text-slate-900 dark:text-white tabular-nums text-xs" id="dispGross">₱0.00</strong>
        </div>
        <div class="rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200/60 dark:bg-slate-900 dark:ring-slate-800">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-0.5">Open Copay</span>
          <strong class="font-mono font-bold text-rose-600 dark:text-rose-400 tabular-nums text-xs" id="dispBalance">₱0.00</strong>
        </div>
        <div class="rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200/60 dark:bg-slate-900 dark:ring-slate-800" id="dispForecastCard">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-0.5">Forecasted Due</span>
          <strong class="font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums text-xs" id="dispRemaining">₱0.00</strong>
        </div>
      </div>

      <!-- Quick Auto-Fill Preset Buttons -->
      <div>
        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">Quick Auto-Fill Presets:</span>
        <div class="flex flex-wrap gap-2">
          <button 
            type="button" 
            class="inline-flex items-center gap-1 rounded-lg border border-purple-300 bg-white px-2.5 py-1 text-xs font-medium text-purple-700 hover:bg-purple-50 transition-colors dark:bg-slate-800 dark:border-purple-800 dark:text-purple-300 dark:hover:bg-purple-950/40 cursor-pointer" 
            id="btn-preset-statutory-20"
          >
            <i class="ph-bold ph-percent"></i> Apply 20% Statutory Discount
          </button>
          <button 
            type="button" 
            class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-white px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 transition-colors dark:bg-slate-800 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/40 cursor-pointer" 
            id="btnApply100Pct"
          >
            <i class="ph-bold ph-check-circle"></i> 100% Full Balance Write-Off
          </button>
          <button 
            type="button" 
            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 cursor-pointer" 
            id="btnApply50Pct"
          >
            <i class="ph-bold ph-divide"></i> 50% Charity Subsidy
          </button>
        </div>
      </div>
    </div>

    <!-- Input Fields -->
    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-7">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" for="credit_amount_input">
          Credit Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0.01" 
            name="amount" 
            id="credit_amount_input" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-rose-600 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400" 
            placeholder="0.00" 
            required
          >
        </div>
        <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400" id="amountFeedback">
          Credit amount cannot exceed target invoice open patient copay balance.
        </div>
      </div>

      <div class="sm:col-span-5">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" for="issue_date_input">
          Issue Date
        </label>
        <input 
          type="date" 
          name="issue_date" 
          id="issue_date_input" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}" 
          required
        >
      </div>
    </div>

    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
      <input type="hidden" name="save_as_draft" value="0">
      <input 
        type="checkbox" 
        id="save_as_draft" 
        name="save_as_draft" 
        value="1" 
        checked 
        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800"
      >
      <label class="text-xs text-slate-600 dark:text-slate-400" for="save_as_draft">
        Save as Draft for Management Approval (Uncheck for immediate posting if authorized)
      </label>
    </div>
  </div>
</x-modal>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const invoiceSelect = document.getElementById('target_invoice_select');
  const reasonSelect = document.getElementById('credit_reason_select');
  const amountInput = document.getElementById('credit_amount_input') || document.getElementById('credit_amount');
  const calcCard = document.getElementById('discountCalculationCard');
  const dispGross = document.getElementById('dispGross');
  const dispBalance = document.getElementById('dispBalance');
  const dispRemaining = document.getElementById('dispRemaining');
  const invoiceTag = document.getElementById('invoiceTag');
  const amountFeedback = document.getElementById('amountFeedback');
  const submitBtn = document.getElementById('submitCreditNoteBtn');
  const statutoryWarningAlert = document.getElementById('statutoryWarningAlert');
  const statutoryRefDisplay = document.getElementById('statutoryRefDisplay');

  const btnApply20Pct = document.getElementById('btn-preset-statutory-20') || document.getElementById('btnApply20Pct');
  const btnApply100Pct = document.getElementById('btnApply100Pct');
  const btnApply50Pct = document.getElementById('btnApply50Pct');

  function getSelectedInvoiceData() {
    const selectedOption = invoiceSelect.options[invoiceSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
      return null;
    }

    const gross = parseFloat(selectedOption.getAttribute('data-gross')) || 0;
    const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
    const hasStatutory = selectedOption.getAttribute('data-has-statutory') === '1';
    const statutoryType = selectedOption.getAttribute('data-statutory-type') || '';
    const statutoryRef = selectedOption.getAttribute('data-statutory-ref') || '';
    const label = selectedOption.textContent.split('—')[0].trim();

    return { gross, balance, hasStatutory, statutoryType, statutoryRef, label };
  }

  function syncStatutoryOptions(hasStatutory, statutoryType, statutoryRef) {
    const optSenior = reasonSelect.querySelector('option[value="SENIOR_CITIZEN_DISCOUNT"]');
    const optPwd = reasonSelect.querySelector('option[value="PWD_DISCOUNT"]');

    if (hasStatutory) {
      if (statutoryWarningAlert) {
        statutoryWarningAlert.style.display = 'flex';
        if (statutoryRefDisplay) {
          statutoryRefDisplay.textContent = statutoryRef || 'Active Record';
        }
      }

      const refText = statutoryRef ? `Currently Applied: ${statutoryRef}` : 'Currently Applied';

      if (statutoryType === 'PWD_DISCOUNT' || statutoryType === 'PWD') {
        if (optPwd) {
          optPwd.disabled = true;
          optPwd.textContent = `Person with Disability (PWD) Discount (20%) — (${refText})`;
        }
        if (optSenior) {
          optSenior.disabled = true;
          optSenior.textContent = 'Statutory Senior Citizen Discount (20%) — (Disabled: 1 Statutory Discount Limit)';
        }
      } else {
        if (optSenior) {
          optSenior.disabled = true;
          optSenior.textContent = `Statutory Senior Citizen Discount (20%) — (${refText})`;
        }
        if (optPwd) {
          optPwd.disabled = true;
          optPwd.textContent = 'Person with Disability (PWD) Discount (20%) — (Disabled: 1 Statutory Discount Limit)';
        }
      }

      if (btnApply20Pct) {
        btnApply20Pct.disabled = true;
        btnApply20Pct.classList.add('opacity-50', 'cursor-not-allowed');
      }

      if (reasonSelect.value === 'SENIOR_CITIZEN_DISCOUNT' || reasonSelect.value === 'PWD_DISCOUNT') {
        reasonSelect.value = 'CHARITY_SUBSIDY';
      }
    } else {
      if (statutoryWarningAlert) statutoryWarningAlert.style.display = 'none';
      if (optSenior) {
        optSenior.disabled = false;
        optSenior.textContent = 'Statutory Senior Citizen Discount (20%)';
      }
      if (optPwd) {
        optPwd.disabled = false;
        optPwd.textContent = 'Person with Disability (PWD) Discount (20%)';
      }
      if (btnApply20Pct) {
        btnApply20Pct.disabled = false;
        btnApply20Pct.classList.remove('opacity-50', 'cursor-not-allowed');
      }
    }
  }

  function updateCalculationCard() {
    const data = getSelectedInvoiceData();
    if (!data) {
      calcCard.style.display = 'none';
      if (statutoryWarningAlert) statutoryWarningAlert.style.display = 'none';
      dispGross.textContent = '₱0.00';
      dispBalance.textContent = '₱0.00';
      dispRemaining.textContent = '₱0.00';
      return;
    }

    syncStatutoryOptions(data.hasStatutory, data.statutoryType, data.statutoryRef);
    calcCard.style.display = 'block';
    invoiceTag.textContent = data.label;
    dispGross.textContent = '₱' + data.gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    dispBalance.textContent = '₱' + data.balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const enteredAmount = parseFloat(amountInput.value) || 0;
    const remaining = Math.max(0, data.balance - enteredAmount);
    dispRemaining.textContent = '₱' + remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (enteredAmount > data.balance + 0.0001) {
      amountInput.classList.add('border-rose-500', 'text-rose-600');
      amountFeedback.className = 'mt-1 text-[11px] text-rose-600 font-bold';
      amountFeedback.textContent = '⚠️ Credit amount (₱' + enteredAmount.toFixed(2) + ') exceeds open copay balance (₱' + data.balance.toFixed(2) + ').';
      if (submitBtn) submitBtn.disabled = true;
    } else {
      amountInput.classList.remove('border-rose-500');
      amountFeedback.className = 'mt-1 text-[11px] text-slate-500 dark:text-slate-400';
      amountFeedback.textContent = 'Amount will reduce patient copay from ₱' + data.balance.toFixed(2) + ' to ₱' + remaining.toFixed(2) + '.';
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  function applyStatutory20Preset(grossAmount, balanceAmount) {
    const vatExemptBase = grossAmount / 1.12;
    const vatRelief = grossAmount - vatExemptBase;
    const statutoryDiscount = vatExemptBase * 0.20;
    const totalStatutoryCredit = vatRelief + statutoryDiscount;

    const creditAmountInput = document.getElementById('credit_amount_input') || document.getElementById('credit_amount');
    if (creditAmountInput) {
      const finalCredit = (typeof balanceAmount === 'number' && balanceAmount > 0)
        ? Math.min(balanceAmount, parseFloat(totalStatutoryCredit.toFixed(2)))
        : parseFloat(totalStatutoryCredit.toFixed(2));
      creditAmountInput.value = finalCredit.toFixed(2);
    }

    updateCalculationCard();
  }

  function autoFillDiscount(reason) {
    const data = getSelectedInvoiceData();
    if (!data) return;

    if (reason === 'SENIOR_CITIZEN_DISCOUNT' || reason === 'PWD_DISCOUNT') {
      if (data.hasStatutory) return;
      const baseGross = data.gross > 0 ? data.gross : data.balance;
      applyStatutory20Preset(baseGross, data.balance);
    } else if (reason === 'CHARITY_SUBSIDY') {
      amountInput.value = data.balance.toFixed(2);
      updateCalculationCard();
    } else if (reason === 'EMPLOYEE_SUBSIDY') {
      const discount = Math.min(data.balance, parseFloat((data.gross * 0.20).toFixed(2)));
      amountInput.value = discount.toFixed(2);
      updateCalculationCard();
    }
  }

  invoiceSelect.addEventListener('change', function () {
    const data = getSelectedInvoiceData();
    if (data) {
      syncStatutoryOptions(data.hasStatutory, data.statutoryType, data.statutoryRef);
      autoFillDiscount(reasonSelect.value);
    }
    updateCalculationCard();
  });

  reasonSelect.addEventListener('change', function () {
    autoFillDiscount(this.value);
  });

  amountInput.addEventListener('input', function () {
    updateCalculationCard();
  });

  if (btnApply20Pct) {
    btnApply20Pct.addEventListener('click', function () {
      const data = getSelectedInvoiceData();
      if (!data || data.hasStatutory) return;
      const baseGross = data.gross > 0 ? data.gross : data.balance;
      applyStatutory20Preset(baseGross, data.balance);
    });
  }

  if (btnApply100Pct) {
    btnApply100Pct.addEventListener('click', function () {
      const data = getSelectedInvoiceData();
      if (!data) return;
      amountInput.value = data.balance.toFixed(2);
      updateCalculationCard();
    });
  }

  if (btnApply50Pct) {
    btnApply50Pct.addEventListener('click', function () {
      const data = getSelectedInvoiceData();
      if (!data) return;
      amountInput.value = (data.balance * 0.50).toFixed(2);
      updateCalculationCard();
    });
  }
});
</script>
@endpush
@endsection
