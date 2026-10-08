@extends('layouts.app')

@section('title', 'Invoice Matching & Verification - Accounts Payable | FMS')
@section('module', 'ap')
@section('page', 'purchase-bills')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('inspectOpen', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  inspectOpen: false,
  inspectData: {
    id: null,
    bill_number: '',
    vendor_invoice: '',
    vendor_name: '',
    vendor_tin: '',
    bill_date: '',
    due_date: '',
    po_number: '',
    grn_number: '',
    po_amount: '₱0.00',
    grn_amount: '₱0.00',
    invoice_amount: '₱0.00',
    variance: '₱0.00',
    variance_raw: 0.00,
    match_status: 'MATCHED',
    bill_status: 'UNPAID',
    approver_name: '',
    approved_at: '',
    items: [],
    tax_withheld: '₱0.00',
    net_payable: '₱0.00'
  },
  openInspection(data) {
    this.inspectData = data;
    this.inspectOpen = true;
  }
}">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          3-Way Invoice Matching &amp; Verification
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <form method="POST" action="{{ route('ap.purchase-bills.sync') }}" class="inline">
        @csrf
        <button 
          type="submit" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3.5 py-2 text-xs font-semibold text-teal-700 ring-1 ring-teal-600/20 hover:bg-teal-100 transition-all dark:bg-teal-950/40 dark:text-teal-300"
          title="Sync latest procurement purchase orders and warehouse receipts"
        >
          <i class="ph-bold ph-arrows-clockwise"></i>
          <span>Sync External PSM/SWS</span>
        </button>
      </form>
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'createBillModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-plus"></i>
        <span>Record Supplier Bill</span>
      </button>
    </div>
  </div>

  <!-- Alerts -->
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-xs font-medium text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-lg text-emerald-600"></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-medium text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 flex items-center justify-between">
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

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Total Purchase Bills" 
      :value="$bills->total()" 
      :isCurrency="false"
      icon="ph-receipt" 
      color="blue" 
      subtitle="Audited procurement bills"
    />

    <x-stat-card 
      title="Total Unpaid Balance" 
      :value="$totalUnpaid" 
      icon="ph-clock-afternoon" 
      color="rose" 
      subtitle="Open accounts payable"
    />

    <x-stat-card 
      title="Paid (Settled)" 
      :value="$totalPaid" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Disbursed supplier payouts"
    />

    <x-stat-card 
      title="Pending 3-Way Approvals" 
      :value="$pendingCount" 
      :isCurrency="false"
      icon="ph-stamp" 
      color="amber" 
      subtitle="Awaiting CFO sign-off"
    />
  </div>

  <!-- 3-Way Matching Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ap.purchase-bills') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-git-merge"></i>
            <span>Variance:</span>
          </div>
          <select 
            name="variance_status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ request('variance_status') === null || request('variance_status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="MATCHED" {{ request('variance_status') === 'MATCHED' ? 'selected' : '' }}>Matched (0.00 Variance)</option>
            <option value="VARIANCE" {{ request('variance_status') === 'VARIANCE' ? 'selected' : '' }}>Discrepancy / Variance</option>
            <option value="PENDING_GRN" {{ request('variance_status') === 'PENDING_GRN' ? 'selected' : '' }}>Pending GRN</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Bill Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
            <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
            <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
            <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative w-full sm:w-72">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass text-sm"></i>
            </div>
            <input 
              type="search" 
              name="search" 
              value="{{ request('search') }}" 
              placeholder="Search bill #, PO, GRN, supplier..." 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
          </div>
          <button type="submit" class="rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
            Filter
          </button>
          @if(request()->hasAny(['variance_status', 'status', 'search']))
            <a href="{{ route('ap.purchase-bills') }}" class="rounded-xl px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-300">
              Reset
            </a>
          @endif
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th class="py-3 px-4">Bill # &amp; Vendor Invoice</th>
            <th class="py-3 px-4">Supplier Legal Name</th>
            <th class="py-3 px-4 text-right font-mono">PO Amount</th>
            <th class="py-3 px-4 text-right font-mono">GRN Amount</th>
            <th class="py-3 px-4 text-right font-mono">Invoice Total</th>
            <th class="py-3 px-4 text-right font-mono">Variance</th>
            <th class="py-3 px-4 text-center">3-Way Match</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($bills as $inv)
            @php
              $m = $inv->threeWayMatch;
              $poAmt = $m ? (float) $m->po_amount : (float) $inv->total_amount;
              $grnAmt = $m ? (float) $m->grn_amount : (float) $inv->total_amount;
              $invAmt = (float) $inv->total_amount;
              $variance = $m ? (float) $m->price_variance : 0.00;
              $matchStatus = $m?->match_status ?? 'MATCHED';

              $inspectionPayload = [
                'id'                 => $inv->id,
                'bill_number'        => $inv->bill_number,
                'vendor_invoice'     => $inv->vendor_invoice_number,
                'vendor_name'        => $inv->vendor?->name ?? 'Unknown Vendor',
                'vendor_tin'         => $inv->vendor?->tin ?? 'N/A',
                'bill_date'          => $inv->bill_date?->format('M d, Y') ?? '—',
                'due_date'           => $inv->due_date?->format('M d, Y') ?? '—',
                'po_number'          => $m?->po_number ?? '—',
                'grn_number'         => $m?->grn_number ?? '—',
                'po_amount'          => '₱' . number_format($poAmt, 2),
                'grn_amount'         => '₱' . number_format($grnAmt, 2),
                'invoice_amount'     => '₱' . number_format($invAmt, 2),
                'variance'           => '₱' . number_format($variance, 2),
                'variance_raw'       => $variance,
                'match_status'       => $matchStatus,
                'bill_status'        => $inv->status,
                'approver_name'      => $m?->approver?->name ?? 'Finance Approver',
                'approved_at'        => $m?->approved_at?->format('M d, Y H:i') ?? ($inv->status === 'APPROVED' ? 'Approved' : 'Pending Review'),
                'items'              => $inv->items->map(fn($it) => [
                    'description'    => $it->description,
                    'expense_type'   => $it->expense_type,
                    'atc_code'       => $it->atc_code,
                    'quantity'       => $it->quantity,
                    'unit_price'     => '₱' . number_format((float) $it->unit_price, 2),
                    'gross'          => '₱' . number_format((float) $it->gross_amount, 2),
                    'ewt'            => '₱' . number_format((float) $it->ewt_amount, 2),
                    'net'            => '₱' . number_format((float) $it->net_payable, 2),
                ])->toArray(),
                'tax_withheld'       => '₱' . number_format((float) $inv->withholding_tax_amount, 2),
                'net_payable'        => '₱' . number_format((float) $inv->net_payable_amount, 2),
              ];
            @endphp
            <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
              <td class="py-3.5 px-4">
                <div class="font-mono font-bold text-slate-900 dark:text-white">{{ $inv->bill_number }}</div>
                <div class="text-xs text-slate-400 font-mono">Inv: {{ $inv->vendor_invoice_number }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $inv->vendor?->name ?? 'Unknown Vendor' }}</div>
                <div class="font-mono text-xs text-slate-400">PO: {{ $m?->po_number ?? 'N/A' }} | GRN: {{ $m?->grn_number ?? 'N/A' }}</div>
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-600 dark:text-slate-400">
                ₱{{ number_format($poAmt, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-600 dark:text-slate-400">
                ₱{{ number_format($grnAmt, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                ₱{{ number_format($invAmt, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold {{ abs($variance) > 0.001 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                ₱{{ number_format($variance, 2) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                @if($matchStatus === 'MATCHED' && abs($variance) < 0.001)
                  <x-status-badge status="MATCHED" label="3-WAY MATCHED" />
                @elseif($matchStatus === 'PENDING_GRN')
                  <x-status-badge status="PENDING" label="PENDING GRN" />
                @else
                  <x-status-badge status="CRITICAL" :label="'VARIANCE (₱' . number_format(abs($variance), 2) . ')'" />
                @endif
              </td>
              <td class="py-3.5 px-4 text-center">
                <x-status-badge :status="$inv->status" />
              </td>
              <td class="py-3.5 px-4 text-right">
                <button 
                  type="button" 
                  @click="openInspection({{ json_encode($inspectionPayload) }})"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100 transition-all dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                  <i class="ph-bold ph-scales"></i>
                  <span>Inspect Matrix</span>
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-receipt text-3xl mb-2 block mx-auto text-slate-300"></i>
                No purchase bills found matching criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      {{ $bills->links() }}
    </div>
  </div>

  <!-- 3-Way Matching Visual Inspection Drawer (Blueprint 6) -->
  <template x-teleport="body">
  <div 
    x-show="inspectOpen" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-hidden" 
    role="dialog" 
    aria-modal="true"
  >
    <div 
      x-show="inspectOpen" 
      x-transition.opacity.duration.300ms 
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
      @click="inspectOpen = false"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
      <div 
        x-show="inspectOpen" 
        x-transition:enter="transform transition ease-in-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @click.outside="inspectOpen = false" 
        class="w-screen max-w-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 flex flex-col justify-between"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="ph-bold ph-scales text-emerald-600"></i>
              <span>3-Way Matching Verification Matrix</span>
            </h3>
            <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="inspectData.bill_number + ' • ' + inspectData.vendor_name"></p>
          </div>
          <button 
            type="button" 
            @click="inspectOpen = false" 
            class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          >
            <i class="ph-bold ph-x text-lg"></i>
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
          <!-- Comparison Matrix 3-Card Visual -->
          <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/80 dark:bg-slate-800/60 dark:ring-slate-700">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-500">3-Way Matching Integrity Check</span>
              <template x-if="Math.abs(inspectData.variance_raw) < 0.001">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                  <i class="ph-bold ph-check"></i> 3-WAY MATCHED (0.00 Variance)
                </span>
              </template>
              <template x-if="Math.abs(inspectData.variance_raw) >= 0.001">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
                  <i class="ph-bold ph-warning"></i> VARIANCE FLAGGED
                </span>
              </template>
            </div>

            <!-- 3 Comparison Columns: PO ↔ RR/DR ↔ Vendor Bill -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 font-mono text-xs">
              <!-- PO Column -->
              <div class="rounded-xl bg-white p-3.5 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                <div class="font-sans font-semibold text-slate-700 dark:text-slate-300 text-xs">1. Purchase Order (PO)</div>
                <div class="mt-1 text-slate-400 text-[11px]" x-text="'Ref: ' + inspectData.po_number"></div>
                <div class="mt-2 font-bold text-slate-900 dark:text-white text-sm" x-text="inspectData.po_amount"></div>
                <div class="text-[10px] text-slate-400 mt-0.5">Procurement Order</div>
              </div>

              <!-- RR Column -->
              <div class="rounded-xl bg-white p-3.5 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                <div class="font-sans font-semibold text-slate-700 dark:text-slate-300 text-xs">2. Receiving Report (RR)</div>
                <div class="mt-1 text-slate-400 text-[11px]" x-text="'Ref: ' + inspectData.grn_number"></div>
                <div class="mt-2 font-bold text-slate-900 dark:text-white text-sm" x-text="inspectData.grn_amount"></div>
                <div class="text-[10px] text-slate-400 mt-0.5">Warehouse Accepted</div>
              </div>

              <!-- Vendor Bill Column -->
              <div class="rounded-xl bg-white p-3.5 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                <div class="font-sans font-semibold text-slate-700 dark:text-slate-300 text-xs">3. Vendor Sales Bill</div>
                <div class="mt-1 text-slate-400 text-[11px]" x-text="'Inv: ' + inspectData.vendor_invoice"></div>
                <div class="mt-2 font-bold text-emerald-600 dark:text-emerald-400 text-sm" x-text="inspectData.invoice_amount"></div>
                <div class="text-[10px] text-slate-400 mt-0.5">Supplier Billed Total</div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center text-xs">
              <span class="text-slate-500 font-sans">Audit Variance:</span>
              <span class="font-mono font-bold text-base" :class="Math.abs(inspectData.variance_raw) < 0.001 ? 'text-emerald-600' : 'text-rose-600'" x-text="inspectData.variance"></span>
            </div>
          </div>

          <!-- Itemized Breakdown Table -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Itemized Line Breakdown (<span x-text="inspectData.items.length"></span> Items)
              </span>
              <span class="text-[11px] text-slate-400 font-mono" x-text="'Due: ' + inspectData.due_date"></span>
            </div>

            <div class="rounded-xl ring-1 ring-slate-200 overflow-hidden dark:ring-slate-800">
              <table class="w-full text-left text-xs font-mono">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50">
                  <tr>
                    <th class="py-2.5 px-3 font-sans">Description</th>
                    <th class="py-2.5 px-3">Qty</th>
                    <th class="py-2.5 px-3 text-right">Unit Price</th>
                    <th class="py-2.5 px-3 text-right">Gross (₱)</th>
                    <th class="py-2.5 px-3 text-right">EWT (2307)</th>
                    <th class="py-2.5 px-3 text-right">Net Payable</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <template x-for="(it, idx) in inspectData.items" :key="idx">
                    <tr>
                      <td class="py-2.5 px-3 font-sans font-medium text-slate-900 dark:text-white" x-text="it.description"></td>
                      <td class="py-2.5 px-3" x-text="it.quantity"></td>
                      <td class="py-2.5 px-3 text-right" x-text="it.unit_price"></td>
                      <td class="py-2.5 px-3 text-right font-bold text-slate-900 dark:text-white" x-text="it.gross"></td>
                      <td class="py-2.5 px-3 text-right text-rose-600 dark:text-rose-400" x-text="it.ewt"></td>
                      <td class="py-2.5 px-3 text-right font-bold text-emerald-600 dark:text-emerald-400" x-text="it.net"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
          <button 
            type="button" 
            @click="inspectOpen = false" 
            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300"
          >
            Close
          </button>

          <template x-if="inspectData.bill_status !== 'APPROVED' && inspectData.bill_status !== 'PAID'">
            <form :action="'/accounts-payable/purchase-bills/' + inspectData.id + '/approve'" method="POST" class="inline">
              @csrf
              <button 
                type="submit" 
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20"
              >
                <i class="ph-bold ph-check-circle"></i>
                <span>Authorize &amp; Convert to AP Voucher</span>
              </button>
            </form>
          </template>
          <template x-if="inspectData.bill_status === 'APPROVED' || inspectData.bill_status === 'PAID'">
            <a 
              href="{{ route('ap.invoices') }}" 
              class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
            >
              <i class="ph-bold ph-receipt"></i>
              <span>Open in Voucher Hub</span>
            </a>
          </template>
        </div>
      </div>
    </div>
  </div>
  </template>

  <!-- Modal: Record New Supplier Bill (With Live 3-Way Match Verification) -->
  <x-modal 
    id="createBillModal" 
    title="Record New Supplier Purchase Bill" 
    subtitle="3-Way matched procurement invoice ingestion & BIR ATC tax calculation" 
    icon="ph-plus-circle" 
    iconVariant="emerald" 
    size="3xl" 
    formAction="{{ route('ap.purchase-bills.store') }}" 
    formMethod="POST" 
    :showFooter="false"
  >
    <div 
      x-data="{
        autoSyncAmounts: true,
        customPoAmount: null,
        customGrnAmount: null,
        items: [
          { description: '', expense_type: 'GOODS_INVENTORY', atc_code: 'WI158', quantity: 1, unit_price: 0.00 }
        ],
        addItem() {
          this.items.push({ description: '', expense_type: 'GOODS_INVENTORY', atc_code: 'WI158', quantity: 1, unit_price: 0.00 });
        },
        removeItem(index) {
          if (this.items.length > 1) {
            this.items.splice(index, 1);
          }
        },
        get totalGross() {
          return this.items.reduce((sum, it) => sum + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unit_price) || 0)), 0);
        },
        get poAmount() {
          return this.autoSyncAmounts ? this.totalGross : (parseFloat(this.customPoAmount) || 0);
        },
        get grnAmount() {
          return this.autoSyncAmounts ? this.totalGross : (parseFloat(this.customGrnAmount) || 0);
        },
        get totalEwt() {
          return this.items.reduce((sum, it) => {
            const gross = (parseFloat(it.quantity) || 0) * (parseFloat(it.unit_price) || 0);
            const rate = it.atc_code === 'WI160' ? 0.02 : (it.atc_code === 'WC100' ? 0.05 : (it.atc_code === 'WI010' ? 0.10 : (it.atc_code === 'EXEMPT' ? 0.00 : 0.01)));
            return sum + (gross * rate);
          }, 0);
        },
        get totalNet() {
          return this.totalGross - this.totalEwt;
        },
        get priceVariance() {
          return this.totalGross - this.poAmount;
        },
        get isMatched() {
          return Math.abs(this.priceVariance) < 0.001 && this.totalGross > 0;
        }
      }" 
      class="space-y-5"
    >
      <!-- Vendor & Document References -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Select Supplier Vendor <span class="text-rose-500">*</span></label>
          <select name="vendor_id" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
            <option value="" disabled selected>Select Approved Vendor...</option>
            @foreach(\App\Models\Vendor::orderBy('name')->get() as $v)
              <option value="{{ $v->id }}">{{ $v->name }} (TIN: {{ $v->tin }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Bill Date <span class="text-rose-500">*</span></label>
          <input type="date" name="bill_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Payment Due Date <span class="text-rose-500">*</span></label>
          <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
        </div>
      </div>

      <!-- 3-Way Verification Documents & Controls -->
      <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200/80 pb-3 dark:border-slate-700">
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
              <i class="ph-bold ph-scales text-emerald-600"></i>
              <span>3-Way Verification (PO ↔ Delivery ↔ Supplier Bill)</span>
            </h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Validate hospital purchase order limits and warehouse delivery receiving against this bill.</p>
          </div>

          <!-- Auto-Match vs Custom Variance Toggle -->
          <div class="flex items-center gap-2">
            <button 
              type="button" 
              @click="autoSyncAmounts = !autoSyncAmounts; if (!autoSyncAmounts && !customPoAmount) { customPoAmount = totalGross; customGrnAmount = totalGross; }" 
              class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all shadow-sm ring-1 cursor-pointer"
              :class="autoSyncAmounts 
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/30 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300' 
                : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600'"
            >
              <i class="ph-bold" :class="autoSyncAmounts ? 'ph-check-circle text-emerald-600' : 'ph-pencil-simple text-blue-600'"></i>
              <span x-text="autoSyncAmounts ? '✓ 100% Match Mode (Auto-Synced)' : 'Custom Variance Mode'"></span>
            </button>
          </div>
        </div>

        <!-- 3 Columns for Documents & Values -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <!-- Col 1: Purchase Order -->
          <div class="space-y-2 rounded-xl bg-white p-3 ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-700">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
              <span>1. Purchase Order (PO #)</span>
              <span class="text-[10px] font-normal text-slate-400">Optional</span>
            </label>
            <input 
              type="text" 
              name="po_number" 
              placeholder="e.g. PO-2026-0042" 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
            >

            <div class="pt-1">
              <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <span>Authorized PO Amount:</span>
                <span 
                  x-show="autoSyncAmounts" 
                  class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                >Auto-synced</span>
              </div>

              <!-- When Auto-Synced -->
              <div 
                x-show="autoSyncAmounts" 
                class="flex items-center justify-between rounded-xl bg-emerald-50/60 px-3 py-1.5 text-xs font-mono font-bold text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-300"
              >
                <span>₱</span>
                <span x-text="totalGross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
              </div>

              <!-- When Custom Variance Mode -->
              <div x-show="!autoSyncAmounts" x-cloak class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">₱</span>
                <input 
                  type="number" 
                  step="0.01" 
                  min="0" 
                  x-model.number="customPoAmount" 
                  placeholder="0.00" 
                  class="w-full rounded-xl border-0 bg-white py-1.5 pl-7 pr-3 text-xs font-mono text-right font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
                >
              </div>
            </div>
          </div>

          <!-- Col 2: Goods Receipt Note -->
          <div class="space-y-2 rounded-xl bg-white p-3 ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-700">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
              <span>2. Delivery Receipt (GRN #)</span>
              <span class="text-[10px] font-normal text-slate-400">Optional</span>
            </label>
            <input 
              type="text" 
              name="grn_number" 
              placeholder="e.g. GRN-2026-0092" 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
            >

            <div class="pt-1">
              <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <span>Received GRN Amount:</span>
                <span 
                  x-show="autoSyncAmounts" 
                  class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                >Auto-synced</span>
              </div>

              <!-- When Auto-Synced -->
              <div 
                x-show="autoSyncAmounts" 
                class="flex items-center justify-between rounded-xl bg-emerald-50/60 px-3 py-1.5 text-xs font-mono font-bold text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-300"
              >
                <span>₱</span>
                <span x-text="totalGross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
              </div>

              <!-- When Custom Variance Mode -->
              <div x-show="!autoSyncAmounts" x-cloak class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">₱</span>
                <input 
                  type="number" 
                  step="0.01" 
                  min="0" 
                  x-model.number="customGrnAmount" 
                  placeholder="0.00" 
                  class="w-full rounded-xl border-0 bg-white py-1.5 pl-7 pr-3 text-xs font-mono text-right font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
                >
              </div>
            </div>
          </div>

          <!-- Col 3: Vendor Sales Invoice -->
          <div class="space-y-2 rounded-xl bg-white p-3 ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-700">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
              <span>3. Supplier Sales Invoice #</span>
              <span class="text-rose-500 font-bold">*</span>
            </label>
            <input 
              type="text" 
              name="vendor_invoice_number" 
              placeholder="e.g. SI-88992211" 
              required 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
            >

            <div class="pt-1">
              <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <span>Calculated Bill Total:</span>
                <span class="text-[10px] text-slate-400">From line items</span>
              </div>
              <div class="flex items-center justify-between rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-mono font-bold text-slate-900 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
                <span>₱</span>
                <span x-text="totalGross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden inputs submitted to backend -->
        <input type="hidden" name="po_amount" :value="poAmount">
        <input type="hidden" name="grn_amount" :value="grnAmount">

        <!-- Live 3-Way Match Verification Status Banner -->
        <div 
          class="rounded-xl p-3.5 ring-1 flex flex-col sm:flex-row items-center justify-between gap-3 transition-colors"
          :class="totalGross === 0 
            ? 'bg-slate-100/80 text-slate-700 ring-slate-200 dark:bg-slate-800/80 dark:text-slate-300 dark:ring-slate-700' 
            : (isMatched 
              ? 'bg-emerald-50 text-emerald-900 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-200' 
              : 'bg-amber-50 text-amber-900 ring-amber-600/30 dark:bg-amber-950/40 dark:text-amber-200')"
        >
          <div class="flex items-center gap-2.5">
            <i class="ph-bold text-lg" :class="totalGross === 0 ? 'ph-info text-blue-600' : (isMatched ? 'ph-check-circle text-emerald-600' : 'ph-warning text-amber-600')"></i>
            <div>
              <span class="font-bold text-xs uppercase tracking-wider block" x-text="totalGross === 0 ? '3-Way Verification Ready' : (isMatched ? '3-Way Match Verified (100% Consistent)' : 'Price Variance Detected')"></span>
              <span class="text-[11px] text-slate-600 dark:text-slate-300" x-text="totalGross === 0 
                ? 'Add bill line items below. PO and delivery amounts will auto-synchronize to ensure 100% match.'
                : (isMatched 
                  ? 'Purchase order limit, goods receipt, and supplier invoice totals match perfectly.' 
                  : 'Variance between PO (₱' + poAmount.toFixed(2) + ') and Bill Total (₱' + totalGross.toFixed(2) + ') is ₱' + Math.abs(priceVariance).toFixed(2) + '. Will require managerial approval.')"></span>
            </div>
          </div>

          <div class="font-mono text-xs sm:text-sm font-bold flex items-center gap-2">
            <span class="text-slate-500 dark:text-slate-400">Variance:</span>
            <span 
              class="px-2 py-0.5 rounded-lg text-xs" 
              :class="Math.abs(priceVariance) < 0.001 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300'"
              x-text="'₱' + Math.abs(priceVariance).toFixed(2)"
            ></span>
          </div>
        </div>
      </div>

      <!-- Line Items Repeater -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Bill Items &amp; Tax Withholding</span>
          <button type="button" @click="addItem()" class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
            <i class="ph-bold ph-plus"></i> Add Line
          </button>
        </div>

        <div class="space-y-2.5 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
          <template x-for="(it, idx) in items" :key="idx">
            <div class="grid grid-cols-12 gap-2.5 items-center rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200 dark:bg-slate-800/40 dark:ring-slate-700">
              <div class="col-span-12 sm:col-span-4">
                <input type="text" :name="'items[' + idx + '][description]'" x-model="it.description" placeholder="Item description..." required class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
              </div>

              <div class="col-span-6 sm:col-span-3">
                <select :name="'items[' + idx + '][expense_type]'" x-model="it.expense_type" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
                  <option value="GOODS_INVENTORY">Goods / Inventory</option>
                  <option value="SERVICES_MAINTENANCE">Services &amp; Maintenance</option>
                  <option value="SPACE_RENTAL">Space Rental</option>
                  <option value="DOCTOR_PROFESSIONAL_FEE">Doctor PF</option>
                  <option value="EXEMPT">Non-Taxable</option>
                </select>
              </div>

              <div class="col-span-6 sm:col-span-2">
                <select :name="'items[' + idx + '][atc_code]'" x-model="it.atc_code" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs font-mono ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
                  <option value="WI158">WI158 (1%)</option>
                  <option value="WI160">WI160 (2%)</option>
                  <option value="WC100">WC100 (5%)</option>
                  <option value="WI010">WI010 (10%)</option>
                  <option value="EXEMPT">EXEMPT (0%)</option>
                </select>
              </div>

              <div class="col-span-3 sm:col-span-1">
                <input type="number" step="1" min="1" :name="'items[' + idx + '][quantity]'" x-model.number="it.quantity" required class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
              </div>

              <div class="col-span-4 sm:col-span-1">
                <input type="number" step="0.01" min="0" :name="'items[' + idx + '][unit_price]'" x-model.number="it.unit_price" required class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
              </div>

              <div class="col-span-5 sm:col-span-1 flex items-center justify-end gap-1">
                <span class="font-mono text-xs font-bold text-slate-800 dark:text-slate-200 tabular-nums truncate" x-text="'₱' + ((it.quantity||0)*(it.unit_price||0)).toFixed(2)"></span>
                <button type="button" @click="removeItem(idx)" :disabled="items.length <= 1" class="text-slate-400 hover:text-rose-600 disabled:opacity-20 p-1">
                  <i class="ph-bold ph-trash text-sm"></i>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Financial Totals Summary -->
      <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4 font-mono text-xs sm:text-sm">
        <div>
          <span class="text-slate-500 font-sans">Gross Total: </span>
          <strong class="text-slate-900 dark:text-white">₱<span x-text="totalGross.toFixed(2)"></span></strong>
        </div>
        <div>
          <span class="text-slate-500 font-sans">EWT 2307: </span>
          <strong class="text-rose-600 dark:text-rose-400">-₱<span x-text="totalEwt.toFixed(2)"></span></strong>
        </div>
        <div class="border-t sm:border-t-0 sm:border-l border-slate-200 sm:pl-4 pt-2 sm:pt-0">
          <span class="text-slate-500 font-sans">Net Payable to Vendor: </span>
          <strong class="text-emerald-600 dark:text-emerald-400 text-base">₱<span x-text="totalNet.toFixed(2)"></span></strong>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
        <button 
          type="button" 
          @click="$dispatch('close-modal', 'createBillModal')" 
          class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
        >
          <i class="ph-bold ph-check"></i>
          <span>Save &amp; Ingest Bill</span>
        </button>
      </div>
    </div>
  </x-modal>

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createBillModal' }));
  });
</script>
@endif

</div>
@endsection
