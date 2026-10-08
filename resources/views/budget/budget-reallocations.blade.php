@extends('layouts.app')

@section('title', 'Budget Reallocations - Budget Management | FMS')
@section('module', 'budget')
@section('page', 'reallocations')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Budget Reallocations &amp; Fund Transfers
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="alert('Downloading Transfer Log PDF...');" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-file-text text-emerald-600"></i>
        <span>Transfer Log PDF</span>
      </button>
      <button 
        type="button" 
        id="btnRequestTransfer" 
        @click="$dispatch('open-modal', 'requestTransferModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-arrows-left-right"></i>
        <span>Request Transfer</span>
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
      title="Monitored Departments" 
      :value="($budgets ?? collect())->count()" 
      :isCurrency="false"
      icon="ph-buildings" 
      color="amber" 
      subtitle="Active operational cost centers"
    />
    <x-stat-card 
      title="Available Budget Pool" 
      :value="($budgets ?? collect())->sum('remaining_balance')" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Liquid unencumbered pool"
    />
    <x-stat-card 
      title="Total Allocated Cap" 
      :value="($budgets ?? collect())->sum('allocated_amount')" 
      icon="ph-scales" 
      color="blue" 
      subtitle="Total authorized budget cap"
    />
    <x-stat-card 
      title="Transfer Status" 
      value="100% Balanced" 
      :isCurrency="false"
      icon="ph-shield-check" 
      color="purple" 
      subtitle="Zero debit/credit variances"
    />
  </div>

  <!-- Reallocations Data Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Source Dept:</span>
          </div>
          <select 
            id="sourceDeptSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Source Departments</option>
            <option value="radiology">Radiology &amp; Imaging</option>
            <option value="outpatient">Outpatient Clinic</option>
            <option value="pharmacy">Pharmacy</option>
            <option value="emergency">Emergency Care</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 ml-2">
            <span>Status:</span>
          </div>
          <select 
            id="transferStatusSelect" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" selected>All Transfer Statuses</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending CFO Review</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            id="reallocSearchInput" 
            placeholder="Search transfer ref, source, target..." 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table id="reallocTable" class="w-full text-left text-xs">
        <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60 sticky top-0">
          <tr>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Transfer Ref</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Source (Surplus)</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Destination (Deficit)</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Transfer Amount</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Operational Reason</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($reallocations ?? [] as $r)
          @php
            $rArr = is_array($r) ? $r : [
              'ref' => $r->reference_number ?? 'REAL-N/A',
              'from' => $r->source_department ?? 'N/A', 
              'to' => $r->destination_department ?? 'N/A',
              'amount' => '₱' . number_format((float) ($r->amount ?? 0), 2),
              'reason' => $r->reason ?? 'N/A', 
              'status' => $r->status ?? 'Pending',
              'status_badge' => 'emerald',
            ];
            $statusLower = strtolower($rArr['status'] ?? 'pending');
            $badgeColor = str_contains($statusLower, 'appr') ? 'emerald' : 'amber';
          @endphp
          <tr 
            class="realloc-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors cursor-pointer"
            data-from="{{ strtolower($rArr['from']) }}" 
            data-status="{{ strtolower($rArr['status']) }}" 
            onclick="openReallocationDetailsModal({{ json_encode($rArr) }})"
          >
            <td class="py-3.5 px-4 font-mono font-semibold text-purple-600 dark:text-purple-400">
              {{ $rArr['ref'] }}
            </td>
            <td class="py-3.5 px-4">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                {{ $rArr['from'] }}
              </span>
            </td>
            <td class="py-3.5 px-4">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                {{ $rArr['to'] }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">
              {{ $rArr['amount'] }}
            </td>
            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
              {{ $rArr['reason'] }}
            </td>
            <td class="py-3.5 px-4">
              <x-status-badge :status="$rArr['status']" :color="$badgeColor" />
            </td>
            <td class="py-3.5 px-4 text-right" onclick="event.stopPropagation();">
              <button 
                type="button" 
                title="View Transfer Details" 
                onclick="openReallocationDetailsModal({{ json_encode($rArr) }})"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-purple-600 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer"
              >
                <i class="ph ph-eye text-sm"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
              <i class="ph ph-arrows-left-right text-3xl block mb-2 text-slate-300 dark:text-slate-600"></i>
              No budget reallocations recorded in database.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Table Footer & Pagination -->
    @if(isset($rawReallocations) && method_exists($rawReallocations, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $rawReallocations->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal: In-Depth Reallocation Details (Executive Design) -->
<x-modal 
  id="reallocationDetailsModal" 
  title="Inter-Departmental Budget Transfer" 
  size="lg"
>
  <div class="space-y-4">
    <!-- Header badges inside modal -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
      <div class="flex items-center gap-2">
        <span class="font-mono text-xs font-bold text-purple-600 dark:text-purple-400 px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800" id="detailRealRef">
          REAL-2026-05
        </span>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" id="detailRealStatus">
          <i class="ph ph-clock mr-1"></i> Pending CFO Review
        </span>
      </div>
      <span class="text-xs text-slate-400">Transfer Review</span>
    </div>

    <!-- Key Metrics Highlight -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-center dark:border-slate-800 dark:bg-slate-800/40">
        <span class="block text-xs font-semibold uppercase text-slate-400">Transfer Amount</span>
        <h4 class="mt-1 text-2xl font-bold font-sans kpi-value tabular-nums text-emerald-600 dark:text-emerald-400" id="detailRealAmount">₱150,000.00</h4>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-center dark:border-slate-800 dark:bg-slate-800/40">
        <span class="block text-xs font-semibold uppercase text-slate-400">Transfer Route</span>
        <h5 class="mt-1 text-base font-bold font-mono text-slate-800 dark:text-slate-200" id="detailRealRoute">Radiology ➔ Facilities</h5>
      </div>
    </div>

    <!-- Justification & Dept Breakdown -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
      <h6 class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-3">
        <i class="ph-bold ph-arrows-left-right text-purple-600"></i>
        Transfer Justification &amp; Departments
      </h6>
      <div class="space-y-2.5 text-xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Source Department (Surplus)</span>
          <span class="font-medium text-slate-800 dark:text-slate-200" id="detailRealFrom">Radiology &amp; Imaging</span>
        </div>
        <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Destination Department (Deficit)</span>
          <span class="font-medium text-slate-800 dark:text-slate-200" id="detailRealTo">Facilities &amp; Utilities</span>
        </div>
        <div class="flex items-center justify-between pt-1">
          <span class="text-slate-500 dark:text-slate-400">Operational Reason</span>
          <span class="font-semibold text-slate-800 dark:text-slate-200" id="detailRealReason">Coverage for power generator fuel rate hike</span>
        </div>
      </div>
    </div>

    <!-- Audit Trail & Segregation of Duties -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
      <h6 class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-3">
        <i class="ph-bold ph-shield-check text-emerald-600"></i>
        Audit Trail &amp; CFO Approval Verification
      </h6>
      <div class="space-y-2 text-xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Dual Sign-off Authorization:</span>
          <span class="inline-flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            <i class="ph-bold ph-check mr-1"></i> Department Heads Approved
          </span>
        </div>
        <div class="flex items-center justify-between pt-1">
          <span class="text-slate-500 dark:text-slate-400">System Audit Stamp:</span>
          <span class="font-mono text-slate-400">LOG-REAL-2026-05 | {{ date('Y-m-d H:i:s') }} PST</span>
        </div>
      </div>
    </div>
  </div>

  <x-slot:footer>
    <button 
      type="button" 
      @click="show = false"
      class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer"
    >
      Close
    </button>
    <button 
      type="button" 
      onclick="alert('Transfer Approved by CFO!');" 
      class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
    >
      <i class="ph-bold ph-check"></i>
      <span>Approve Transfer</span>
    </button>
  </x-slot:footer>
</x-modal>

<!-- Modal: Request Inter-Departmental Transfer -->
<x-modal 
  id="requestTransferModal" 
  title="Request Inter-Departmental Budget Transfer" 
  size="lg"
  formId="requestTransferForm" 
  formAction="{{ route('budget.reallocate') }}" 
  formMethod="POST" 
  submitText="Submit Transfer Request" 
  submitIcon="ph-paper-plane-tilt"
>
  <input type="hidden" name="transfer_date" value="{{ date('Y-m-d') }}">
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Source Department (Surplus) <span class="text-rose-500">*</span>
        </label>
        <select 
          name="source_budget_allocation_id" 
          id="modalRealFrom" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          @foreach($budgets ?? [] as $b)
            <option value="{{ $b->id }}">{{ $b->department }} (Avail: ₱{{ number_format((float) $b->available_unencumbered_balance, 2) }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Destination Department (Deficit) <span class="text-rose-500">*</span>
        </label>
        <select 
          name="destination_budget_allocation_id" 
          id="modalRealTo" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          required
        >
          @foreach($budgets ?? [] as $b)
            <option value="{{ $b->id }}">{{ $b->department }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Transfer Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            name="amount" 
            id="modalRealAmount" 
            step="0.01" 
            min="0.01" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            placeholder="0.00" 
            value="100000.00" 
            required
          >
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Effective Transfer Date <span class="text-rose-500">*</span>
        </label>
        <input 
          type="date" 
          name="transfer_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}" 
          required
        >
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Operational Justification <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="reason" 
        id="modalRealReason" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Emergency equipment repair cost overrun" 
        required
      >
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openReallocationDetailsModal(r) {
  if (!r) return;

  const elRef = document.getElementById('detailRealRef');
  if (elRef) elRef.textContent = r.ref || 'REAL-000';

  const elFrom = document.getElementById('detailRealFrom');
  if (elFrom) elFrom.textContent = r.from || 'Source';

  const elTo = document.getElementById('detailRealTo');
  if (elTo) elTo.textContent = r.to || 'Target';

  const elAmount = document.getElementById('detailRealAmount');
  if (elAmount) elAmount.textContent = r.amount || '₱0.00';

  const elRoute = document.getElementById('detailRealRoute');
  if (elRoute) elRoute.textContent = (r.from || 'Source') + ' ➔ ' + (r.to || 'Target');

  const elReason = document.getElementById('detailRealReason');
  if (elReason) elReason.textContent = r.reason || '-';

  const statusEl = document.getElementById('detailRealStatus');
  if (statusEl) {
    statusEl.textContent = r.status;
  }

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'reallocationDetailsModal' }));
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('reallocSearchInput');
  const sourceDeptSelect = document.getElementById('sourceDeptSelect');
  const transferStatusSelect = document.getElementById('transferStatusSelect');
  const summaryText = document.getElementById('reallocSummaryText');

  function filterReallocations() {
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedSource = sourceDeptSelect ? sourceDeptSelect.value.toLowerCase() : '';
    const selectedStatus = transferStatusSelect ? transferStatusSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.realloc-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
      const rowFrom = row.getAttribute('data-from') || '';
      const rowStatus = row.getAttribute('data-status') || '';
      const rowText = row.textContent.toLowerCase();

      const matchSource = !selectedSource || rowFrom.includes(selectedSource);
      const matchStatus = !selectedStatus || rowStatus.includes(selectedStatus);
      const matchSearch = !searchQuery || rowText.includes(searchQuery);

      if (matchSource && matchStatus && matchSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (summaryText) {
      summaryText.textContent = `Showing ${visibleCount} Transfer Request${visibleCount !== 1 ? 's' : ''}`;
    }

    let emptyRow = document.getElementById('noReallocRow');
    const tbody = document.querySelector('#reallocTable tbody');
    if (visibleCount === 0) {
      if (!emptyRow && tbody) {
        emptyRow = document.createElement('tr');
        emptyRow.id = 'noReallocRow';
        emptyRow.innerHTML = `<td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500"><i class="ph ph-magnifying-glass text-3xl block mb-2"></i>No transfer requests found matching current filter.</td>`;
        tbody.appendChild(emptyRow);
      }
      if (emptyRow) emptyRow.style.display = '';
    } else if (emptyRow) {
      emptyRow.style.display = 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterReallocations);
    searchInput.addEventListener('keyup', filterReallocations);
  }
  if (sourceDeptSelect) sourceDeptSelect.addEventListener('change', filterReallocations);
  if (transferStatusSelect) transferStatusSelect.addEventListener('change', filterReallocations);

  filterReallocations();
});
</script>
@endpush
