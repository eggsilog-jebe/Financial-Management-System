@extends('layouts.app')

@section('title', 'Variance Analysis - Budget Management | FMS')
@section('module', 'budget')
@section('page', 'variance-analysis')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Budget vs. Actual Variance Analysis
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="alert('Re-calculating real-time budget variances...');" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-arrows-counter-clockwise text-emerald-600"></i>
        <span>Re-Calculate Variances</span>
      </button>
      <button 
        type="button" 
        onclick="alert('Exporting Variance Audit PDF...');" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-file-arrow-down"></i>
        <span>Export Variance PDF</span>
      </button>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    @php
      $favorable = ($budgets ?? collect())->filter(fn($b) => ((float) $b->allocated_amount - (float) $b->spent_amount) > 0)->sum(fn($b) => (float) $b->allocated_amount - (float) $b->spent_amount);
      $unfavorable = ($budgets ?? collect())->filter(fn($b) => ((float) $b->allocated_amount - (float) $b->spent_amount) < 0)->sum(fn($b) => (float) $b->spent_amount - (float) $b->allocated_amount);
    @endphp
    <x-stat-card 
      title="Favorable Variances" 
      :value="$favorable" 
      icon="ph-trend-down" 
      color="emerald" 
      subtitle="Under-budget operational savings"
    />
    <x-stat-card 
      title="Unfavorable Variances" 
      :value="$unfavorable" 
      icon="ph-trend-up" 
      color="rose" 
      subtitle="Departmental budget overruns"
    />
    <x-stat-card 
      title="Net Budget Variance" 
      :value="$totalVariance ?? 0" 
      icon="ph-scales" 
      color="blue" 
      subtitle="Net variance equilibrium"
    />
    <x-stat-card 
      title="Monitored Units" 
      :value="($budgets ?? collect())->count()" 
      :isCurrency="false"
      icon="ph-warning-octagon" 
      color="amber" 
      subtitle="Cost centers under variance tracking"
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
            <span>Variance Type:</span>
          </div>
          <select 
            id="varianceTypeSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Variance Types</option>
            <option value="favorable">Favorable (Under Budget)</option>
            <option value="unfavorable">Unfavorable (Over Budget)</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 ml-2">
            <span>Department:</span>
          </div>
          <select 
            id="varianceDeptSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Departments</option>
            <option value="pharmacy">Pharmacy</option>
            <option value="facilities">Facilities &amp; Power</option>
            <option value="icu">ICU Care</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            id="varianceSearchInput" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search line item, cost center, status..."
          >
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table id="varianceTable" class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Expense Category / Line Item</th>
            <th class="px-4 py-3.5 text-right">Budgeted (₱)</th>
            <th class="px-4 py-3.5 text-right">Actual Realized (₱)</th>
            <th class="px-4 py-3.5 text-right">Variance (₱)</th>
            <th class="px-4 py-3.5 text-right">Variance %</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($variances ?? [] as $v)
          @php
            $vArr = is_array($v) ? $v : [
              'item' => $v->budget_item ?? 'N/A', 'cc' => $v->cost_center ?? 'N/A',
              'budget' => '₱' . number_format($v->budgeted_amount ?? 0, 2),
              'actual' => '₱' . number_format($v->actual_amount ?? 0, 2),
              'variance' => ($v->variance_amount >= 0 ? '+' : '') . '₱' . number_format($v->variance_amount ?? 0, 2),
              'pct' => ($v->variance_pct >= 0 ? '+' : '') . number_format($v->variance_pct ?? 0, 1) . '%',
              'pct_class' => ($v->variance_amount ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400',
              'status' => $v->status ?? 'N/A', 'status_badge' => 'bg-info-subtle text-info',
              'status_icon' => 'ph-chart-bar', 'type' => ($v->variance_amount ?? 0) >= 0 ? 'favorable' : 'unfavorable',
            ];
            $isFavorable = ($vArr['type'] === 'favorable');
          @endphp
          <tr 
            class="variance-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer" 
            data-type="{{ $vArr['type'] }}" 
            data-cc="{{ strtolower($vArr['cc']) }}" 
            onclick="openVarianceDetailsModal({{ json_encode($vArr) }})"
          >
            <td class="px-4 py-3">
              <div class="font-bold text-slate-900 dark:text-white">{{ $vArr['item'] }}</div>
              <span class="text-[11px] font-mono text-slate-400">Cost Center: {{ $vArr['cc'] }}</span>
            </td>
            <td class="px-4 py-3 text-right font-mono text-slate-600 dark:text-slate-300 tabular-nums">{{ $vArr['budget'] }}</td>
            <td class="px-4 py-3 text-right font-mono text-slate-600 dark:text-slate-300 tabular-nums">{{ $vArr['actual'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums {{ $isFavorable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
              {{ $vArr['variance'] }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums {{ $isFavorable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
              {{ $vArr['pct'] }}
            </td>
            <td class="px-4 py-3 text-center">
              @if($isFavorable)
                <x-status-badge status="active" label="Favorable" size="sm" />
              @else
                <x-status-badge status="inactive" label="Over Budget" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
              <button 
                type="button" 
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer" 
                title="View Variance Details" 
                onclick="openVarianceDetailsModal({{ json_encode($vArr) }})"
              >
                <i class="ph-bold ph-eye"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-scales text-3xl mb-2 text-slate-400"></i>
              <p>No variance records available in database.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Meta Footer -->
    <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span id="varianceSummaryText">Showing {{ count($variances ?? []) }} Variance Items</span>
    </div>
  </div>
</div>

<!-- Modal: In-Depth Variance Details (Executive Design) -->
<x-modal 
  id="varianceDetailsModal" 
  title="Variance Analysis Breakdown" 
  subtitle="Detailed variance tolerance, audit trail, and account code verification." 
  icon="ph-scales" 
  iconVariant="blue" 
  size="lg" 
  submitText="Export Line Audit" 
  submitIcon="ph-file-text"
>
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-700">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300" id="detailVarCc">CC-101 (Pharmacy)</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" id="detailVarStatus">
            Favorable (Under Budget)
          </span>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="detailVarItem">Pharmacy Medical Supplies &amp; Antibiotics</h3>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Budget Target</span>
        <h4 class="font-mono font-bold text-slate-900 dark:text-white text-sm tabular-nums" id="detailVarBudget">₱2,500,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Actual Spend</span>
        <h4 class="font-mono font-bold text-slate-900 dark:text-white text-sm tabular-nums" id="detailVarActual">₱2,280,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Variance</span>
        <h4 class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums" id="detailVarAmount">+₱220,000.00</h4>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-percent text-blue-600"></i> Variance Percentage &amp; Account Code
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Percentage Variance:</span>
        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" id="detailVarPct">+8.8%</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">Reporting Period:</span>
        <span class="font-mono text-slate-500">FY 2026 Year-To-Date</span>
      </div>
    </div>

    <!-- Audit Trail & Segregation of Duties -->
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-shield-check text-emerald-600"></i> Audit Trail &amp; Variance Verification
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Management Action Flag:</span>
        <span class="text-emerald-600 font-semibold flex items-center gap-1"><i class="ph-bold ph-check"></i> Variance within Acceptable Tolerance (&lt; 10%)</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">System Audit Stamp:</span>
        <span class="font-mono text-slate-400 text-[11px]">LOG-VAR-2026-CC101 | {{ date('Y-m-d H:i:s') }} PST</span>
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openVarianceDetailsModal(v) {
  if (!v) return;

  const elItem = document.getElementById('detailVarItem');
  if (elItem) elItem.textContent = v.item || 'Item Name';

  const elCc = document.getElementById('detailVarCc');
  if (elCc) elCc.textContent = v.cc || 'CC-000';

  const elBudget = document.getElementById('detailVarBudget');
  if (elBudget) elBudget.textContent = v.budget || '₱0.00';

  const elActual = document.getElementById('detailVarActual');
  if (elActual) elActual.textContent = v.actual || '₱0.00';

  const elAmt = document.getElementById('detailVarAmount');
  if (elAmt) elAmt.textContent = v.variance || '₱0.00';

  const elPct = document.getElementById('detailVarPct');
  if (elPct) elPct.textContent = v.pct || '0%';

  const statusEl = document.getElementById('detailVarStatus');
  if (statusEl) {
    statusEl.textContent = v.status;
  }

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'varianceDetailsModal' }));
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('varianceSearchInput');
  const typeSelect = document.getElementById('varianceTypeSelect');
  const deptSelect = document.getElementById('varianceDeptSelect');
  const summaryText = document.getElementById('varianceSummaryText');

  function filterVariances() {
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedType = typeSelect ? typeSelect.value.toLowerCase() : '';
    const selectedDept = deptSelect ? deptSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.variance-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
      const rowType = row.getAttribute('data-type') || '';
      const rowCc = row.getAttribute('data-cc') || '';
      const rowText = row.textContent.toLowerCase();

      const matchType = !selectedType || rowType.includes(selectedType);
      const matchDept = !selectedDept || rowCc.includes(selectedDept);
      const matchSearch = !searchQuery || rowText.includes(searchQuery);

      if (matchType && matchDept && matchSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (summaryText) {
      summaryText.textContent = `Showing ${visibleCount} Variance Item${visibleCount !== 1 ? 's' : ''}`;
    }

    let emptyRow = document.getElementById('noVarRow');
    const tbody = document.querySelector('#varianceTable tbody');
    if (visibleCount === 0) {
      if (!emptyRow && tbody) {
        emptyRow = document.createElement('tr');
        emptyRow.id = 'noVarRow';
        emptyRow.innerHTML = `<td colspan="7" class="text-center py-8 text-slate-400"><i class="ph ph-magnifying-glass text-3xl block mb-2"></i>No variance items found matching current filter.</td>`;
        tbody.appendChild(emptyRow);
      }
      if (emptyRow) emptyRow.style.display = '';
    } else if (emptyRow) {
      emptyRow.style.display = 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterVariances);
  }
  if (typeSelect) typeSelect.addEventListener('change', filterVariances);
  if (deptSelect) deptSelect.addEventListener('change', filterVariances);

  filterVariances();
});
</script>
@endpush
