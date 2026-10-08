@extends('layouts.app')

@section('title', 'Supplier Payment Approvals & Disbursement - Accounts Payable | FMS')
@section('module', 'ap')
@section('page', 'ap-approvals')

@section('content')
<div class="space-y-6" x-data="{
  selectedVouchers: [],
  toggleAll(checked, ids) {
    this.selectedVouchers = checked ? ids : [];
  }
}">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Supplier Payment Approvals &amp; Release
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <!-- Bulk Approval Form -->
      <form method="POST" action="{{ route('ap.payment-approvals.bulk-approve') }}" class="inline">
        @csrf
        <template x-for="id in selectedVouchers" :key="id">
          <input type="hidden" name="voucher_ids[]" :value="id">
        </template>
        <button 
          type="submit" 
          :disabled="selectedVouchers.length === 0" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed ring-1 ring-emerald-600/20 transition-all"
        >
          <i class="ph-bold ph-check-circle"></i>
          <span>Authorize Selected Vouchers</span>
        </button>
      </form>
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

  <!-- Summary Metric Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
    <x-stat-card 
      title="Prepared Vouchers (Draft)" 
      :value="$totalPrepared ?? 0" 
      icon="ph-clock" 
      color="amber" 
      subtitle="Awaiting Finance Manager sign-off"
      badge="Pending"
    />

    <x-stat-card 
      title="Approved for Release" 
      :value="$totalApproved ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Ready for CFO release & bank issuance"
      badge="Approved"
    />

    <x-stat-card 
      title="Released / Payout Executed" 
      :value="$totalReleased ?? 0" 
      icon="ph-bank" 
      color="blue" 
      subtitle="Settled via Check or Bank EFT transfer"
      badge="Released"
    />
  </div>

  <!-- Approvals Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ap.payment-approvals.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-funnel"></i>
            <span>Voucher Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($status) ? 'selected' : '' }}>All Statuses</option>
            <option value="DRAFT" {{ ($status ?? '') === 'DRAFT' ? 'selected' : '' }}>DRAFT (Prepared)</option>
            <option value="APPROVED" {{ ($status ?? '') === 'APPROVED' ? 'selected' : '' }}>APPROVED (Pending Release)</option>
            <option value="RELEASED" {{ ($status ?? '') === 'RELEASED' ? 'selected' : '' }}>RELEASED (Settled)</option>
            <option value="REJECTED" {{ ($status ?? '') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            value="{{ $search ?? '' }}" 
            placeholder="Search voucher #, payee, ref..." 
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
            <th scope="col" class="py-3.5 pl-4 pr-2 w-10 text-center">
              <input 
                type="checkbox" 
                @change="toggleAll($el.checked, {{ json_encode($vouchers->pluck('id')->all()) }})" 
                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800"
              >
            </th>
            <th scope="col" class="py-3.5 px-3">Voucher #</th>
            <th scope="col" class="px-3 py-3.5">Payee / Supplier</th>
            <th scope="col" class="px-3 py-3.5">Bill Reference</th>
            <th scope="col" class="px-3 py-3.5">Bank Settlement Account</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Gross Amount</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">EWT Withheld</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Net Payout</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($vouchers as $voucher)
            @php
              $gross = (float) $voucher->gross_amount;
              $ewt = (float) ($voucher->tax_withheld ?? 0);
              $net = (float) ($voucher->net_disbursed_amount ?? ($gross - $ewt));
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-4 pr-2 text-center">
                @if($voucher->status === 'DRAFT')
                  <input 
                    type="checkbox" 
                    :value="{{ $voucher->id }}" 
                    x-model="selectedVouchers" 
                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800"
                  >
                @endif
              </td>
              <td class="py-3.5 px-3 font-mono font-bold text-slate-900 dark:text-white text-xs">
                <div>{{ $voucher->voucher_number }}</div>
                <div class="text-[10px] text-slate-400 font-sans">{{ $voucher->voucher_date?->format('M d, Y') ?? '—' }}</div>
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">
                  {{ $voucher->payee_name }}
                </div>
                <div class="text-[11px] font-mono text-slate-500">
                  TIN: {{ $voucher->purchaseBill?->vendor?->tin ?: 'N/A' }}
                </div>
              </td>
              <td class="px-3 py-3.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                {{ $voucher->purchaseBill?->bill_number ?? 'DIRECT-PAYMENT' }}
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                <div class="font-medium text-slate-900 dark:text-white">{{ $voucher->bankAccount?->name ?? 'Primary Operating Account' }}</div>
                <div class="text-[11px] font-mono text-slate-400">{{ $voucher->check_or_eft_ref ? 'Ref: ' . $voucher->check_or_eft_ref : 'EFT Batch' }}</div>
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
                ₱{{ number_format($gross, 2) }}
              </td>
              <td class="px-3 py-3.5 text-right font-mono tabular-nums text-indigo-600 dark:text-indigo-400 font-semibold">
                -₱{{ number_format($ewt, 2) }}
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400 text-sm">
                ₱{{ number_format($net, 2) }}
              </td>
              <td class="px-3 py-3.5 text-center">
                <x-status-badge :status="$voucher->status" />
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  @if($voucher->status === 'DRAFT')
                    <form action="{{ route('ap.payment-approvals.approve', $voucher->id) }}" method="POST" class="inline">
                      @csrf
                      <button 
                        type="submit" 
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all"
                        title="Authorize Disbursement Voucher"
                      >
                        <i class="ph-bold ph-check"></i>
                        <span>Approve</span>
                      </button>
                    </form>
                    <form action="{{ route('ap.payment-approvals.reject', $voucher->id) }}" method="POST" class="inline">
                      @csrf
                      <button 
                        type="submit" 
                        class="rounded-lg p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors"
                        title="Reject Voucher"
                      >
                        <i class="ph-bold ph-x text-sm"></i>
                      </button>
                    </form>
                  @elseif($voucher->status === 'APPROVED')
                    <form action="{{ route('ap.payment-approvals.release', $voucher->id) }}" method="POST" class="inline">
                      @csrf
                      <button 
                        type="submit" 
                        class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-teal-700 transition-all cursor-pointer"
                        title="CFO Release Payout & Post to Bank"
                      >
                        <i class="ph-bold ph-paper-plane-tilt"></i>
                        <span>Release</span>
                      </button>
                    </form>
                  @else
                    <span class="text-xs text-slate-400 font-semibold flex items-center justify-end gap-1">
                      <i class="ph-bold ph-check-circle text-emerald-500"></i> Executed
                    </span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-stamp text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No disbursement vouchers currently awaiting managerial approval.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      {{ $vouchers->links() }}
    </div>
  </div>
</div>
@endsection
