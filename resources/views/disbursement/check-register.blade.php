@extends('layouts.app')

@section('title', 'Check Register & Issuance - Disbursement | FMS')
@section('module', 'disbursement')
@section('page', 'check-register')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Bank Check Register &amp; Printing
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        id="btnIssueCheck"
        @click="$dispatch('open-modal', 'issueCheckModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Issue New Check</span>
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

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total Checks Logged" 
      :value="$totalIssued ?? 0" 
      :isCurrency="false"
      icon="ph-file-text" 
      color="slate" 
      subtitle="Cumulative serial checks in ledger"
    />
    <x-stat-card 
      title="Issued / Released" 
      :value="$totalReleased ?? 0" 
      icon="ph-clock" 
      color="blue" 
      subtitle="Uncleared checks in circulation"
    />
    <x-stat-card 
      title="Cleared via Bank Recon" 
      :value="$totalCleared ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Successfully reconciled bank checks"
    />
    <x-stat-card 
      title="Void / Stale Checks" 
      :value="$totalVoid ?? 0" 
      icon="ph-x-circle" 
      color="rose" 
      subtitle="Cancelled or expired check series"
    />
  </div>

  <!-- Check Register Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('disbursement.check-register') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Check Status:</span>
          </div>
          <select 
            name="status" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            onchange="this.form.submit()"
          >
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="ISSUED" {{ request('status') === 'ISSUED' ? 'selected' : '' }}>Issued</option>
            <option value="RELEASED" {{ request('status') === 'RELEASED' ? 'selected' : '' }}>Released</option>
            <option value="CLEARED" {{ request('status') === 'CLEARED' ? 'selected' : '' }}>Cleared</option>
            <option value="VOID" {{ request('status') === 'VOID' ? 'selected' : '' }}>Void</option>
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
            placeholder="Search check #, payee, voucher..." 
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
            <th class="px-4 py-3.5">Check Number</th>
            <th class="px-4 py-3.5">Payee Legal Name</th>
            <th class="px-4 py-3.5">Voucher Ref #</th>
            <th class="px-4 py-3.5">Bank Account</th>
            <th class="px-4 py-3.5">Check Date</th>
            <th class="px-4 py-3.5 text-right">Amount (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($checks as $chk)
          @php
            $amt = (float) $chk->amount;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">
              {{ $chk->check_number }}
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $chk->payee_name }}</div>
            </td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
              {{ $chk->disbursementVoucher?->voucher_number ?? 'N/A' }}
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white">{{ $chk->bankAccount?->bank_name ?? 'Bank' }}</div>
              <div class="text-[11px] text-slate-400 font-mono">{{ $chk->bankAccount?->account_number ?? 'Acc' }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ $chk->check_date ? $chk->check_date->format('M d, Y') : '—' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
              ₱{{ number_format($amt, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              @if($chk->status === 'CLEARED')
                <x-status-badge status="active" label="CLEARED" size="sm" />
                @if($chk->cleared_at)
                  <div class="text-[10px] text-slate-400 mt-0.5">Cleared: {{ $chk->cleared_at->format('M d') }}</div>
                @endif
              @elseif($chk->status === 'RELEASED')
                <x-status-badge status="blue" label="RELEASED" size="sm" />
              @elseif($chk->status === 'VOID')
                <x-status-badge status="inactive" label="VOID" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $chk->status }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <a 
                  href="{{ route('disbursement.check-register.print', $chk->id) }}" 
                  target="_blank" 
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" 
                  title="Print Check Template"
                >
                  <i class="ph-bold ph-printer text-blue-600"></i>
                  <span>Print</span>
                </a>
                @if($chk->status !== 'CLEARED' && $chk->status !== 'VOID')
                  <form method="POST" action="{{ route('disbursement.check-register.clear', $chk->id) }}" onsubmit="return confirm('Mark check {{ $chk->check_number }} as CLEARED?');" class="inline">
                    @csrf
                    <button 
                      type="submit" 
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer" 
                      title="Bank Clearing"
                    >
                      <i class="ph-bold ph-check"></i>
                      <span>Clear</span>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-money text-3xl mb-2 text-slate-400"></i>
              <p>No check register records found matching current filter.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $checks->firstItem() ?? 0 }} - {{ $checks->lastItem() ?? 0 }} of {{ $checks->total() }} Checks</span>
      <div>
        {{ $checks->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal: Issue Check -->
<x-modal 
  id="issueCheckModal" 
  title="Issue Bank Check" 
  subtitle="Select an approved disbursement voucher to generate and log an official bank check." 
  icon="ph-money" 
  iconVariant="blue" 
  size="md" 
  formAction="{{ route('disbursement.check-register.store') }}" 
  formMethod="POST" 
  submitText="Register Check" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Disbursement Voucher <span class="text-rose-500">*</span>
      </label>
      <select 
        name="disbursement_voucher_id" 
        id="issueVoucherSelect" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required 
        onchange="updateCheckFormFromVoucher(this)"
      >
        <option value="">-- Select Approved Disbursement Voucher --</option>
        @foreach($approvedVouchers as $av)
          <option value="{{ $av->id }}" data-bank="{{ $av->bank_account_id }}" data-payee="{{ $av->payee_name }}" data-amount="{{ $av->net_disbursed_amount }}">
            {{ $av->voucher_number }} — {{ $av->payee_name }} (₱{{ number_format((float) $av->net_disbursed_amount, 2) }})
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Bank Account <span class="text-rose-500">*</span>
      </label>
      <select 
        name="bank_account_id" 
        id="issueBankSelect" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        @foreach($bankAccounts as $ba)
          <option value="{{ $ba->id }}">{{ $ba->bank_name }} ({{ $ba->account_number }})</option>
        @endforeach
      </select>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Check Number <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          name="check_number" 
          id="issueCheckNum" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. CHK-2026-9901" 
          required
        >
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Check Date <span class="text-rose-500">*</span>
        </label>
        <input 
          type="date" 
          name="check_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}" 
          required
        >
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Payee Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="payee_name" 
        id="issuePayeeName" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Amount (₱) <span class="text-rose-500">*</span>
      </label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
        <input 
          type="number" 
          step="0.01" 
          name="amount" 
          id="issueAmount" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
          placeholder="0.00" 
          required
        >
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function updateCheckFormFromVoucher(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  if (!opt || !opt.value) return;

  const bankId = opt.getAttribute('data-bank');
  const payee = opt.getAttribute('data-payee');
  const amount = opt.getAttribute('data-amount');

  if (bankId) document.getElementById('issueBankSelect').value = bankId;
  if (payee) document.getElementById('issuePayeeName').value = payee;
  if (amount) document.getElementById('issueAmount').value = parseFloat(amount).toFixed(2);
  document.getElementById('issueCheckNum').value = 'CHK-' + new Date().getFullYear() + '-' + Math.floor(100000 + Math.random() * 900000);
}
</script>
@endpush
