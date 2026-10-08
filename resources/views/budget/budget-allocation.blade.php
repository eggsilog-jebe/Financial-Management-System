@extends('layouts.app')

@section('title', 'Budget Allocation - Budget Management | FMS')
@section('module', 'budget')
@section('page', 'budget-allocation')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Budget Category Allocation
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="alert('Viewing Allocation Matrix...');" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-sliders text-emerald-600"></i>
        <span>Allocation Matrix</span>
      </button>
      <button 
        type="button" 
        id="btnAllocateBudget"
        @click="$dispatch('open-modal', 'allocateBudgetModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Allocate Funds</span>
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

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total Fiscal Budget Cap" 
      :value="$totalAllocated ?? 0" 
      icon="ph-vault" 
      color="slate" 
      subtitle="Total authorized cost center funds"
    />
    <x-stat-card 
      title="Encumbered POs" 
      :value="$totalEncumbered ?? 0" 
      icon="ph-lock-key" 
      color="amber" 
      subtitle="Committed procurement requisitions"
    />
    <x-stat-card 
      title="Actual Expended Funds" 
      :value="$totalSpent ?? 0" 
      icon="ph-calculator" 
      color="rose" 
      subtitle="Realized departmental disbursements"
    />
    <x-stat-card 
      title="Remaining Free Capacity" 
      :value="(($totalRemaining ?? 0) - ($totalEncumbered ?? 0))" 
      icon="ph-hand-coins" 
      color="emerald" 
      subtitle="Unencumbered spendable balance"
    />
  </div>

  

  <!-- Data Table Card -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Cost Center:</span>
          </div>
          <select 
            id="costCenterSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Cost Centers</option>
            <option value="cc-101">CC-101 (Pharmacy)</option>
            <option value="cc-102">CC-102 (ICU Care)</option>
            <option value="cc-104">CC-104 (Facilities)</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 ml-2">
            <span>Category:</span>
          </div>
          <select 
            id="expenditureCatSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Categories</option>
            <option value="medical">Medical Supplies</option>
            <option value="equipment">Equipment Maintenance</option>
            <option value="utilities">Electric &amp; Power</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            id="allocationSearchInput" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search cost center, category..."
          >
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table id="allocationTable" class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Cost Center</th>
            <th class="px-4 py-3.5">Expenditure Category</th>
            <th class="px-4 py-3.5 text-right">Initial Cap (₱)</th>
            <th class="px-4 py-3.5 text-right">Encumbered POs (₱)</th>
            <th class="px-4 py-3.5 text-right">Actual Expended (₱)</th>
            <th class="px-4 py-3.5 text-right">Available Balance (₱)</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($allocations ?? [] as $a)
          @php
            $cc = is_array($a) ? $a['cc'] : $a->cost_center;
            $cat = is_array($a) ? $a['cat'] : $a->category;
            $sub = is_array($a) ? $a['sub'] : 'Category';
            $initial = is_array($a) ? $a['initial'] : ('₱' . number_format($a->allocated_amount, 2));
            $encumbered = is_array($a) ? $a['encumbered'] : ('₱' . number_format($a->encumbered_amount, 2));
            $expended = is_array($a) ? $a['expended'] : ('₱' . number_format($a->expended_amount, 2));
            $available = is_array($a) ? $a['available'] : ('₱' . number_format($a->allocated_amount - $a->expended_amount - $a->encumbered_amount, 2));
            $aData = [
              'cc' => $cc,
              'cat' => $cat,
              'sub' => $sub,
              'initial' => $initial,
              'encumbered' => $encumbered,
              'expended' => $expended,
              'available' => $available
            ];
          @endphp
          <tr 
            class="allocation-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer" 
            onclick="openAllocationDetailsModal({{ json_encode($aData) }})"
          >
            <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $cc }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $cat }}</div>
              <span class="text-[11px] text-slate-400">{{ $sub }}</span>
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-slate-900 dark:text-white">{{ $initial }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-amber-600 dark:text-amber-400">{{ $encumbered }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">{{ $expended }}</td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $available }}</td>
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
              <button 
                type="button" 
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 cursor-pointer" 
                onclick="openAllocationDetailsModal({{ json_encode($aData) }})"
              >
                <i class="ph-bold ph-eye"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-sliders text-3xl mb-2 text-slate-400"></i>
              <p>No budget allocations configured in database.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Meta Footer -->
    <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span id="allocationSummaryText">Showing {{ count($allocations ?? []) }} Budget Allocations</span>
    </div>
  </div>
</div>

<!-- Modal: In-Depth Allocation Details (Executive Design) -->
<x-modal 
  id="allocationDetailsModal" 
  title="Cost Center Allocation Details" 
  subtitle="Commitment control lock, PO encumbrances, and expenditure history." 
  icon="ph-sliders" 
  iconVariant="blue" 
  size="lg" 
  submitText="Export Allocation Report" 
  submitIcon="ph-file-text"
>
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-700">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300" id="detailAllocCc">CC-101</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
            Active Cost Center
          </span>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="detailAllocCat">Pharmacy Medical Supplies</h3>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Initial Cap</span>
        <h5 class="font-mono font-bold text-slate-900 dark:text-white text-xs tabular-nums" id="detailAllocInitial">₱25,000,000.00</h5>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Encumbered</span>
        <h5 class="font-mono font-bold text-amber-600 dark:text-amber-400 text-xs tabular-nums" id="detailAllocEncumbered">₱6,500,000.00</h5>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Actual Expended</span>
        <h5 class="font-mono font-bold text-rose-600 dark:text-rose-400 text-xs tabular-nums" id="detailAllocExpended">₱12,800,000.00</h5>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Free Balance</span>
        <h5 class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs tabular-nums" id="detailAllocAvailable">₱5,700,000.00</h5>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-sliders text-blue-600"></i> Sub-Account Breakdown
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Account Classification:</span>
        <span class="font-mono font-semibold text-slate-900 dark:text-white" id="detailAllocSub">Medical Supplies</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">Fiscal Year Assignment:</span>
        <span class="font-mono font-bold text-blue-600 dark:text-blue-400">FY 2026 Master Budget</span>
      </div>
    </div>

    <!-- Audit Trail & Segregation of Duties -->
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-shield-check text-emerald-600"></i> Audit Trail &amp; Encumbrance Control
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Commitment Control Lock:</span>
        <span class="text-emerald-600 font-semibold flex items-center gap-1"><i class="ph-bold ph-check"></i> Auto-Encumbrance Active</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">System Audit Log:</span>
        <span class="font-mono text-slate-400 text-[11px]">LOG-ALLOC-CC-101 | {{ date('Y-m-d H:i:s') }} PST</span>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Allocate Category Budget -->
<x-modal 
  id="allocateBudgetModal" 
  title="Allocate Budget to Cost Center" 
  subtitle="Assign approved expenditure cap from master budget to specific hospital cost center." 
  icon="ph-plus-circle" 
  iconVariant="blue" 
  size="lg" 
  formId="allocateBudgetForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Post Allocation" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Cost Center <span class="text-rose-500">*</span>
        </label>
        <select 
          id="modalAllocCc" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          <option value="CC-101">CC-101: Pharmacy &amp; Therapeutics</option>
          <option value="CC-102">CC-102: Emergency &amp; ICU Care</option>
          <option value="CC-104">CC-104: Facilities &amp; Utilities</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Expenditure Category <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          id="modalAllocCat" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. Medical Supplies &amp; Consumables" 
          required
        >
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Allocation Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            id="modalAllocAmount" 
            step="0.01" 
            min="0" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            placeholder="0.00" 
            value="15000000.00" 
            required
          >
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Effective Period</label>
        <select class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <option value="annual">Full Fiscal Year 2026</option>
        </select>
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openAllocationDetailsModal(a) {
  if (!a) return;

  const elCc = document.getElementById('detailAllocCc');
  if (elCc) elCc.textContent = a.cc || 'CC-000';

  const elCat = document.getElementById('detailAllocCat');
  if (elCat) elCat.textContent = a.cat || 'Category';

  const elSub = document.getElementById('detailAllocSub');
  if (elSub) elSub.textContent = a.sub || '-';

  const elInit = document.getElementById('detailAllocInitial');
  if (elInit) elInit.textContent = a.initial || '₱0.00';

  const elEnc = document.getElementById('detailAllocEncumbered');
  if (elEnc) elEnc.textContent = a.encumbered || '₱0.00';

  const elExp = document.getElementById('detailAllocExpended');
  if (elExp) elExp.textContent = a.expended || '₱0.00';

  const elAvail = document.getElementById('detailAllocAvailable');
  if (elAvail) elAvail.textContent = a.available || '₱0.00';

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'allocationDetailsModal' }));
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('allocationSearchInput');
  const costCenterSelect = document.getElementById('costCenterSelect');
  const expenditureCatSelect = document.getElementById('expenditureCatSelect');
  const summaryText = document.getElementById('allocationSummaryText');

  function filterAllocations() {
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedCc = costCenterSelect ? costCenterSelect.value.toLowerCase() : '';
    const selectedCat = expenditureCatSelect ? expenditureCatSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.allocation-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
      const rowCc = row.getAttribute('data-cc') || '';
      const rowCat = row.getAttribute('data-cat') || '';
      const rowText = row.textContent.toLowerCase();

      const matchCc = !selectedCc || rowCc.includes(selectedCc);
      const matchCat = !selectedCat || rowCat.includes(selectedCat);
      const matchSearch = !searchQuery || rowText.includes(searchQuery);

      if (matchCc && matchCat && matchSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (summaryText) {
      summaryText.textContent = `Showing ${visibleCount} Budget Allocation${visibleCount !== 1 ? 's' : ''}`;
    }

    let emptyRow = document.getElementById('noAllocationRow');
    const tbody = document.querySelector('#allocationTable tbody');
    if (visibleCount === 0) {
      if (!emptyRow && tbody) {
        emptyRow = document.createElement('tr');
        emptyRow.id = 'noAllocationRow';
        emptyRow.innerHTML = `<td colspan="7" class="text-center py-8 text-slate-400"><i class="ph ph-magnifying-glass text-3xl block mb-2"></i>No budget allocations found matching current filter.</td>`;
        tbody.appendChild(emptyRow);
      }
      if (emptyRow) emptyRow.style.display = '';
    } else if (emptyRow) {
      emptyRow.style.display = 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterAllocations);
  }
  if (costCenterSelect) costCenterSelect.addEventListener('change', filterAllocations);
  if (expenditureCatSelect) expenditureCatSelect.addEventListener('change', filterAllocations);

  const allocateBudgetForm = document.getElementById('allocateBudgetForm');
  if (allocateBudgetForm) {
    allocateBudgetForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const ccVal = document.getElementById('modalAllocCc').value;
      const catVal = document.getElementById('modalAllocCat').value;
      const rawInitial = parseFloat(document.getElementById('modalAllocAmount').value || 0);
      const formattedInitial = '₱' + rawInitial.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const formattedZero = '₱0.00';

      const allocObj = {
        cc: ccVal,
        cat: catVal,
        sub: 'Operational Expenditure Account',
        initial: formattedInitial,
        encumbered: formattedZero,
        expended: formattedZero,
        available: formattedInitial
      };

      const tbody = document.querySelector('#allocationTable tbody');
      if (tbody) {
        const newRow = document.createElement('tr');
        newRow.className = 'allocation-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer';
        newRow.setAttribute('data-cc', ccVal.toLowerCase());
        newRow.setAttribute('data-cat', catVal.toLowerCase());
        newRow.onclick = function() { openAllocationDetailsModal(allocObj); };

        newRow.innerHTML = `
          <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">${ccVal}</td>
          <td class="px-4 py-3">
            <div class="font-semibold text-slate-900 dark:text-white">${catVal}</div>
            <span class="text-[11px] text-slate-400">Operational Expenditure Account</span>
          </td>
          <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-slate-900 dark:text-white">${formattedInitial}</td>
          <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-amber-600 dark:text-amber-400">${formattedZero}</td>
          <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">${formattedZero}</td>
          <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">${formattedInitial}</td>
          <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
            <button type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer"><i class="ph-bold ph-eye"></i></button>
          </td>
        `;

        const eyeBtn = newRow.querySelector('button');
        if (eyeBtn) {
          eyeBtn.onclick = function(ex) {
            ex.stopPropagation();
            openAllocationDetailsModal(allocObj);
          };
        }

        tbody.insertBefore(newRow, tbody.firstChild);
      }

      window.dispatchEvent(new CustomEvent('close-modal', { detail: 'allocateBudgetModal' }));
      allocateBudgetForm.reset();
      filterAllocations();
    });
  }

  filterAllocations();
});
</script>
@endpush
