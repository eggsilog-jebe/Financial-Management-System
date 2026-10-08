@extends('layouts.app')

@section('title', 'Malasakit Center Financial Assistance & Subsidies (RA 11463) | FMS')
@section('module', 'accounts-receivable')
@section('page', 'malasakit')

@section('content')
<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Malasakit Center Financial Assistance &amp; Subsidies
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'waterfallCalcModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 shadow-sm transition-all dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700"
      >
        <i class="ph-bold ph-calculator"></i>
        <span>Simulate Bill Waterfall</span>
      </button>
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'addGlModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-700 ring-1 ring-teal-600/20 transition-all"
      >
        <i class="ph-bold ph-plus"></i>
        <span>Register Guarantee Letter (GL)</span>
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

  @if($errors->any())
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-medium text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Total Subsidies Authorized" 
      :value="(float) $totalAuthorized" 
      icon="ph-file-text" 
      color="teal" 
      subtitle="Approved multi-agency GLs"
    />

    <x-stat-card 
      title="Total Utilized by Patients" 
      :value="(float) $totalUtilized" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Liquidated medical co-pays"
    />

    <x-stat-card 
      title="Remaining Active GL Funds" 
      :value="(float) $totalRemaining" 
      icon="ph-coins" 
      color="rose" 
      subtitle="Available patient credits"
    />

    <x-stat-card 
      title="Active Guarantee Letters" 
      :value="$activeGlCount" 
      :isCurrency="false"
      icon="ph-users-three" 
      color="amber" 
      subtitle="Open patient vouchers"
    />
  </div>

  <!-- Main Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ar.malasakit.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-bank"></i>
            <span>Agency:</span>
          </div>
          <select 
            name="agency" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Government Agencies</option>
            <option value="PCSO" @selected($agency === 'PCSO')>PCSO (Charity Fund)</option>
            <option value="DSWD" @selected($agency === 'DSWD')>DSWD (AICS Program)</option>
            <option value="DOH_MAIP" @selected($agency === 'DOH_MAIP')>DOH-MAIP (Indigent Grant)</option>
            <option value="LGU" @selected($agency === 'LGU')>LGU / Mayor / Governor</option>
            <option value="OTHER" @selected($agency === 'OTHER')>Office of the President / Other</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Statuses</option>
            <option value="ACTIVE" @selected($status === 'ACTIVE')>ACTIVE (Funds Available)</option>
            <option value="APPLIED" @selected($status === 'APPLIED')>APPLIED</option>
            <option value="DEPLETED" @selected($status === 'DEPLETED')>DEPLETED</option>
            <option value="EXPIRED" @selected($status === 'EXPIRED')>EXPIRED</option>
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
              value="{{ $search }}" 
              placeholder="Search GL #, patient, MRN..." 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
          </div>
          <button type="submit" class="rounded-xl bg-teal-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-700">
            Filter
          </button>
          @if(request()->hasAny(['agency', 'status', 'search']))
            <a href="{{ route('ar.malasakit.index') }}" class="rounded-xl px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-300">
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
            <th class="py-3 px-4 font-mono">GL Reference No.</th>
            <th class="py-3 px-4 text-center">Issuing Agency</th>
            <th class="py-3 px-4">Patient Name &amp; MRN</th>
            <th class="py-3 px-4">Diagnosis / Case</th>
            <th class="py-3 px-4 text-right font-mono">Authorized</th>
            <th class="py-3 px-4 text-right font-mono">Utilized</th>
            <th class="py-3 px-4 text-right font-mono">Remaining</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-right font-mono">Issued Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($letters as $letter)
            <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
              <td class="py-3.5 px-4 font-mono font-bold text-teal-700 dark:text-teal-400">
                {{ $letter->gl_number }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <x-status-badge status="MALASAKIT" :label="$letter->issuing_agency" />
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $letter->patientAccount?->full_name ?? '—' }}</div>
                <div class="font-mono text-xs text-slate-400">MRN: {{ $letter->patientAccount?->patient_id_number ?? 'N/A' }}</div>
              </td>
              <td class="py-3.5 px-4 text-xs text-slate-500 max-w-xs truncate" title="{{ $letter->diagnosis }}">
                {{ $letter->diagnosis ?: 'Clinical Admission' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                ₱{{ number_format((float) $letter->authorized_amount, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500">
                ₱{{ number_format((float) $letter->utilized_amount, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                ₱{{ number_format((float) $letter->remaining_amount, 2) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <x-status-badge :status="$letter->status" />
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500">
                {{ $letter->issued_date->format('M d, Y') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-hand-coins text-3xl mb-2 block mx-auto text-slate-300"></i>
                <p>No Guarantee Letters registered yet.</p>
                <button 
                  type="button" 
                  @click="$dispatch('open-modal', 'addGlModal')"
                  class="mt-3 inline-flex items-center gap-1 rounded-xl bg-teal-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-700"
                >
                  <i class="ph-bold ph-plus"></i>
                  <span>Register First Guarantee Letter</span>
                </button>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    @if($letters->hasPages())
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $letters->links() }}
      </div>
    @else
      <div class="border-t border-slate-200 p-4 bg-slate-50/50 flex items-center justify-between text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-900/50">
        <span>Showing {{ $letters->total() }} records</span>
        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
          <i class="ph-bold ph-shield-check text-teal-600"></i>
          <span>Multi-Agency Malasakit Desk (RA 11463)</span>
        </span>
      </div>
    @endif
  </div>

  <!-- Modal 1: Register Guarantee Letter -->
  <x-modal 
    id="addGlModal" 
    title="Register New Guarantee Letter (GL)" 
    subtitle="Ingest medical assistance commitment from PCSO, DSWD, MAIP, or LGU" 
    icon="ph-file-plus" 
    iconVariant="teal" 
    size="xl" 
    formAction="{{ route('ar.malasakit.store') }}" 
    formMethod="POST" 
    :showFooter="false"
  >
    <div class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Patient Account <span class="text-rose-500">*</span>
          </label>
          <select 
            name="patient_account_id" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            required
          >
            <option value="">-- Select Patient --</option>
            @foreach($patients as $pt)
              <option value="{{ $pt->id }}">
                {{ $pt->full_name }} (MRN: {{ $pt->patient_id_number }})
                @if($pt->is_nbb) [NBB] @endif
                @if($pt->discount_category) [{{ $pt->discount_category }}] @endif
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Issuing Government Agency <span class="text-rose-500">*</span>
          </label>
          <select 
            name="issuing_agency" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            required
          >
            <option value="PCSO">PCSO — Philippine Charity Sweepstakes Office</option>
            <option value="DSWD">DSWD — AICS Assistance to Individuals in Crisis</option>
            <option value="DOH_MAIP" selected>DOH-MAIP — Medical Assistance for Indigent Patients</option>
            <option value="LGU">LGU — City / Provincial Government</option>
            <option value="OTHER">Office of the President / Congressional GL</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            GL Reference Number <span class="text-rose-500">*</span>
          </label>
          <input 
            type="text" 
            name="gl_number" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="e.g. PCSO-2026-00412" 
            required
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Authorized Amount (₱) <span class="text-rose-500">*</span>
          </label>
          <input 
            type="number" 
            step="0.01" 
            min="1" 
            name="authorized_amount" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono text-right text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="25000.00" 
            required
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Date Issued <span class="text-rose-500">*</span>
          </label>
          <input 
            type="date" 
            name="issued_date" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            value="{{ date('Y-m-d') }}" 
            required
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Valid Until
          </label>
          <input 
            type="date" 
            name="valid_until" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            value="{{ date('Y-m-d', strtotime('+60 days')) }}"
          >
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Clinical Diagnosis / Indication
          </label>
          <input 
            type="text" 
            name="diagnosis" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="e.g. Community Acquired Pneumonia, Inpatient Medical Ward"
          >
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Remarks / Special Notes
          </label>
          <textarea 
            name="remarks" 
            rows="2" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Specific laboratory, pharmacy, or operative procedure covered..."
          ></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
        <button 
          type="button" 
          @click="$dispatch('close-modal', 'addGlModal')" 
          class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-700 ring-1 ring-teal-600/20"
        >
          <i class="ph-bold ph-check"></i>
          <span>Save &amp; Activate GL</span>
        </button>
      </div>
    </div>
  </x-modal>

  <!-- Modal 2: Interactive Malasakit Bill Waterfall Calculator -->
  <x-modal 
    id="waterfallCalcModal" 
    title="Philippine Public Hospital Bill Waterfall Simulation" 
    subtitle="Simulates legal order: Statutory Discounts &rarr; PhilHealth ACR &rarr; NBB Policy &rarr; Malasakit GLs" 
    icon="ph-calculator" 
    iconVariant="teal" 
    size="2xl" 
    :showFooter="false"
  >
    <div 
      x-data="{
        patientId: '',
        gross: 125000,
        philhealth: 38000,
        calculating: false,
        result: null,
        async runSimulation() {
          this.calculating = true;
          try {
            const resp = await fetch('{{ route('ar.malasakit.calculate') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                patient_account_id: this.patientId,
                gross_amount: this.gross,
                philhealth_amount: this.philhealth
              })
            });
            const data = await resp.json();
            this.result = data.waterfall;
          } catch (err) {
            alert('Simulation error: ' + err.message);
          } finally {
            this.calculating = false;
          }
        }
      }"
      class="space-y-4 text-xs"
    >
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="sm:col-span-3">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Patient Profile</label>
          <select 
            id="simPatient" 
            x-model="patientId" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">-- Choose Patient --</option>
            @foreach($patients as $pt)
              <option value="{{ $pt->id }}">
                {{ $pt->full_name }} (MRN: {{ $pt->patient_id_number }})
                @if($pt->is_nbb) [NBB Covered] @endif
                @if($pt->discount_category) [{{ $pt->discount_category }}] @endif
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Gross Bill (₱)</label>
          <input 
            type="number" 
            id="simGross" 
            x-model.number="gross" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono text-right text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">PhilHealth Case Rate (₱)</label>
          <input 
            type="number" 
            id="simPhilhealth" 
            x-model.number="philhealth" 
            class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono text-right text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <div class="flex items-end">
          <button 
            type="button" 
            id="btnRunSim" 
            @click="runSimulation()" 
            :disabled="calculating"
            class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-700 disabled:opacity-50"
          >
            <i class="ph-bold" :class="calculating ? 'ph-spinner animate-spin' : 'ph-play'"></i>
            <span x-text="calculating ? 'Calculating...' : 'Calculate Waterfall'"></span>
          </button>
        </div>
      </div>

      <!-- Simulation Output Panel -->
      <template x-if="result">
        <div id="simResult" class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 space-y-2.5">
          <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 uppercase text-[11px] tracking-wider text-slate-500 border-b border-slate-200 dark:border-slate-700 pb-2">
            <i class="ph-bold ph-receipt text-teal-600"></i>
            <span>Patient Statement Deduction Breakdown</span>
          </h4>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-600 dark:text-slate-400">Gross Hospital Incurred Charges:</span>
            <strong class="font-mono text-slate-900 dark:text-white" id="outGross" x-text="'₱' + Number(result.gross_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-700/60 text-purple-600 dark:text-purple-400">
            <span id="outDiscountLabel" x-text="'Less: ' + (result.discount_rule_applied !== 'NONE' ? result.discount_rule_applied : 'Statutory Discount') + ' (RA 9994/10754):'"></span>
            <strong class="font-mono" id="outDiscount" x-text="'− ₱' + (Number(result.vat_exempt_relief) + Number(result.statutory_discount)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-700/60 text-blue-600 dark:text-blue-400">
            <span>Less: PhilHealth Case Rate (RA 11223):</span>
            <strong class="font-mono" id="outPhilhealth" x-text="'− ₱' + Number(result.philhealth_deduction).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
          </div>

          <template x-if="result.is_nbb_covered && Number(result.nbb_subsidy_amount) > 0">
            <div id="rowNbb" class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-700/60 text-emerald-600 dark:text-emerald-400">
              <span>Less: No Balance Billing (NBB Policy Subsidy):</span>
              <strong class="font-mono" id="outNbb" x-text="'− ₱' + Number(result.nbb_subsidy_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
            </div>
          </template>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60 dark:border-slate-700/60 text-teal-600 dark:text-teal-400">
            <span>Less: Malasakit Guarantee Letters (PCSO/DSWD/MAIP):</span>
            <strong class="font-mono" id="outGls" x-text="'− ₱' + ((result.guarantee_letters_applied || []).reduce((acc, g) => acc + Number(g.amount), 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
          </div>

          <div class="flex justify-between items-center pt-2 text-sm font-bold">
            <span class="text-slate-900 dark:text-white">Final Patient Out-of-Pocket Balance:</span>
            <span 
              id="outNet" 
              class="font-mono text-base tabular-nums" 
              :class="Number(result.net_patient_payable) === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
              x-text="Number(result.net_patient_payable) === 0 ? '₱0.00 (FULLY SUBSIDIZED / ZERO BALANCE)' : '₱' + Number(result.net_patient_payable).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"
            ></span>
          </div>
        </div>
      </template>

      <div class="flex items-center justify-end pt-3 border-t border-slate-200 dark:border-slate-800">
        <button 
          type="button" 
          @click="$dispatch('close-modal', 'waterfallCalcModal')" 
          class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300"
        >
          Close
        </button>
      </div>
    </div>
  </x-modal>

</div>
@endsection
