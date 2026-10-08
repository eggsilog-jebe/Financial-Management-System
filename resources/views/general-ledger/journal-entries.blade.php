@extends('layouts.app')

@section('title', 'Journal Entries - General Ledger | FMS')
@section('module', 'gl')
@section('page', 'journal-entries')

@section('content')
<div class="space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Double-Entry Journal Entries
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('gl.ledger-books.export') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export Master GL</span>
      </a>
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'newJournalModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>New Journal Entry</span>
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
    <div class="rounded-xl bg-rose-50 p-4 text-xs text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <strong class="font-bold block mb-1">Please correct the following errors:</strong>
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Monthly Debit Volume" 
      :value="$monthlyDebitTotal ?? 0" 
      icon="ph-arrow-up-right" 
      color="emerald" 
      subtitle="Total validated debits"
    />

    <x-stat-card 
      title="Monthly Credit Volume" 
      :value="$monthlyCreditTotal ?? 0" 
      icon="ph-arrow-down-left" 
      color="blue" 
      subtitle="Total validated credits"
    />

    <x-stat-card 
      title="Posted to Ledger" 
      :value="$postedCount ?? 0" 
      :isCurrency="false"
      icon="ph-check-circle" 
      color="teal" 
      subtitle="Immutable posted records"
    />

    <x-stat-card 
      title="Draft / Pending Entries" 
      :value="$draftCount ?? 0" 
      :isCurrency="false"
      icon="ph-clock" 
      color="amber" 
      subtitle="Awaiting CFO posting"
    />
  </div>

  

  <!-- Data Table Card with Expandable Accordions -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('gl.journal-entries') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-funnel"></i>
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($selectedStatus) ? 'selected' : '' }}>All Statuses</option>
            <option value="POSTED" {{ ($selectedStatus ?? '') === 'POSTED' ? 'selected' : '' }}>POSTED</option>
            <option value="DRAFT" {{ ($selectedStatus ?? '') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
            <option value="REVERSED" {{ ($selectedStatus ?? '') === 'REVERSED' ? 'selected' : '' }}>REVERSED</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Type:</span>
          </div>
          <select 
            name="type" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($selectedType) ? 'selected' : '' }}>All Types</option>
            <option value="GENERAL" {{ ($selectedType ?? '') === 'GENERAL' ? 'selected' : '' }}>General Journal</option>
            <option value="ADJUSTING" {{ ($selectedType ?? '') === 'ADJUSTING' ? 'selected' : '' }}>Adjusting Entry</option>
            <option value="CLOSING" {{ ($selectedType ?? '') === 'CLOSING' ? 'selected' : '' }}>Closing Entry</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="q" 
            value="{{ $search ?? '' }}" 
            placeholder="Search entry ref, description..." 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th class="py-3 px-3 w-10"></th>
            <th class="py-3 px-4">Ref #</th>
            <th class="py-3 px-4">Date</th>
            <th class="py-3 px-4">Type</th>
            <th class="py-3 px-4">Narrative / Description</th>
            <th class="py-3 px-4 text-right font-mono">Total Debit</th>
            <th class="py-3 px-4 text-right font-mono">Total Credit</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4">Created By</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800" x-data="{ expandedRow: null }">
          @forelse($entries as $entry)
            @php
              $totalDebit = (float) $entry->lines->sum('debit');
              $totalCredit = (float) $entry->lines->sum('credit');
            @endphp
            <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
              <td class="py-3 px-3 text-center">
                <button 
                  type="button" 
                  @click="expandedRow = (expandedRow === {{ $entry->id }} ? null : {{ $entry->id }})" 
                  class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 transition-colors"
                  title="Expand Lines"
                >
                  <i class="ph-bold ph-caret-down text-xs transition-transform" :class="expandedRow === {{ $entry->id }} ? 'rotate-180' : ''"></i>
                </button>
              </td>
              <td class="py-3.5 px-4 font-mono font-semibold text-slate-900 dark:text-white">
                {{ $entry->reference_number }}
              </td>
              <td class="py-3.5 px-4 font-mono text-xs text-slate-500">
                {{ $entry->entry_date->format('Y-m-d') }}
              </td>
              <td class="py-3.5 px-4 text-xs">
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                  {{ $entry->type }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-medium text-slate-900 dark:text-white text-xs">{{ $entry->description }}</div>
                @if($entry->reversed_by_entry_id && $entry->reversedByEntry)
                  <span class="text-[11px] text-rose-600 dark:text-rose-400 flex items-center gap-1 mt-0.5">
                    <i class="ph-bold ph-arrow-u-down-left"></i> Reversed by {{ $entry->reversedByEntry->reference_number }}
                  </span>
                @endif
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">
                ₱{{ number_format($totalDebit, 2) }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">
                ₱{{ number_format($totalCredit, 2) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <x-status-badge :status="$entry->status" />
              </td>
              <td class="py-3.5 px-4 text-xs text-slate-500">
                {{ $entry->creator?->name ?? 'System' }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  @if($entry->status === 'DRAFT')
                    <form action="{{ route('gl.journal-entries.post', $entry->id) }}" method="POST" class="inline">
                      @csrf
                      <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <i class="ph-bold ph-check"></i> Post
                      </button>
                    </form>
                  @elseif($entry->status === 'POSTED')
                    <button 
                      type="button" 
                      @click="$dispatch('open-modal', { id: 'reverseModal', entryId: {{ $entry->id }}, ref: '{{ $entry->reference_number }}' })"
                      class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-600/20 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                      <i class="ph-bold ph-arrow-u-down-left"></i> Reverse
                    </button>
                  @else
                    <span class="text-xs text-slate-400">VOIDED</span>
                  @endif
                </div>
              </td>
            </tr>

            <!-- Expandable Line Items Details -->
            <tr x-show="expandedRow === {{ $entry->id }}" x-cloak class="bg-slate-50/75 dark:bg-slate-800/40">
              <td colspan="10" class="p-4 pl-12 pr-6">
                <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                  <div class="flex justify-between items-center mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                      Journal Entry Lines Breakdown (#{{ $entry->reference_number }})
                    </span>
                    <span class="text-xs text-slate-400">Posted: {{ $entry->posted_at ? $entry->posted_at->format('Y-m-d H:i') : 'Pending Post' }}</span>
                  </div>
                  <table class="w-full text-xs">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase">
                      <tr>
                        <th class="py-2 px-3 text-left">Account Code</th>
                        <th class="py-2 px-3 text-left">Account Title</th>
                        <th class="py-2 px-3 text-left">Memo</th>
                        <th class="py-2 px-3 text-right font-mono">Debit (₱)</th>
                        <th class="py-2 px-3 text-right font-mono">Credit (₱)</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                      @foreach($entry->lines as $line)
                        <tr>
                          <td class="py-2 px-3 font-bold text-slate-700 dark:text-slate-300">{{ $line->account->code ?? 'N/A' }}</td>
                          <td class="py-2 px-3 font-sans text-slate-800 dark:text-slate-200">{{ $line->account->name ?? 'Unknown Account' }}</td>
                          <td class="py-2 px-3 font-sans text-slate-500">{{ $line->memo ?? '—' }}</td>
                          <td class="py-2 px-3 text-right {{ (float)$line->debit > 0 ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-400' }}">
                            {{ (float)$line->debit > 0 ? number_format((float)$line->debit, 2) : '—' }}
                          </td>
                          <td class="py-2 px-3 text-right {{ (float)$line->credit > 0 ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-400' }}">
                            {{ (float)$line->credit > 0 ? number_format((float)$line->credit, 2) : '—' }}
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 dark:border-slate-800 font-bold">
                      <tr>
                        <td colspan="3" class="py-2 px-3 text-right font-sans text-slate-500">Assertion Total:</td>
                        <td class="py-2 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format($totalDebit, 2) }}</td>
                        <td class="py-2 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format($totalCredit, 2) }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-receipt-x text-3xl mb-2 block mx-auto text-slate-300"></i>
                No journal entries found matching criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      {{ $entries->links() }}
    </div>
  </div>

</div>

<!-- Modal: New Manual Journal Entry Builder (Interactive Alpine.js Double-Entry Invariance Validator) -->
<x-modal 
  id="newJournalModal" 
  title="Create Manual Journal Entry" 
  subtitle="Every voucher must strictly satisfy double-entry accounting invariance (Debits == Credits)" 
  icon="ph-receipt" 
  iconVariant="emerald" 
  size="3xl" 
  formAction="{{ route('gl.journal-entries.store') }}" 
  formMethod="POST" 
  formId="journalEntryForm"
  :showFooter="false"
>
  <div 
    x-data="{
      lines: [
        { account_id: '', memo: '', debit: '0.00', credit: '0.00' },
        { account_id: '', memo: '', debit: '0.00', credit: '0.00' }
      ],
      addLine() {
        this.lines.push({ account_id: '', memo: '', debit: '0.00', credit: '0.00' });
      },
      removeLine(index) {
        if (this.lines.length > 2) {
          this.lines.splice(index, 1);
        }
      },
      get totalDebit() {
        return this.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
      },
      get totalCredit() {
        return this.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
      },
      get isBalanced() {
        return Math.abs(this.totalDebit - this.totalCredit) < 0.0001 && this.totalDebit > 0;
      },
      get difference() {
        return Math.abs(this.totalDebit - this.totalCredit);
      }
    }" 
    class="space-y-5"
  >
    <!-- Header Details -->
    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 p-4 rounded-xl bg-slate-50 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700">
      <div class="sm:col-span-3">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Entry Date <span class="text-rose-500">*</span></label>
        <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
      </div>
      <div class="sm:col-span-3">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Journal Type <span class="text-rose-500">*</span></label>
        <select name="type" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
          <option value="GENERAL">GENERAL</option>
          <option value="ADJUSTING">ADJUSTING</option>
          <option value="CLOSING">CLOSING</option>
        </select>
      </div>
      <div class="sm:col-span-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Description / Narrative <span class="text-rose-500">*</span></label>
        <input type="text" name="description" placeholder="e.g. Monthly Accrual of Biomedical Oxygen Supplies" required class="w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
      </div>
    </div>

    <!-- Line Items Header -->
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
        <i class="ph-bold ph-list-plus text-emerald-600"></i>
        <span>Voucher Line Items (Debits &amp; Credits)</span>
      </span>
      <button 
        type="button" 
        @click="addLine()" 
        class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 transition-all"
      >
        <i class="ph-bold ph-plus"></i> Add Line
      </button>
    </div>

    <!-- Line Items Repeater -->
    <div class="space-y-2.5 max-h-[360px] overflow-y-auto custom-scrollbar pr-1">
      <template x-for="(line, index) in lines" :key="index">
        <div class="grid grid-cols-12 gap-2.5 items-center rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-200/80 dark:bg-slate-800/40 dark:ring-slate-700/60">
          <!-- Account Select (Col 5) -->
          <div class="col-span-12 sm:col-span-5">
            <label class="block text-[11px] font-semibold text-slate-400 mb-0.5">Account Code &amp; Title</label>
            <select 
              :name="'lines[' + index + '][account_id]'" 
              x-model="line.account_id" 
              required 
              class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 font-mono"
            >
              <option value="" disabled>Select Account...</option>
              @foreach($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }} ({{ $acc->category }})</option>
              @endforeach
            </select>
          </div>

          <!-- Memo (Col 3) -->
          <div class="col-span-12 sm:col-span-3">
            <label class="block text-[11px] font-semibold text-slate-400 mb-0.5">Line Memo</label>
            <input 
              type="text" 
              :name="'lines[' + index + '][memo]'" 
              x-model="line.memo" 
              placeholder="Memo description..." 
              class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
            >
          </div>

          <!-- Debit (Col 2) -->
          <div class="col-span-5 sm:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-400 mb-0.5 text-right">Debit (₱)</label>
            <input 
              type="number" 
              step="0.01" 
              min="0" 
              :name="'lines[' + index + '][debit]'" 
              x-model="line.debit" 
              @focus="if(line.debit === '0.00') line.debit = ''" 
              @blur="if(!line.debit) line.debit = '0.00'" 
              class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
            >
          </div>

          <!-- Credit (Col 2) -->
          <div class="col-span-5 sm:col-span-2">
            <div class="flex items-center justify-between mb-0.5">
              <label class="block text-[11px] font-semibold text-slate-400 text-right w-full">Credit (₱)</label>
            </div>
            <div class="flex items-center gap-1.5">
              <input 
                type="number" 
                step="0.01" 
                min="0" 
                :name="'lines[' + index + '][credit]'" 
                x-model="line.credit" 
                @focus="if(line.credit === '0.00') line.credit = ''" 
                @blur="if(!line.credit) line.credit = '0.00'" 
                class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
              >
              <button 
                type="button" 
                @click="removeLine(index)" 
                :disabled="lines.length <= 2" 
                class="text-slate-400 hover:text-rose-600 disabled:opacity-20 disabled:hover:text-slate-400 p-1"
                title="Remove Line"
              >
                <i class="ph-bold ph-trash text-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Live Invariance Verification Bar -->
    <div 
      class="rounded-xl p-4 ring-1 flex flex-col sm:flex-row items-center justify-between gap-3 transition-colors"
      :class="isBalanced ? 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-800'"
    >
      <div class="flex items-center gap-2.5">
        <i class="ph-bold text-xl" :class="isBalanced ? 'ph-check-circle text-emerald-600' : 'ph-warning-circle text-rose-600'"></i>
        <div>
          <span class="font-bold text-xs sm:text-sm block" x-text="isBalanced ? 'Balanced Journal Entry (Debit == Credit)' : 'Out of Balance by ₱' + difference.toFixed(2)"></span>
          <span class="text-[11px] opacity-80" x-text="isBalanced ? 'Entry strictly asserts double-entry invariance.' : 'Debits must equal credits before posting.'"></span>
        </div>
      </div>

      <div class="flex items-center gap-6 font-mono text-xs sm:text-sm tabular-nums">
        <div>
          <span class="text-xs opacity-75 mr-1 font-sans">Debits:</span>
          <strong class="font-bold">₱<span x-text="totalDebit.toFixed(2)"></span></strong>
        </div>
        <div>
          <span class="text-xs opacity-75 mr-1 font-sans">Credits:</span>
          <strong class="font-bold">₱<span x-text="totalCredit.toFixed(2)"></span></strong>
        </div>
      </div>
    </div>

    <!-- Modal Actions -->
    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
      <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
        <input type="checkbox" name="auto_post" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
        <span>Immediately Post to General Ledger</span>
      </label>

      <div class="flex items-center gap-2">
        <button 
          type="button" 
          @click="$dispatch('close-modal', 'newJournalModal')" 
          class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          :disabled="!isBalanced" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
        >
          <i class="ph-bold ph-shield-check"></i>
          <span>Submit Journal Entry</span>
        </button>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Reverse Journal Entry (With reason justification for CAS audit) -->
<x-modal 
  id="reverseModal" 
  title="Reverse Journal Entry" 
  subtitle="Generates an automated counter-balancing journal entry and records an immutable CAS audit trail" 
  icon="ph-arrow-u-down-left" 
  iconVariant="rose" 
  size="md" 
  :showFooter="false"
>
  <div 
    x-data="{
      entryId: null,
      ref: '',
      init() {
        window.addEventListener('open-modal', (e) => {
          if (typeof e.detail === 'object' && e.detail.id === 'reverseModal') {
            this.entryId = e.detail.entryId;
            this.ref = e.detail.ref;
          }
        });
      }
    }"
  >
    <form :action="'{{ url('/general-ledger/journal-entries') }}/' + entryId + '/reverse'" method="POST" class="space-y-4">
      @csrf
      <p class="text-xs text-slate-600 dark:text-slate-300">
        You are reversing posted journal entry <strong class="font-mono text-slate-900 dark:text-white" x-text="ref"></strong>. 
        This will reverse all lines, mark the original entry as <strong>REVERSED</strong>, and record an audit log.
      </p>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          Reason / Justification for Reversal <span class="text-rose-500">*</span>
        </label>
        <textarea 
          name="reason" 
          rows="3" 
          required 
          placeholder="State reason for reversal/adjustment..." 
          class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-rose-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        ></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
        <button 
          type="button" 
          @click="$dispatch('close-modal', 'reverseModal')" 
          class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700"
        >
          <i class="ph-bold ph-arrow-u-down-left"></i>
          <span>Confirm Reversal</span>
        </button>
      </div>
    </form>
  </div>
</x-modal>

@endsection
