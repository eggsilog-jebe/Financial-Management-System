@extends('layouts.app')

@section('title', 'Fiscal Year Planning - Budget Management | FMS')
@section('module', 'budget')
@section('page', 'fiscal-planning')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Fiscal Year Planning &amp; Target Setting
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="alert('Exporting Master Fiscal Plan...');" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-download-simple text-emerald-600"></i>
        <span>Export Master Plan</span>
      </button>
      <button 
        type="button" 
        id="btnCreatePlan"
        @click="$dispatch('open-modal', 'createPlanModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Create Plan Draft</span>
      </button>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Master Allocated Budget" 
      :value="$totalAllocated ?? 0" 
      icon="ph-chart-line-up" 
      color="emerald" 
      subtitle="Approved expenditure pool"
    />
    <x-stat-card 
      title="Master Expensed / Spent" 
      :value="$totalSpent ?? 0" 
      icon="ph-calculator" 
      color="rose" 
      subtitle="Total realized expenses to date"
    />
    <x-stat-card 
      title="Remaining Budget Pool" 
      :value="$totalRemaining ?? 0" 
      icon="ph-percent" 
      color="blue" 
      subtitle="Unencumbered fiscal funds"
    />
    <x-stat-card 
      title="Active Fiscal Plans" 
      :value="($budgets ?? collect())->count()" 
      :isCurrency="false"
      icon="ph-files" 
      color="slate" 
      subtitle="Operational fiscal frameworks"
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
            <span>Fiscal Year:</span>
          </div>
          <select 
            id="fiscalYearSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Fiscal Years</option>
            <option value="2026">FY 2026 (Current)</option>
            <option value="2027">FY 2027 (Next)</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 ml-2">
            <span>Plan Status:</span>
          </div>
          <select 
            id="planStatusSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Plan Statuses</option>
            <option value="active master budget">Active Master Budget</option>
            <option value="under review">Under Review</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            id="planSearchInput" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search plan title, period, status..."
          >
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table id="planTable" class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Plan Title</th>
            <th class="px-4 py-3.5">Fiscal Period</th>
            <th class="px-4 py-3.5 text-right">Revenue Target (₱)</th>
            <th class="px-4 py-3.5 text-right">Expense Budget (₱)</th>
            <th class="px-4 py-3.5 text-right">Target Net Margin (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($plans ?? [] as $p)
          @php
            $pArr = is_array($p) ? $p : [
              'title' => $p->plan_name ?? 'N/A', 'sub' => $p->description ?? 'N/A',
              'period' => ($p->start_date ?? 'N/A') . ' - ' . ($p->end_date ?? 'N/A'),
              'year' => $p->fiscal_year ?? date('Y'),
              'revenue' => '₱' . number_format($p->projected_revenue ?? 0, 2),
              'expense' => '₱' . number_format($p->projected_expense ?? 0, 2),
              'margin' => '₱' . number_format(($p->projected_revenue ?? 0) - ($p->projected_expense ?? 0), 2),
              'status' => $p->status ?? 'Draft', 'status_badge' => 'bg-warning-subtle text-warning',
              'status_icon' => 'ph-clock', 'resolution' => $p->resolution_number ?? 'N/A',
            ];
          @endphp
          <tr 
            class="plan-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer" 
            data-year="{{ $pArr['year'] }}" 
            data-status="{{ strtolower($pArr['status']) }}" 
            onclick="openFiscalPlanDetailsModal({{ json_encode($pArr) }})"
          >
            <td class="px-4 py-3">
              <div class="font-bold text-slate-900 dark:text-white">{{ $pArr['title'] }}</div>
              <span class="text-[11px] text-slate-400">{{ $pArr['sub'] }}</span>
            </td>
            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $pArr['period'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $pArr['revenue'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">{{ $pArr['expense'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ $pArr['margin'] }}</td>
            <td class="px-4 py-3 text-center">
              @if(str_contains(strtolower($pArr['status']), 'active') || str_contains(strtolower($pArr['status']), 'approved'))
                <x-status-badge status="active" label="{{ $pArr['status'] }}" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $pArr['status'] }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
              <button 
                type="button" 
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 cursor-pointer" 
                title="View Plan Details" 
                onclick="openFiscalPlanDetailsModal({{ json_encode($pArr) }})"
              >
                <i class="ph-bold ph-eye"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-calendar-blank text-3xl mb-2 text-slate-400"></i>
              <p>No fiscal plans available in database.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Meta Footer -->
    <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span id="planSummaryText">Showing {{ count($plans ?? []) }} Fiscal Plans</span>
    </div>
  </div>
</div>

<!-- Modal: In-Depth Fiscal Plan Details (Executive Design) -->
<x-modal 
  id="fiscalPlanDetailsModal" 
  title="Fiscal Plan Details" 
  subtitle="Approved operating plan governance and board resolution audit data." 
  icon="ph-calendar-check" 
  iconVariant="blue" 
  size="lg" 
  submitText="Export Plan PDF" 
  submitIcon="ph-file-pdf"
>
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-700">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300" id="detailPlanYear">FY 2026</span>
          <span id="detailPlanStatus" class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
            Active Master Budget
          </span>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="detailPlanTitle">FY 2026 Approved Operating Master Plan</h3>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Revenue Target</span>
        <h4 class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums" id="detailPlanRevenue">₱120,000,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Expense Budget Cap</span>
        <h4 class="font-mono font-bold text-rose-600 dark:text-rose-400 text-sm tabular-nums" id="detailPlanExpense">₱85,000,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Projected Margin</span>
        <h4 class="font-mono font-bold text-blue-600 dark:text-blue-400 text-sm tabular-nums" id="detailPlanMargin">₱35,000,000.00</h4>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-calendar text-blue-600"></i> Fiscal Schedule &amp; Governance
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Effective Fiscal Period:</span>
        <span class="font-mono font-semibold text-slate-900 dark:text-white" id="detailPlanPeriod">Jan 01, 2026 - Dec 31, 2026</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">Board Resolution Reference:</span>
        <span class="font-mono font-bold text-blue-600 dark:text-blue-400" id="detailPlanResolution">RES-2025-99</span>
      </div>
    </div>

    <!-- Audit Trail & Segregation of Duties -->
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-shield-check text-emerald-600"></i> Audit Trail &amp; Board Approval Verification
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Board of Trustees Sign-off:</span>
        <span class="text-emerald-600 font-semibold flex items-center gap-1"><i class="ph-bold ph-check"></i> Unanimously Approved</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">System Log Stamp:</span>
        <span class="font-mono text-slate-400 text-[11px]">LOG-PLAN-2026-001 | {{ date('Y-m-d H:i:s') }} PST</span>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Create Plan Draft -->
<x-modal 
  id="createPlanModal" 
  title="Create Fiscal Year Plan Draft" 
  subtitle="Establish preliminary budget envelope for board review and departmental breakdown." 
  icon="ph-plus-circle" 
  iconVariant="blue" 
  size="lg" 
  formId="createPlanForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Save Plan Draft" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-8">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Master Plan Title <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          id="modalPlanTitle" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. FY 2027 Master Operating Plan" 
          required
        >
      </div>
      <div class="sm:col-span-4">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Fiscal Year <span class="text-rose-500">*</span>
        </label>
        <input 
          type="number" 
          id="modalPlanYear" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="2027" 
          required
        >
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Target Revenue (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            id="modalPlanRevenue" 
            step="0.01" 
            min="0" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-emerald-600 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-emerald-400" 
            placeholder="0.00" 
            value="130000000.00" 
            required
          >
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Approved Expense Budget Cap (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            id="modalPlanExpense" 
            step="0.01" 
            min="0" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-rose-600 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-rose-400" 
            placeholder="0.00" 
            value="90000000.00" 
            required
          >
        </div>
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openFiscalPlanDetailsModal(p) {
  if (!p) return;

  const elYear = document.getElementById('detailPlanYear');
  if (elYear) elYear.textContent = 'FY ' + (p.year || '2026');

  const elTitle = document.getElementById('detailPlanTitle');
  if (elTitle) elTitle.textContent = p.title || 'Plan Title';

  const elRev = document.getElementById('detailPlanRevenue');
  if (elRev) elRev.textContent = p.revenue || '₱0.00';

  const elExp = document.getElementById('detailPlanExpense');
  if (elExp) elExp.textContent = p.expense || '₱0.00';

  const elMargin = document.getElementById('detailPlanMargin');
  if (elMargin) elMargin.textContent = p.margin || '₱0.00';

  const elPeriod = document.getElementById('detailPlanPeriod');
  if (elPeriod) elPeriod.textContent = p.period || '-';

  const elRes = document.getElementById('detailPlanResolution');
  if (elRes) elRes.textContent = p.resolution || 'RES-000';

  const statusEl = document.getElementById('detailPlanStatus');
  if (statusEl) {
    statusEl.textContent = p.status;
  }

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'fiscalPlanDetailsModal' }));
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('planSearchInput');
  const fiscalYearSelect = document.getElementById('fiscalYearSelect');
  const planStatusSelect = document.getElementById('planStatusSelect');
  const summaryText = document.getElementById('planSummaryText');

  function filterPlans() {
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedYear = fiscalYearSelect ? fiscalYearSelect.value.toLowerCase() : '';
    const selectedStatus = planStatusSelect ? planStatusSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.plan-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
      const rowYear = row.getAttribute('data-year') || '';
      const rowStatus = row.getAttribute('data-status') || '';
      const rowText = row.textContent.toLowerCase();

      const matchYear = !selectedYear || rowYear === selectedYear;
      const matchStatus = !selectedStatus || rowStatus.includes(selectedStatus);
      const matchSearch = !searchQuery || rowText.includes(searchQuery);

      if (matchYear && matchStatus && matchSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (summaryText) {
      summaryText.textContent = `Showing ${visibleCount} Fiscal Plan${visibleCount !== 1 ? 's' : ''}`;
    }

    let emptyRow = document.getElementById('noPlanRow');
    const tbody = document.querySelector('#planTable tbody');
    if (visibleCount === 0) {
      if (!emptyRow && tbody) {
        emptyRow = document.createElement('tr');
        emptyRow.id = 'noPlanRow';
        emptyRow.innerHTML = `<td colspan="7" class="text-center py-8 text-slate-400"><i class="ph ph-magnifying-glass text-3xl block mb-2"></i>No fiscal plans found matching current filter.</td>`;
        tbody.appendChild(emptyRow);
      }
      if (emptyRow) emptyRow.style.display = '';
    } else if (emptyRow) {
      emptyRow.style.display = 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterPlans);
  }
  if (fiscalYearSelect) fiscalYearSelect.addEventListener('change', filterPlans);
  if (planStatusSelect) planStatusSelect.addEventListener('change', filterPlans);

  const createPlanForm = document.getElementById('createPlanForm');
  if (createPlanForm) {
    createPlanForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const titleVal = document.getElementById('modalPlanTitle').value;
      const yearVal = document.getElementById('modalPlanYear').value;
      const rawRev = parseFloat(document.getElementById('modalPlanRevenue').value || 0);
      const rawExp = parseFloat(document.getElementById('modalPlanExpense').value || 0);
      const rawMargin = rawRev - rawExp;
      const formattedRev = '₱' + rawRev.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const formattedExp = '₱' + rawExp.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const formattedMargin = '₱' + rawMargin.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const periodVal = `Jan 01, ${yearVal} - Dec 31, ${yearVal}`;

      const planObj = {
        title: titleVal,
        sub: 'Preliminary Board Proposal',
        period: periodVal,
        year: yearVal,
        revenue: formattedRev,
        expense: formattedExp,
        margin: formattedMargin,
        status: 'Under Review',
        status_badge: 'bg-warning-subtle text-warning',
        status_icon: 'ph-clock',
        resolution: 'DRAFT-NEW-' + Math.floor(10 + Math.random() * 90)
      };

      const tbody = document.querySelector('#planTable tbody');
      if (tbody) {
        const newRow = document.createElement('tr');
        newRow.className = 'plan-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer';
        newRow.setAttribute('data-year', yearVal);
        newRow.setAttribute('data-status', 'under review');
        newRow.onclick = function() { openFiscalPlanDetailsModal(planObj); };

        newRow.innerHTML = `
          <td class="px-4 py-3">
            <div class="font-bold text-slate-900 dark:text-white">${titleVal}</div>
            <span class="text-[11px] text-slate-400">Preliminary Board Proposal</span>
          </td>
          <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">${periodVal}</td>
          <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">${formattedRev}</td>
          <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">${formattedExp}</td>
          <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-blue-600 dark:text-blue-400">${formattedMargin}</td>
          <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Under Review</span></td>
          <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
            <button type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer" title="View Plan Details"><i class="ph-bold ph-eye"></i></button>
          </td>
        `;

        const eyeBtn = newRow.querySelector('button[title="View Plan Details"]');
        if (eyeBtn) {
          eyeBtn.onclick = function(ex) {
            ex.stopPropagation();
            openFiscalPlanDetailsModal(planObj);
          };
        }

        tbody.insertBefore(newRow, tbody.firstChild);
      }

      window.dispatchEvent(new CustomEvent('close-modal', { detail: 'createPlanModal' }));
      createPlanForm.reset();
      filterPlans();
    });
  }

  filterPlans();
});
</script>
@endpush
