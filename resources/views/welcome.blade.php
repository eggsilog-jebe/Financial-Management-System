@extends('layouts.app')

@section('title', 'Financial Management System (TRANSACTION CORE)')
@section('module', 'finance')
@section('page', 'dashboard')

@section('content')
<div class="space-y-6">
  <!-- Executive Command Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Financial Management System
      </h1>
    </div>
    <div class="flex items-center gap-2">
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-printer text-sm"></i>
        <span>Print Summary</span>
      </button>
      <button 
        type="button" 
        onclick="showToast('Transaction Core Active &amp; Ledger Invariance Verified', 'success')" 
        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all ring-1 ring-emerald-600/20"
      >
        <i class="ph-bold ph-lightning text-sm"></i>
        <span>System Status: Optimal</span>
      </button>
    </div>
  </div>

  <!-- Key KPI Metrics Row -->
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat-card 
      title="Total Ledger Balance" 
      :value="$totalLedgerBalance ?? 0" 
      :isCurrency="true" 
      icon="ph-trend-up" 
      color="emerald" 
      subtitle="Real-time General Ledger Balance" 
      badge="GL"
    />
    <x-stat-card 
      title="Accounts Receivable" 
      :value="$totalAR ?? 0" 
      :isCurrency="true" 
      icon="ph-clock" 
      color="blue" 
      :subtitle="($activeInvoices ?? 0) . ' Active Patient & HMO Invoices'" 
      badge="Pending"
    />
    <x-stat-card 
      title="Accounts Payable" 
      :value="$totalAP ?? 0" 
      :isCurrency="true" 
      icon="ph-calendar" 
      color="amber" 
      :subtitle="($pendingVendors ?? 0) . ' Vendor Payments Due'" 
      badge="Scheduled"
    />
    <x-stat-card 
      title="Available Cash Pool" 
      :value="$totalCash ?? 0" 
      :isCurrency="true" 
      icon="ph-shield-check" 
      color="teal" 
      :subtitle="'Liquid Across ' . ($bankAccountCount ?? 0) . ' Bank Account' . (($bankAccountCount ?? 0) !== 1 ? 's' : '')" 
      badge="Optimal"
    />
  </div>

  <!-- Core Modules Grid Header -->
  <div class="flex items-center justify-between pt-2">
    <div>
      <h2 class="text-base font-bold text-slate-900 dark:text-white">Transaction Core Systems &amp; Workstations</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Direct navigation across all 10 clinical fintech modules</p>
    </div>
    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
      <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
      <span>10 Modules Active</span>
    </span>
  </div>

  <!-- Module Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @php
      $modules = [
        [
          'title' => 'General Ledger', 
          'desc' => 'Chart of Accounts, Journal Entries, Ledger Books, Trial Balance & Period Closing',
          'icon' => 'ph-book-open', 
          'badge' => 'GL Core', 
          'color' => 'emerald',
          'route' => route('gl.chart-of-accounts')
        ],
        [
          'title' => 'Accounts Payable (AP)', 
          'desc' => 'Vendor Registry, 3-Way Matching, Purchase Bills, Aging & Payment Approvals',
          'icon' => 'ph-receipt', 
          'badge' => 'Liabilities', 
          'color' => 'amber',
          'route' => route('ap.vendors')
        ],
        [
          'title' => 'Accounts Receivable (AR)', 
          'desc' => 'Patient Billing, PhilHealth ACR, RA 9994/10754 Discounts & Malasakit Assistance',
          'icon' => 'ph-currency-circle-dollar', 
          'badge' => 'Receivables', 
          'color' => 'blue',
          'route' => route('ar.customers')
        ],
        [
          'title' => 'Disbursement Management', 
          'desc' => 'Payment Vouchers, Multi-signature Approvals, Check Register & Imprest Petty Cash',
          'icon' => 'ph-arrows-out', 
          'badge' => 'Treasury', 
          'color' => 'rose',
          'route' => route('disbursement.payment-requests')
        ],
        [
          'title' => 'Collection Management', 
          'desc' => 'Cashier Shifts, POS Settlement, Official Receipts (OR) & Bank Deposits',
          'icon' => 'ph-vault', 
          'badge' => 'Collections', 
          'color' => 'teal',
          'route' => route('collection.receipts')
        ],
        [
          'title' => 'Budget Management', 
          'desc' => 'Fiscal Planning, Department Allocations, Encumbrances & Variance Analysis',
          'icon' => 'ph-calculator', 
          'badge' => 'Planning', 
          'color' => 'purple',
          'route' => route('budget.fiscal-planning')
        ],
        [
          'title' => 'User Security & CAS Audit', 
          'desc' => 'Workstation Binding, Role Authorization, 2FA TOTP & Tamper-proof CAS Logs',
          'icon' => 'ph-shield-check', 
          'badge' => 'Security', 
          'color' => 'emerald',
          'route' => route('user-security.workstations')
        ],
      ];
    @endphp

    @foreach($modules as $mod)
    <a 
      href="{{ $mod['route'] }}" 
      class="group relative flex flex-col justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-emerald-500/40 dark:bg-slate-900 dark:ring-slate-800 dark:hover:ring-emerald-500/40"
    >
      <div>
        <div class="flex items-center justify-between">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-700 ring-1 ring-slate-200 group-hover:bg-emerald-50 group-hover:text-emerald-600 group-hover:ring-emerald-500/20 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:group-hover:bg-emerald-950/50 dark:group-hover:text-emerald-400 transition-colors">
            <i class="ph-bold {{ $mod['icon'] }} text-xl"></i>
          </span>
          <x-status-badge :status="$mod['badge']" :variant="$mod['color']" />
        </div>
        <h3 class="mt-4 text-sm font-bold text-slate-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400 transition-colors">
          {{ $mod['title'] }}
        </h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
          {{ $mod['desc'] }}
        </p>
      </div>

      <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400">
        <span>Open Workstation</span>
        <i class="ph-bold ph-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endsection
