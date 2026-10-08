@extends('layouts.app')

@section('title', 'Payment Receipts - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'payment-receipts')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Payment Receipts &amp; Official Receipts (OR)
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <a 
        href="{{ route('collection.cashier-desk') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
      >
        <i class="ph-bold ph-hand-coins text-emerald-600"></i>
        <span>Cashier POS Desk</span>
      </a>
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

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total Collections" 
      :value="$totalCollected ?? 0" 
      icon="ph-receipt" 
      color="emerald" 
      subtitle="Cumulative received tender"
    />
    <x-stat-card 
      title="Cash Collections" 
      :value="$cashCollected ?? 0" 
      icon="ph-money" 
      color="slate" 
      subtitle="Physical tender across cashier desks"
    />
    <x-stat-card 
      title="Digital / E-Wallet" 
      :value="$digitalCollected ?? 0" 
      icon="ph-credit-card" 
      color="blue" 
      subtitle="GCash, Maya, cards & bank"
    />
    <x-stat-card 
      title="Receipts Issued" 
      :value="$payments->total() ?? count($payments)" 
      :isCurrency="false"
      icon="ph-files" 
      color="amber" 
      subtitle="Official BIR receipt entries"
    />
  </div>

  

  <!-- Data Table Card -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('collection.receipts') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
        <div class="sm:col-span-4 relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            value="{{ request('search') }}" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search OR #, patient, payor..."
          >
        </div>

        <div class="sm:col-span-2">
          <select 
            name="method" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Payment Methods</option>
            <option value="CASH" {{ request('method') === 'CASH' ? 'selected' : '' }}>CASH</option>
            <option value="GCASH" {{ request('method') === 'GCASH' ? 'selected' : '' }}>GCASH</option>
            <option value="MAYA" {{ request('method') === 'MAYA' ? 'selected' : '' }}>MAYA</option>
            <option value="CREDIT_CARD" {{ request('method') === 'CREDIT_CARD' ? 'selected' : '' }}>CREDIT CARD</option>
            <option value="DEBIT_CARD" {{ request('method') === 'DEBIT_CARD' ? 'selected' : '' }}>DEBIT CARD</option>
            <option value="CHECK" {{ request('method') === 'CHECK' ? 'selected' : '' }}>CHECK</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <input 
            type="date" 
            name="date_from" 
            value="{{ request('date_from') }}" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <div class="sm:col-span-2">
          <input 
            type="date" 
            name="date_to" 
            value="{{ request('date_to') }}" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <div class="sm:col-span-2 flex items-center gap-2">
          <button 
            type="submit" 
            class="w-full inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
          >
            <i class="ph-bold ph-funnel"></i> Filter
          </button>
          <a 
            href="{{ route('collection.receipts') }}" 
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
          >
            <i class="ph-bold ph-arrow-counter-clockwise"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Official Receipt #</th>
            <th class="px-4 py-3.5">Date</th>
            <th class="px-4 py-3.5">Patient / Payor</th>
            <th class="px-4 py-3.5">Invoice Number</th>
            <th class="px-4 py-3.5">Payment Method</th>
            <th class="px-4 py-3.5">Shift / Terminal</th>
            <th class="px-4 py-3.5">General Ledger</th>
            <th class="px-4 py-3.5 text-right">Amount Paid (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($payments as $pay)
          @php
            $orNo = $pay->officialReceipt?->or_number ?? $pay->payment_reference;
            $isCancelled = ($pay->officialReceipt?->status === 'CANCELLED');
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $isCancelled ? 'opacity-60 bg-slate-50/40 dark:bg-slate-800/20' : '' }}">
            <td class="px-4 py-3">
              <span class="font-mono font-bold {{ $isCancelled ? 'line-through text-rose-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ $orNo }}
              </span>
              <div class="text-[11px] text-slate-400 font-mono">{{ $pay->payment_reference }}</div>
            </td>
            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">
              {{ $pay->payment_date ? $pay->payment_date->format('M d, Y') : '-' }}
            </td>
            <td class="px-4 py-3">
              <strong class="block text-slate-900 dark:text-white">{{ $pay->officialReceipt?->payor_name ?: ($pay->patientAccount?->full_name ?? 'Walk-In') }}</strong>
              <span class="text-[11px] text-slate-400 font-mono">{{ $pay->patientAccount?->patient_id_number }}</span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-mono font-medium bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                {{ $pay->invoice?->invoice_number ?? 'COP-SETTLED' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                {{ $pay->payment_method }}
              </span>
            </td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
              {{ $pay->cashierShift?->shift_code ?? '-' }}
            </td>
            <td class="px-4 py-3">
              <a 
                href="{{ route('gl.journal-entries') }}?search={{ $pay->payment_reference }}" 
                class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-0.5 text-[11px] font-mono font-medium text-blue-700 ring-1 ring-blue-500/20 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300"
              >
                <i class="ph-bold ph-link-simple"></i> JE-COL-{{ $pay->payment_reference }}
              </a>
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums {{ $isCancelled ? 'text-slate-400' : 'text-emerald-600 dark:text-emerald-400' }}">
              ₱{{ number_format((float) $pay->amount, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              @if($isCancelled)
                <x-status-badge status="inactive" label="VOIDED" size="sm" />
              @else
                <x-status-badge status="active" label="VALID" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <a 
                  href="{{ route('collection.receipts.print', $pay->id) }}" 
                  target="_blank" 
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" 
                  title="Print Official Receipt"
                >
                  <i class="ph-bold ph-printer text-emerald-600"></i>
                </a>
                @if(! $isCancelled)
                  <button 
                    type="button" 
                    class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-800 dark:text-rose-400 cursor-pointer" 
                    title="Void Official Receipt" 
                    onclick="openVoidModal('{{ $pay->id }}', '{{ $orNo }}', '{{ number_format((float) $pay->amount, 2) }}')"
                  >
                    <i class="ph-bold ph-prohibit"></i>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-receipt text-3xl mb-2 text-slate-400"></i>
              <p>No official payment receipts found.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $payments->count() }} of {{ $payments->total() }} Official Receipts</span>
      <div>
        {{ $payments->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal: Void Official Receipt -->
<x-modal 
  id="voidReceiptModal" 
  title="Void Official Receipt & Reverse Ledger" 
  subtitle="Marks receipt as CANCELLED, restores invoice balance, and posts GL reversal." 
  icon="ph-warning" 
  iconVariant="rose" 
  size="md" 
  formId="voidReceiptForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Confirm Receipt Void" 
  submitIcon="ph-prohibit"
>
  <div class="space-y-4">
    <p class="text-xs text-slate-500 dark:text-slate-400">
      Voiding an official receipt marks it as <strong>CANCELLED</strong> in compliance with BIR CAS rules, restores the outstanding copay balance on the patient invoice, and automatically posts a balancing reversing General Ledger entry.
    </p>

    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-1.5">
      <div class="flex justify-between items-center text-xs">
        <span class="text-slate-400">Official Receipt:</span>
        <span id="voidOrNumber" class="font-mono font-bold text-slate-900 dark:text-white">-</span>
      </div>
      <div class="flex justify-between items-center text-xs pt-1 border-t border-slate-200 dark:border-slate-700">
        <span class="text-slate-400">Amount to Reverse:</span>
        <span id="voidAmount" class="font-mono font-bold text-rose-600 dark:text-rose-400 tabular-nums">-</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Reason for Voiding <span class="text-rose-500">*</span>
      </label>
      <select 
        name="reason" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-rose-500 focus:bg-white focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        <option value="Erroneous Amount Tendered">Erroneous Amount Tendered</option>
        <option value="Duplicate Official Receipt">Duplicate Official Receipt</option>
        <option value="Patient Transaction Cancelled / Reversed">Patient Transaction Cancelled / Reversed</option>
        <option value="Payment Method Input Correction">Payment Method Input Correction</option>
        <option value="Management Discretion / Refund">Management Discretion / Refund</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Audit Justification Notes
      </label>
      <textarea 
        name="notes" 
        rows="2" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:bg-white focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="Additional audit details..."
      ></textarea>
    </div>
  </div>
</x-modal>

@push('scripts')
<script>
function openVoidModal(paymentId, orNumber, amount) {
  const form = document.getElementById('voidReceiptForm');
  if (form) {
    form.action = `/collection-management/payment-receipts/${paymentId}/void`;
  }

  const elOr = document.getElementById('voidOrNumber');
  if (elOr) elOr.textContent = orNumber;

  const elAmt = document.getElementById('voidAmount');
  if (elAmt) elAmt.textContent = '₱' + amount;

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'voidReceiptModal' }));
}
</script>
@endpush
@endsection
