@extends('layouts.app')

@section('title', 'Disbursement Approvals - Disbursement | FMS')
@section('module', 'disbursement')
@section('page', 'disbursement-approval')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Executive Disbursement Approvals &amp; Release
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('disbursement.payment-requests') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
      >
        <i class="ph-bold ph-receipt text-blue-600"></i>
        <span>Payment Requests Hub</span>
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

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Prepared (Pending Audit)" 
      :value="$totalPrepared ?? 0" 
      icon="ph-clock" 
      color="slate" 
      subtitle="Initial requisitions awaiting audit"
    />
    <x-stat-card 
      title="Audited (Pending Approval)" 
      :value="$totalAudited ?? 0" 
      icon="ph-hourglass" 
      color="amber" 
      subtitle="Audited vouchers needing approval"
    />
    <x-stat-card 
      title="Approved (Ready for Release)" 
      :value="$totalApproved ?? 0" 
      icon="ph-stamp" 
      color="blue" 
      subtitle="Authorized for check / EFT release"
    />
    <x-stat-card 
      title="Total Released Payouts" 
      :value="$totalReleased ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Settled & disbursed from bank"
    />
  </div>

  <!-- Approvals Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('disbursement.disbursement-approval') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-blue-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            onchange="this.form.submit()"
          >
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="AUDITED" {{ request('status') === 'AUDITED' ? 'selected' : '' }}>Audited (Pending Approval)</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved (Ready for Release)</option>
            <option value="PREPARED" {{ request('status') === 'PREPARED' ? 'selected' : '' }}>Prepared</option>
            <option value="RELEASED" {{ request('status') === 'RELEASED' ? 'selected' : '' }}>Released</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-blue-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search voucher #, payee..." 
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
            <th class="px-4 py-3.5">Payee &amp; Description</th>
            <th class="px-4 py-3.5">Bank Account</th>
            <th class="px-4 py-3.5">Method</th>
            <th class="px-4 py-3.5">Voucher Date</th>
            <th class="px-4 py-3.5 text-right">Amount (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
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
              <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ $v->voucher_number }}</span>
              @if($v->check_or_eft_ref)
                <div class="text-[11px] text-slate-400 font-mono">Ref: {{ $v->check_or_eft_ref }}</div>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $v->payee_name }}</div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ $v->description ?? ($v->purchaseBill ? "Bill {$v->purchaseBill->bill_number}" : 'Disbursement Requisition') }}
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
              @else
                <x-status-badge status="pending" label="{{ $v->status }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                @if($v->status === 'AUDITED' || $v->status === 'PREPARED' || $v->status === 'DRAFT')
                  <form method="POST" action="{{ route('disbursement.disbursement-approvals.approve', $v->id) }}" onsubmit="return confirm('Approve disbursement voucher {{ $v->voucher_number }}?');" class="inline">
                    @csrf
                    <button 
                      type="submit" 
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer" 
                      title="Finance Management Approval"
                    >
                      <i class="ph-bold ph-stamp"></i>
                      <span>Approve</span>
                    </button>
                  </form>
                @elseif($v->status === 'APPROVED')
                  <button 
                    type="button" 
                    class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-teal-700 transition-all cursor-pointer" 
                    onclick="openDisburseReleaseModal({{ $v->id }}, '{{ $v->voucher_number }}', '{{ addslashes($v->payee_name) }}', '{{ $v->payment_method }}', {{ $amt }})"
                  >
                    <i class="ph-bold ph-paper-plane-tilt"></i>
                    <span>Release</span>
                  </button>
                @else
                  <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                    <i class="ph-bold ph-check-circle text-emerald-500"></i> Settled
                  </span>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-shield-check text-3xl mb-2 text-slate-400"></i>
              <p>No disbursement vouchers found matching current filter.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $vouchers->firstItem() ?? 0 }} - {{ $vouchers->lastItem() ?? 0 }} of {{ $vouchers->total() }} Vouchers</span>
      <div>
        {{ $vouchers->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal: Release Disbursement Payout -->
<x-modal 
  id="disburseReleaseModal" 
  title="Executive Payout Release" 
  subtitle="Confirm treasury release and log the issued check or electronic fund transfer reference." 
  icon="ph-check-circle" 
  iconVariant="emerald" 
  size="md" 
  formId="disburseReleaseForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Confirm Executive Release" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2">
      <div>
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Voucher Reference</span>
        <div class="font-mono font-bold text-blue-600 dark:text-blue-400 text-sm" id="drelVoucherRef">-</div>
      </div>
      <div>
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Payee Legal Name</span>
        <div class="font-semibold text-slate-900 dark:text-white text-xs" id="drelPayeeName">-</div>
      </div>
      <div>
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Net Disbursed Amount</span>
        <div class="font-mono font-bold text-slate-900 dark:text-white text-base tabular-nums" id="drelAmount">₱0.00</div>
      </div>
    </div>

    <div id="drelCheckSection" class="rounded-xl bg-blue-50/60 p-3 ring-1 ring-blue-200/70 dark:bg-blue-950/20 dark:ring-blue-900/40 space-y-2">
      <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300">
        <i class="ph-bold ph-pencil-simple-line mr-1"></i> Check Number (Check Register)
      </label>
      <input 
        type="text" 
        name="check_number" 
        id="drelCheckNumber" 
        class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. CHK-10299401"
      >
      <div class="pt-1">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Check Issuance Date</label>
        <input 
          type="date" 
          name="check_date" 
          class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}"
        >
      </div>
    </div>

    <div id="drelEftSection" class="rounded-xl bg-teal-50/60 p-3 ring-1 ring-teal-200/70 dark:bg-teal-950/20 dark:ring-teal-900/40 space-y-2" style="display: none;">
      <label class="block text-xs font-semibold text-teal-700 dark:text-teal-300">
        <i class="ph-bold ph-bank mr-1"></i> EFT Reference / Trace Number
      </label>
      <input 
        type="text" 
        name="eft_reference" 
        id="drelEftReference" 
        class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. EFT-PN-20260826-091"
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Treasury Settlement Notes</label>
      <input 
        type="text" 
        name="notes" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Released across Treasury Counter / EFT Cleared"
      >
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openDisburseReleaseModal(voucherId, voucherRef, payee, paymentMethod, amount) {
  const form = document.getElementById('disburseReleaseForm');
  if (form) {
    form.action = `/disbursement-management/disbursement-approvals/${voucherId}/release`;
  }

  const elRef = document.getElementById('drelVoucherRef');
  if (elRef) elRef.textContent = voucherRef;

  const elPayee = document.getElementById('drelPayeeName');
  if (elPayee) elPayee.textContent = payee;

  const elAmt = document.getElementById('drelAmount');
  if (elAmt) elAmt.textContent = '₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

  const checkSection = document.getElementById('drelCheckSection');
  const eftSection = document.getElementById('drelEftSection');
  const checkInput = document.getElementById('drelCheckNumber');
  const eftInput = document.getElementById('drelEftReference');

  if (paymentMethod === 'CHECK') {
    if (checkSection) checkSection.style.display = 'block';
    if (eftSection) eftSection.style.display = 'none';
    if (checkInput) checkInput.value = 'CHK-' + new Date().getFullYear() + '-' + Math.floor(100000 + Math.random() * 900000);
    if (eftInput) eftInput.value = '';
  } else {
    if (checkSection) checkSection.style.display = 'none';
    if (eftSection) eftSection.style.display = 'block';
    if (checkInput) checkInput.value = '';
    if (eftInput) eftInput.value = 'EFT-' + new Date().getFullYear() + '-' + Math.floor(100000 + Math.random() * 900000);
  }

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'disburseReleaseModal' }));
}
</script>
@endpush
