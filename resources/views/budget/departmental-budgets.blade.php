@extends('layouts.app')

@section('title', 'Departmental Budgets - Budget Management | FMS')
@section('module', 'budget')
@section('page', 'departmental-budgets')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Departmental Operating Budgets
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="alert('Viewing Department Summary Chart...');" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-chart-pie-slice text-emerald-600"></i>
        <span>Department Summary</span>
      </button>
      <button 
        type="button" 
        id="btnEditDept"
        @click="$dispatch('open-modal', 'editDepartmentModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-pencil-line"></i>
        <span>Set Department Cap</span>
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
      title="Active Department Units" 
      :value="($budgets ?? collect())->count()" 
      :isCurrency="false"
      icon="ph-buildings" 
      color="slate" 
      subtitle="Operating clinical & non-clinical units"
    />
    <x-stat-card 
      title="Combined Department Caps" 
      :value="(float) ($budgets ?? collect())->sum('allocated_amount')" 
      icon="ph-vault" 
      color="blue" 
      subtitle="Total allocated departmental caps"
    />
    <x-stat-card 
      title="Total YTD Consumed" 
      :value="(float) ($budgets ?? collect())->sum('spent_amount')" 
      icon="ph-trend-up" 
      color="rose" 
      subtitle="Realized expenditures to date"
    />
    @php
      $allocTotal = (float) ($budgets ?? collect())->sum('allocated_amount');
      $spentTotal = (float) ($budgets ?? collect())->sum('spent_amount');
      $burnRate = $allocTotal > 0 ? round(($spentTotal / $allocTotal) * 100, 1) : 0;
    @endphp
    <x-stat-card 
      title="Hospital Burn Rate" 
      :value="$burnRate . '%'" 
      :isCurrency="false"
      icon="ph-gauge" 
      color="amber" 
      subtitle="YTD utilization velocity"
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
            <span>Wing / Unit:</span>
          </div>
          <select 
            id="wingSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Hospital Wings</option>
            <option value="cardiology">Clinical (Cardiology &amp; ICU)</option>
            <option value="pharmacy">Pharmacy &amp; Therapeutics</option>
            <option value="emergency">Emergency Operations</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 ml-2">
            <span>Burn Status:</span>
          </div>
          <select 
            id="burnRateSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Burn Statuses</option>
            <option value="normal">Normal (&lt; 75%)</option>
            <option value="high">High Burn (75% - 90%)</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            id="deptSearchInput" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search department, head, code..."
          >
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table id="deptTable" class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Department Name</th>
            <th class="px-4 py-3.5">Head of Department</th>
            <th class="px-4 py-3.5 text-right">Annual Cap (₱)</th>
            <th class="px-4 py-3.5 text-right">YTD Spent (₱)</th>
            <th class="px-4 py-3.5 text-right">Available Quota (₱)</th>
            <th class="px-4 py-3.5" style="min-width: 180px;">Burn Rate %</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($departments ?? [] as $d)
          @php
            $dArr = is_array($d) ? $d : [
              'code' => $d->department_code ?? 'N/A', 'name' => $d->name ?? 'N/A',
              'head' => $d->department_head ?? 'N/A',
              'cap' => '₱' . number_format($d->budget_cap ?? 0, 2),
              'spent' => '₱' . number_format($d->amount_spent ?? 0, 2),
              'available' => '₱' . number_format(($d->budget_cap ?? 0) - ($d->amount_spent ?? 0), 2),
              'burn' => number_format($d->burn_rate ?? 0, 1) . '%',
              'burn_val' => $d->burn_rate ?? 0, 'burn_class' => 'bg-emerald-600', 'burn_status' => 'normal',
            ];
            $burnPct = (float) $dArr['burn_val'];
            $barColor = $burnPct > 85 ? 'bg-rose-500' : ($burnPct > 70 ? 'bg-amber-500' : 'bg-emerald-600');
          @endphp
          <tr 
            class="dept-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer" 
            data-wing="{{ strtolower($dArr['name']) }}" 
            data-burn="{{ $dArr['burn_status'] }}" 
            onclick="openDepartmentDetailsModal({{ json_encode($dArr) }})"
          >
            <td class="px-4 py-3">
              <div class="font-bold text-slate-900 dark:text-white">{{ $dArr['name'] }}</div>
              <span class="text-[11px] font-mono text-slate-400">{{ $dArr['code'] }}</span>
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium">{{ $dArr['head'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-slate-900 dark:text-white">{{ $dArr['cap'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-rose-600 dark:text-rose-400">{{ $dArr['spent'] }}</td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $dArr['available'] }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2.5">
                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                  <div class="h-full rounded-full {{ $barColor }}" style="width: {{ min(100, $burnPct) }}%;"></div>
                </div>
                <span class="text-xs font-mono font-bold tabular-nums text-slate-700 dark:text-slate-300 min-w-[42px] text-right">{{ $dArr['burn'] }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
              <button 
                type="button" 
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 cursor-pointer" 
                title="View Department Details" 
                onclick="openDepartmentDetailsModal({{ json_encode($dArr) }})"
              >
                <i class="ph-bold ph-eye"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-buildings text-3xl mb-2 text-slate-400"></i>
              <p>No departmental budgets in database.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Meta Footer -->
    <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span id="deptSummaryText">Showing {{ count($departments ?? []) }} Departmental Budgets</span>
    </div>
  </div>
</div>

<!-- Modal: In-Depth Department Details (Executive Design) -->
<x-modal 
  id="departmentDetailsModal" 
  title="Departmental Budget Details" 
  subtitle="Department head spending cap, burn rate percentage, and quota utilization." 
  icon="ph-buildings" 
  iconVariant="blue" 
  size="lg" 
  submitText="Export Department Ledger" 
  submitIcon="ph-file-pdf"
>
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-700">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300" id="detailDeptCode">DEPT-ICU-01</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
            Active Department
          </span>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="detailDeptName">Cardiology &amp; ICU Care</h3>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Annual Cap Limit</span>
        <h4 class="font-mono font-bold text-slate-900 dark:text-white text-sm tabular-nums" id="detailDeptCap">₱22,000,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">YTD Spent</span>
        <h4 class="font-mono font-bold text-rose-600 dark:text-rose-400 text-sm tabular-nums" id="detailDeptSpent">₱12,700,000.00</h4>
      </div>
      <div class="rounded-xl bg-slate-50 p-3 text-center ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Available Quota</span>
        <h4 class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums" id="detailDeptAvailable">₱9,300,000.00</h4>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-user-gear text-blue-600"></i> Department Head &amp; Quota Utilization
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Head of Department:</span>
        <span class="font-semibold text-slate-900 dark:text-white" id="detailDeptHead">Dr. Alejandro Santos</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">Burn Rate Percentage:</span>
        <span class="font-mono font-bold text-blue-600 dark:text-blue-400" id="detailDeptBurn">57.7%</span>
      </div>
    </div>

    <!-- Audit Trail & Segregation of Duties -->
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2 text-xs">
      <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1">
        <i class="ph-bold ph-shield-check text-emerald-600"></i> Audit Trail &amp; Spending Cap Control
      </h5>
      <div class="flex justify-between border-b border-slate-200 pb-1.5 dark:border-slate-700">
        <span class="text-slate-500">Over-Budget Lock Protection:</span>
        <span class="text-emerald-600 font-semibold flex items-center gap-1"><i class="ph-bold ph-check"></i> Automatic PO Block Active</span>
      </div>
      <div class="flex justify-between pt-0.5">
        <span class="text-slate-500">System Audit Log:</span>
        <span class="font-mono text-slate-400 text-[11px]">LOG-DEPT-2026-ICU | {{ date('Y-m-d H:i:s') }} PST</span>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Edit Departmental Budget Cap -->
<x-modal 
  id="editDepartmentModal" 
  title="Adjust Departmental Budget Cap" 
  subtitle="Update maximum annual spending allocation for a hospital department." 
  icon="ph-pencil-line" 
  iconVariant="blue" 
  size="md" 
  formId="editDeptForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Update Annual Cap" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Target Department <span class="text-rose-500">*</span>
      </label>
      <select 
        id="modalDeptSelect" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        <option value="Cardiology & ICU Care">Cardiology &amp; ICU Care (Dr. Alejandro Santos)</option>
        <option value="Pharmacy & Medical Therapeutics">Pharmacy &amp; Medical Therapeutics (Pharm. Elena Rostova)</option>
        <option value="Emergency Room Operations">Emergency Room Operations (Dr. Marcus Vance)</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        New Annual Budget Cap (₱) <span class="text-rose-500">*</span>
      </label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
        <input 
          type="number" 
          id="modalDeptCap" 
          step="0.01" 
          min="0" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
          value="28000000.00" 
          required
        >
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openDepartmentDetailsModal(d) {
  if (!d) return;

  const elCode = document.getElementById('detailDeptCode');
  if (elCode) elCode.textContent = d.code || 'DEPT-000';

  const elName = document.getElementById('detailDeptName');
  if (elName) elName.textContent = d.name || 'Department Name';

  const elHead = document.getElementById('detailDeptHead');
  if (elHead) elHead.textContent = d.head || '-';

  const elCap = document.getElementById('detailDeptCap');
  if (elCap) elCap.textContent = d.cap || '₱0.00';

  const elSpent = document.getElementById('detailDeptSpent');
  if (elSpent) elSpent.textContent = d.spent || '₱0.00';

  const elAvail = document.getElementById('detailDeptAvailable');
  if (elAvail) elAvail.textContent = d.available || '₱0.00';

  const elBurn = document.getElementById('detailDeptBurn');
  if (elBurn) elBurn.textContent = d.burn || '0%';

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'departmentDetailsModal' }));
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('deptSearchInput');
  const wingSelect = document.getElementById('wingSelect');
  const burnRateSelect = document.getElementById('burnRateSelect');
  const summaryText = document.getElementById('deptSummaryText');

  function filterDepartments() {
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedWing = wingSelect ? wingSelect.value.toLowerCase() : '';
    const selectedBurn = burnRateSelect ? burnRateSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.dept-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
      const rowWing = row.getAttribute('data-wing') || '';
      const rowBurn = row.getAttribute('data-burn') || '';
      const rowText = row.textContent.toLowerCase();

      const matchWing = !selectedWing || rowWing.includes(selectedWing);
      const matchBurn = !selectedBurn || rowBurn.includes(selectedBurn);
      const matchSearch = !searchQuery || rowText.includes(searchQuery);

      if (matchWing && matchBurn && matchSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (summaryText) {
      summaryText.textContent = `Showing ${visibleCount} Departmental Budget${visibleCount !== 1 ? 's' : ''}`;
    }

    let emptyRow = document.getElementById('noDeptRow');
    const tbody = document.querySelector('#deptTable tbody');
    if (visibleCount === 0) {
      if (!emptyRow && tbody) {
        emptyRow = document.createElement('tr');
        emptyRow.id = 'noDeptRow';
        emptyRow.innerHTML = `<td colspan="7" class="text-center py-8 text-slate-400"><i class="ph ph-magnifying-glass text-3xl block mb-2"></i>No departmental budgets found matching current filter.</td>`;
        tbody.appendChild(emptyRow);
      }
      if (emptyRow) emptyRow.style.display = '';
    } else if (emptyRow) {
      emptyRow.style.display = 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterDepartments);
  }
  if (wingSelect) wingSelect.addEventListener('change', filterDepartments);
  if (burnRateSelect) burnRateSelect.addEventListener('change', filterDepartments);

  const editDeptForm = document.getElementById('editDeptForm');
  if (editDeptForm) {
    editDeptForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const nameVal = document.getElementById('modalDeptSelect').value;
      const rawCap = parseFloat(document.getElementById('modalDeptCap').value || 0);
      const formattedCap = '₱' + rawCap.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

      const rows = document.querySelectorAll('.dept-row');
      rows.forEach(function(r) {
        if (r.textContent.includes(nameVal)) {
          const capTd = r.children[2];
          if (capTd) capTd.textContent = formattedCap;
        }
      });

      window.dispatchEvent(new CustomEvent('close-modal', { detail: 'editDepartmentModal' }));
      alert(`Department budget cap for ${nameVal} updated to ${formattedCap}!`);
    });
  }

  filterDepartments();
});
</script>
@endpush
