@extends('layouts.app')

@section('title', 'Statements of Account (SOA) - Accounts Receivable | FMS')
@section('module', 'ar')
@section('page', 'statements')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Patient &amp; HMO Statements of Account (SOA)
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      @if($patientId || $admissionType)
        <a 
          href="{{ route('ar.statements') }}" 
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" 
          title="Clear patient filter and return to full patient directory"
        >
          <i class="ph-bold ph-arrow-counter-clockwise"></i>
          <span>Reset Directory View</span>
        </a>
      @endif
      @if($selectedAccount)
        <a 
          href="{{ route('ar.statements.export', ['patient_id' => $selectedAccount->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        >
          <i class="ph-bold ph-download-simple"></i>
          <span>Export CSV</span>
        </a>
        <a 
          href="{{ route('ar.statements.print', ['patient_id' => $selectedAccount->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
          target="_blank" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
        >
          <i class="ph-bold ph-printer"></i>
          <span>Print Official SOA</span>
        </a>
      @endif
    </div>
  </div>

  <!-- Statement Filter Builder Card -->
  <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <form method="GET" action="{{ route('ar.statements') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end" id="soaFilterForm">
      <div class="sm:col-span-3">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          <i class="ph-bold ph-buildings text-emerald-600 dark:text-emerald-400 mr-1"></i> Care Setting / Admission:
        </label>
        <select 
          name="admission_type" 
          id="admissionTypeFilter" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="" {{ empty($admissionType) ? 'selected' : '' }}>All Care Settings</option>
          <option value="OUTPATIENT" {{ strtoupper((string)($admissionType ?? '')) === 'OUTPATIENT' ? 'selected' : '' }}>Outpatient (OPD)</option>
          <option value="INPATIENT" {{ strtoupper((string)($admissionType ?? '')) === 'INPATIENT' ? 'selected' : '' }}>Inpatient (IPD)</option>
          <option value="EMERGENCY" {{ strtoupper((string)($admissionType ?? '')) === 'EMERGENCY' ? 'selected' : '' }}>Emergency (ER)</option>
        </select>
      </div>

      <div class="sm:col-span-5">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          <i class="ph-bold ph-user text-emerald-600 dark:text-emerald-400 mr-1"></i> Select Patient / Debtor Account:
        </label>
        <select 
          name="patient_id" 
          id="patientSelect" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          onchange="if(this.value) this.form.submit();" 
          required
        >
          <option value="" data-admission="">-- Choose Patient Account --</option>
          @foreach($accounts as $acc)
            @php
              $typeUpper = strtoupper((string)($acc->admission_type ?? 'INPATIENT'));
              $badgeText = match($typeUpper) {
                'OUTPATIENT' => 'OPD',
                'EMERGENCY'  => 'ER',
                default      => 'IPD',
              };
            @endphp
            <option value="{{ $acc->id }}" 
                    data-admission="{{ $typeUpper }}"
                    data-name="{{ strtolower($acc->full_name) }}"
                    data-mrn="{{ strtolower($acc->patient_id_number) }}"
                    {{ (string)$patientId === (string)$acc->id ? 'selected' : '' }}>
              [{{ $badgeText }}] {{ $acc->full_name }} ({{ $acc->patient_id_number }}) — Open: ₱{{ number_format((float) $acc->current_balance, 2) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          <i class="ph-bold ph-calendar text-emerald-600 dark:text-emerald-400 mr-1"></i> Period Start:
        </label>
        <input 
          type="date" 
          name="start_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ $startDate }}" 
          onchange="if(document.getElementById('patientSelect').value) this.form.submit();"
        >
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          <i class="ph-bold ph-calendar text-emerald-600 dark:text-emerald-400 mr-1"></i> Period End:
        </label>
        <input 
          type="date" 
          name="end_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ $endDate }}" 
          onchange="if(document.getElementById('patientSelect').value) this.form.submit();"
        >
      </div>
    </form>
  </div>

  @if($statement && $selectedAccount)
  <!-- Financial Summary KPI Bar -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Beginning Balance" 
      :value="$statement['beginning_balance'] ?? 0" 
      icon="ph-wallet" 
      color="slate" 
      subtitle="Forwarded from previous statement period"
    />
    <x-stat-card 
      title="Period Invoiced (Debits)" 
      :value="$statement['total_debits'] ?? 0" 
      icon="ph-receipt" 
      color="blue" 
      subtitle="Total new patient copay & charges"
    />
    <x-stat-card 
      title="Payments &amp; Credits" 
      :value="$statement['total_credits'] ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Discounts, payments, and settlements"
    />
    <x-stat-card 
      title="Ending Balance Due" 
      :value="$statement['ending_balance'] ?? 0" 
      icon="ph-warning-circle" 
      color="rose" 
      subtitle="Net outstanding payable by patient"
    />
  </div>


  <!-- Running-Balance Ledger Table Card -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <h2 class="text-sm font-bold text-slate-900 dark:text-white">
            Statement Ledger for {{ $selectedAccount->full_name }}
          </h2>
          @php
            $selType = strtoupper((string)($selectedAccount->admission_type ?? 'INPATIENT'));
          @endphp
          <x-status-badge status="info" label="{{ $selectedAccount->admission_type ?? 'Inpatient' }}" size="sm" />
        </div>
        <span class="text-xs font-mono text-slate-500 dark:text-slate-400">
          MRN: {{ $selectedAccount->patient_id_number }} | {{ $statement['start_date'] }} to {{ $statement['end_date'] }}
        </span>
      </div>
      <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
        {{ count($statement['movements']) }} Ledger Records
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Date</th>
            <th class="px-4 py-3.5">Transaction Type</th>
            <th class="px-4 py-3.5">Reference #</th>
            <th class="px-4 py-3.5">Particulars / Description</th>
            <th class="px-4 py-3.5 text-right">Charges / Debit</th>
            <th class="px-4 py-3.5 text-right">Payments / Credit</th>
            <th class="px-4 py-3.5 text-right">Running Balance</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <!-- Forwarded Row -->
          <tr class="bg-slate-50/40 dark:bg-slate-800/20 text-slate-500 dark:text-slate-400 italic">
            <td class="px-4 py-3">{{ $statement['start_date'] }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-mono font-medium bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                FORWARD
              </span>
            </td>
            <td class="px-4 py-3">—</td>
            <td class="px-4 py-3">Beginning Balance Forwarded</td>
            <td class="px-4 py-3 text-right font-mono">—</td>
            <td class="px-4 py-3 text-right font-mono">—</td>
            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">
              ₱{{ number_format((float) $statement['beginning_balance'], 2) }}
            </td>
          </tr>

          @forelse($statement['movements'] as $m)
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $m['date'] }}</td>
            <td class="px-4 py-3">
              @if($m['type'] === 'INVOICE')
                <x-status-badge status="blue" label="INVOICE" size="sm" />
              @elseif($m['type'] === 'PAYMENT')
                <x-status-badge status="active" label="PAYMENT" size="sm" />
              @elseif($m['type'] === 'CREDIT_NOTE')
                <x-status-badge status="purple" label="CREDIT NOTE" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $m['type'] }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 font-mono font-semibold text-blue-600 dark:text-blue-400">
              {{ $m['reference'] }}
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
              {{ $m['description'] }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-blue-600 dark:text-blue-400">
              {{ (float) $m['debit'] > 0 ? '₱' . number_format((float) $m['debit'], 2) : '—' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
              {{ (float) $m['credit'] > 0 ? '₱' . number_format((float) $m['credit'], 2) : '—' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-rose-600 dark:text-rose-400">
              ₱{{ number_format((float) $m['balance'], 2) }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              No transactions recorded for this patient within selected period.
            </td>
          </tr>
          @endforelse
        </tbody>
        <tfoot class="bg-slate-50/75 border-t border-slate-200 font-mono font-semibold text-xs dark:bg-slate-800/50 dark:border-slate-800">
          <tr>
            <td colspan="4" class="px-4 py-3 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-sans">PERIOD TOTALS:</td>
            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 tabular-nums">
              +₱{{ number_format((float) $statement['total_debits'], 2) }}
            </td>
            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 tabular-nums">
              -₱{{ number_format((float) $statement['total_credits'], 2) }}
            </td>
            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400 font-bold tabular-nums">
              ₱{{ number_format((float) $statement['ending_balance'], 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  @else
  <!-- Patient Accounts Directory Table (Shown when no individual patient is selected) -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <div>
        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
          <i class="ph-bold ph-users-three text-blue-500"></i> Patient Accounts Directory
        </h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
          Select a patient account below or use the filter bar above to inspect their itemized Statement of Account (SOA).
        </span>
      </div>
      <span class="inline-flex items-center rounded-xl bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-500/20 dark:bg-blue-950/40 dark:text-blue-300">
        {{ count($accounts) }} Patients Registered
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Patient / Debtor Name</th>
            <th class="px-4 py-3.5">MRN / Patient ID</th>
            <th class="px-4 py-3.5 text-center">Care Setting</th>
            <th class="px-4 py-3.5">HMO / Insurance Provider</th>
            <th class="px-4 py-3.5 text-right">Current Outstanding</th>
            <th class="px-4 py-3.5 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($accounts as $acc)
          <tr 
            class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer"
            onclick="window.location.href='{{ route('ar.statements', ['patient_id' => $acc->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}'"
          >
            <td class="px-4 py-3">
              <div class="font-bold text-slate-900 dark:text-white">{{ $acc->full_name }}</div>
              @if($acc->hmo_provider)
                <span class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                  <i class="ph-bold ph-shield-check text-emerald-500"></i> {{ $acc->hmo_provider }}
                </span>
              @endif
            </td>
            <td class="px-4 py-3 font-mono font-semibold text-blue-600 dark:text-blue-400">
              {{ $acc->patient_id_number }}
            </td>
            <td class="px-4 py-3 text-center">
              <x-status-badge status="info" label="{{ $acc->admission_type ?? 'Inpatient' }}" size="sm" />
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ $acc->hmo_provider ?? 'Self-Pay Patient' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums {{ (float)$acc->current_balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
              ₱{{ number_format((float) $acc->current_balance, 2) }}
            </td>
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
              <a 
                href="{{ route('ar.statements', ['patient_id' => $acc->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
              >
                <i class="ph-bold ph-receipt"></i>
                <span>View Full SOA</span>
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              No patient accounts found.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const admissionFilter = document.getElementById('admissionTypeFilter');
  const patientSelect = document.getElementById('patientSelect');

  function filterPatientDropdown() {
    if (!admissionFilter || !patientSelect) return;
    const selectedAdmission = admissionFilter.value.toUpperCase();
    const options = patientSelect.querySelectorAll('option');

    let currentOptionStillValid = false;

    options.forEach(opt => {
      if (!opt.value) return;
      const optAdmission = (opt.getAttribute('data-admission') || '').toUpperCase();
      if (!selectedAdmission || optAdmission === selectedAdmission) {
        opt.hidden = false;
        opt.disabled = false;
        if (opt.selected) currentOptionStillValid = true;
      } else {
        opt.hidden = true;
        opt.disabled = true;
        if (opt.selected) opt.selected = false;
      }
    });

    if (!currentOptionStillValid && patientSelect.value && selectedAdmission) {
      const firstVisible = Array.from(options).find(opt => opt.value && !opt.hidden);
      if (firstVisible) {
        firstVisible.selected = true;
      } else {
        patientSelect.value = '';
      }
    }
  }

  if (admissionFilter) {
    admissionFilter.addEventListener('change', filterPatientDropdown);
  }
});
</script>
@endpush
@endsection
