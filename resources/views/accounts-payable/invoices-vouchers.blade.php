@extends('layouts.app')

@section('title', 'Vendor Invoices & Tax Processing - Accounts Payable | FMS')
@section('module', 'ap')
@section('page', 'invoices')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Vendor Invoices &amp; Tax Processing
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('ap.purchase-bills') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Record Supplier Bill</span>
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

  @if($errors->any())
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <div class="flex items-center gap-2 mb-2 font-bold text-rose-700 dark:text-rose-300">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600"></i>
        <span>Please check the voucher requirements:</span>
      </div>
      <ul class="list-disc list-inside space-y-1 pl-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Summary Metric Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Total Open Bills" 
      :value="$totalOpenBills ?? 0" 
      :isCurrency="false" 
      icon="ph-receipt" 
      color="slate" 
      subtitle="Pending settlement or voucher release"
    />

    <x-stat-card 
      title="Outstanding AP Balance" 
      :value="$totalPayableAmount ?? 0" 
      icon="ph-trend-down" 
      color="rose" 
      subtitle="Net cash liability owed to vendors"
    />

    <x-stat-card 
      title="Total EWT Withheld (2307)" 
      :value="$totalEwtWithheld ?? 0" 
      icon="ph-percent" 
      color="indigo" 
      subtitle="Creditable withholding for BIR Form 1601-EQ"
      badge="EWT"
    />

    <div class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition-all hover:shadow-md dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Payment Window</span>
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/20 dark:bg-blue-950/50 dark:text-blue-400">
          <i class="ph-bold ph-calendar-check text-lg"></i>
        </span>
      </div>
      <div class="mt-4 flex items-baseline justify-between gap-2">
        <div class="kpi-value font-sans text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
          30 Days
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
          Normal
        </span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Accredited hospital vendor terms</p>
    </div>
  </div>

  <!-- Invoices Table Container -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ap.invoices') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <select 
            name="vendor_id" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Suppliers</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                {{ $v->code }} — {{ $v->name }}
              </option>
            @endforeach
          </select>

          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Statuses</option>
            <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>UNPAID</option>
            <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>PARTIAL</option>
            <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>PAID</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
          </select>

          <select 
            name="match_status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Verification States</option>
            <option value="MATCHED" {{ request('match_status') === 'MATCHED' ? 'selected' : '' }}>MATCHED (100%)</option>
            <option value="DISCREPANCY" {{ request('match_status') === 'DISCREPANCY' ? 'selected' : '' }}>DISCREPANCY</option>
            <option value="UNCHECKED" {{ request('match_status') === 'UNCHECKED' ? 'selected' : '' }}>UNCHECKED</option>
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
            placeholder="Search invoice #, PO #, vendor..." 
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
            <th scope="col" class="py-3.5 pl-5 pr-3">Bill Ref / PO #</th>
            <th scope="col" class="px-3 py-3.5">Supplier Legal Name</th>
            <th scope="col" class="px-3 py-3.5">Dates (Bill / Due)</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Gross Total</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">EWT (2307)</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Net Payable</th>
            <th scope="col" class="px-3 py-3.5 text-center">3-Way Match</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($invoices as $inv)
            @php
              $gross = (float) $inv->total_amount;
              $ewt = (float) ($inv->tax_withheld ?? 0);
              $net = (float) ($inv->net_payable ?? ($gross - $ewt));
              $matchStatus = strtoupper((string) ($inv->match_status ?? 'MATCHED'));

              $committedVouchers = (float) $inv->disbursementVouchers->whereIn('status', ['DRAFT', 'AUDITED', 'APPROVED', 'RELEASED'])->sum('net_disbursed_amount');
              $uncommittedBalance = max(0, (float) $inv->total_amount - $committedVouchers);
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3">
                <div class="font-mono font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                  {{ $inv->invoice_number ?: $inv->bill_number }}
                </div>
                <div class="text-[11px] font-mono text-slate-500">
                  PO: {{ $inv->purchase_order_number ?: ($inv->po_number ?: ($inv->threeWayMatch?->po_number ?: 'N/A')) }}
                </div>
                @if($inv->threeWayMatch?->vendor_invoice_number)
                  <div class="text-[10px] font-mono text-slate-400">
                    Inv: {{ $inv->threeWayMatch->vendor_invoice_number }}
                  </div>
                @endif
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">
                  {{ $inv->vendor?->name ?? 'External Supplier' }}
                </div>
                <div class="text-[11px] font-mono text-slate-500">
                  TIN: {{ $inv->vendor?->tin ?: 'N/A' }}
                </div>
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400 font-mono">
                <div>{{ \Carbon\Carbon::parse($inv->invoice_date ?? $inv->bill_date)->format('M d, Y') }}</div>
                <div class="text-[11px] text-slate-400">Due: {{ \Carbon\Carbon::parse($inv->due_date)->format('M d, Y') }}</div>
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
                ₱{{ number_format($gross, 2) }}
              </td>
              <td class="px-3 py-3.5 text-right font-mono tabular-nums text-indigo-600 dark:text-indigo-400 font-semibold">
                -₱{{ number_format($ewt, 2) }}
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                ₱{{ number_format($net, 2) }}
              </td>
              <td class="px-3 py-3.5 text-center">
                @if($matchStatus === 'MATCHED')
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <i class="ph-bold ph-check"></i> Matched
                  </span>
                @elseif($matchStatus === 'DISCREPANCY')
                  <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
                    <i class="ph-bold ph-warning"></i> Variance
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    Unchecked
                  </span>
                @endif
              </td>
              <td class="px-3 py-3.5 text-center">
                <x-status-badge :status="$inv->status" />
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  @if($inv->status === 'UNPAID' || $inv->status === 'APPROVED')
                    @if($uncommittedBalance > 0.001)
                      <button 
                        type="button" 
                        @click="$dispatch('open-modal', {
                          id: 'prepareVoucherModal',
                          billId: {{ $inv->id }},
                          billNumber: '{{ $inv->bill_number }}',
                          vendorName: '{{ addslashes($inv->vendor?->name ?? 'Vendor') }}',
                          amount: '{{ number_format($uncommittedBalance, 2, '.', '') }}'
                        })"
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
                        title="Prepare Disbursement Voucher"
                      >
                        <i class="ph-bold ph-file-plus"></i>
                        <span>Voucher</span>
                      </button>
                    @else
                      <a 
                        href="{{ route('ap.payment-approvals.index') }}" 
                        class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-600/20 hover:bg-blue-100 transition-all dark:bg-blue-950/40 dark:text-blue-300"
                        title="Voucher already generated. Authorize and release in Payment Approvals Hub"
                      >
                        <i class="ph-bold ph-arrow-square-out"></i>
                        <span>In Approvals</span>
                      </a>
                    @endif
                  @endif
                  @if($inv->status === 'UNPAID')
                    <form action="{{ route('ap.invoices.quick-approve', $inv->id) }}" method="POST" class="inline">
                      @csrf
                      <button 
                        type="submit" 
                        class="rounded-lg p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-colors"
                        title="Quick Authorize Invoice"
                      >
                        <i class="ph-bold ph-check text-sm"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-receipt text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No vendor invoices found matching your criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      {{ $invoices->links() }}
    </div>
  </div>
</div>

<!-- Modal: Prepare Disbursement Voucher -->
<x-modal 
  id="prepareVoucherModal" 
  title="Prepare Disbursement Voucher" 
  subtitle="Authorize bank disbursement and route to AP Payment Approvals" 
  icon="ph-receipt" 
  iconVariant="emerald" 
  size="xl" 
  formAction="{{ route('ap.invoices.prepare-voucher') }}" 
  formMethod="POST" 
  submitText="Generate Disbursement Voucher"
  submitIcon="ph-check"
>
  <div 
    x-data="{
      billId: '',
      billNumber: '',
      vendorName: '',
      amount: '0.00',
      initVoucher(data) {
        if (!data) return;
        this.billId = data.billId || '';
        this.billNumber = data.billNumber || '';
        this.vendorName = data.vendorName || '';
        this.amount = data.amount || '0.00';
      }
    }"
    @open-modal.window="if ($event.detail.id === 'prepareVoucherModal') { initVoucher($event.detail); }"
    class="space-y-4"
  >
    <input type="hidden" name="purchase_bill_id" :value="billId">

    <!-- Bill Summary Card -->
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 font-mono text-xs">
      <div>
        <span class="text-[10px] text-slate-400 font-sans uppercase tracking-wider block">Target Procurement Bill</span>
        <strong class="text-sm font-bold text-slate-900 dark:text-white" x-text="billNumber"></strong>
        <div class="text-xs text-slate-500 font-sans" x-text="vendorName"></div>
      </div>
      <div class="sm:text-right">
        <span class="text-[10px] text-slate-400 font-sans uppercase tracking-wider block">Disbursement Amount</span>
        <strong class="text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="'₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></strong>
      </div>
    </div>

    <!-- Settlement Details Form -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Settlement Bank Account <span class="text-rose-500">*</span>
        </label>
        <select name="bank_account_id" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
          @foreach($bankAccounts as $bank)
            <option value="{{ $bank->id }}" {{ $loop->first ? 'selected' : '' }}>
              {{ $bank->bank_name ?: $bank->name }} ({{ $bank->account_number }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Payment Method <span class="text-rose-500">*</span>
        </label>
        <select name="payment_method" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
          <option value="PESONET_EFT" selected>PESONet EFT (Direct Bank Transfer)</option>
          <option value="CHECK">Commercial Bank Check</option>
          <option value="INSTAPAY">InstaPay Real-Time Settlement</option>
          <option value="TELEGRAPHIC_TRANSFER">Telegraphic Wire Transfer</option>
          <option value="PETTY_CASH">Petty Cash Voucher</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Voucher Date <span class="text-rose-500">*</span>
        </label>
        <input type="date" name="voucher_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Disbursement Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Check / EFT Batch Reference (Optional)
        </label>
        <input type="text" name="check_or_eft_ref" placeholder="e.g. EFT-BATCH-202610-01 or Check #440192" class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
      </div>
    </div>
  </div>
</x-modal>
@endsection
