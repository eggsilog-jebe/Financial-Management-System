<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statement of Account - {{ $statement['patient']->full_name }}</title>
  <x-favicon />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  @vite(['resources/css/app.css'])
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background-color: #fff !important; font-size: 11px; }
      .soa-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 p-4 sm:p-8 font-sans antialiased">
  <div class="max-w-4xl mx-auto">
    <!-- Print Button Toolbar -->
    <div class="flex items-center justify-between mb-6 no-print">
      <button 
        type="button" 
        onclick="window.history.back()" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-arrow-left"></i>
        <span>Back to Statements</span>
      </button>
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-printer"></i>
        <span>Print / Save as PDF</span>
      </button>
    </div>

    <!-- Official Statement Document -->
    <div class="soa-card rounded-2xl bg-white p-6 sm:p-10 shadow-sm ring-1 ring-slate-200/80">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6 mb-6">
        <div>
          <h1 class="text-xl font-extrabold tracking-tight text-slate-900 uppercase">
            Financial Management System
          </h1>
          <p class="text-xs text-slate-600 mt-0.5 font-medium">Novaliches, Quezon City, Metro Manila, Philippines</p>
        </div>
        <div class="text-left sm:text-right">
          <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold tracking-wider uppercase bg-rose-50 text-rose-700 ring-1 ring-rose-500/20 mb-2">
            Statement of Account
          </span>
          <div class="font-mono font-bold text-sm text-slate-900">SOA-{{ date('Ymd') }}-{{ $statement['patient']->id }}</div>
          <div class="text-xs text-slate-400 mt-0.5">Statement Date: {{ date('M d, Y') }}</div>
        </div>
      </div>

      <!-- Debtor Profile -->
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 mb-6 text-xs">
        <div class="sm:col-span-6">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Patient / Debtor Name</span>
          <div class="font-bold text-slate-900 text-sm">{{ $statement['patient']->full_name }}</div>
          <div class="text-xs text-slate-500 font-mono mt-0.5">MRN: {{ $statement['patient']->patient_id_number }}</div>
        </div>
        <div class="sm:col-span-3">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Statement Period</span>
          <div class="font-semibold text-slate-800">{{ $statement['start_date'] }} to {{ $statement['end_date'] }}</div>
          <div class="text-slate-500 mt-0.5">Care: {{ $statement['patient']->admission_type ?? 'Inpatient' }}</div>
        </div>
        <div class="sm:col-span-3">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">HMO / Policy Provider</span>
          <div class="font-semibold text-blue-600">{{ $statement['patient']->hmo_provider ?? 'Self-Pay' }}</div>
          <div class="text-rose-600 font-bold font-mono mt-0.5">Due: ₱{{ number_format((float) $statement['ending_balance'], 2) }}</div>
        </div>
      </div>

      <!-- Financial Metrics Summary Box -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 text-center">
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Beginning Bal</span>
          <span class="font-mono font-bold text-slate-900 text-xs tabular-nums">₱{{ number_format((float) $statement['beginning_balance'], 2) }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Invoiced Charges</span>
          <span class="font-mono font-bold text-blue-600 text-xs tabular-nums">+₱{{ number_format((float) $statement['total_debits'], 2) }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-3">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Payments &amp; Credits</span>
          <span class="font-mono font-bold text-emerald-600 text-xs tabular-nums">-₱{{ number_format((float) $statement['total_credits'], 2) }}</span>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-3">
          <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 block mb-1">Ending Balance Due</span>
          <span class="font-mono font-bold text-rose-700 text-sm tabular-nums">₱{{ number_format((float) $statement['ending_balance'], 2) }}</span>
        </div>
      </div>

      <!-- Ledger Movements Table -->
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Chronological Transaction History</h2>
      <div class="overflow-x-auto rounded-xl border border-slate-200 mb-6">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
            <tr>
              <th class="px-3.5 py-2.5">Date</th>
              <th class="px-3.5 py-2.5">Type</th>
              <th class="px-3.5 py-2.5">Reference #</th>
              <th class="px-3.5 py-2.5">Particulars / Description</th>
              <th class="px-3.5 py-2.5 text-right">Charges</th>
              <th class="px-3.5 py-2.5 text-right">Credits</th>
              <th class="px-3.5 py-2.5 text-right">Balance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr class="bg-slate-50/40 text-slate-500 italic">
              <td class="px-3.5 py-2">{{ $statement['start_date'] }}</td>
              <td class="px-3.5 py-2">
                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600">FORWARD</span>
              </td>
              <td class="px-3.5 py-2">—</td>
              <td class="px-3.5 py-2">Beginning Balance Forwarded</td>
              <td class="px-3.5 py-2 text-right font-mono">—</td>
              <td class="px-3.5 py-2 text-right font-mono">—</td>
              <td class="px-3.5 py-2 text-right font-mono font-bold text-slate-900 tabular-nums">₱{{ number_format((float) $statement['beginning_balance'], 2) }}</td>
            </tr>
            @forelse($statement['movements'] as $m)
            <tr>
              <td class="px-3.5 py-2">{{ $m['date'] }}</td>
              <td class="px-3.5 py-2">
                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700">{{ $m['type'] }}</span>
              </td>
              <td class="px-3.5 py-2 font-mono font-semibold text-blue-600">{{ $m['reference'] }}</td>
              <td class="px-3.5 py-2 text-slate-700">{{ $m['description'] }}</td>
              <td class="px-3.5 py-2 text-right font-mono text-blue-600 tabular-nums">
                {{ (float)$m['debit'] > 0 ? '₱' . number_format((float)$m['debit'], 2) : '—' }}
              </td>
              <td class="px-3.5 py-2 text-right font-mono text-emerald-600 tabular-nums">
                {{ (float)$m['credit'] > 0 ? '₱' . number_format((float)$m['credit'], 2) : '—' }}
              </td>
              <td class="px-3.5 py-2 text-right font-mono font-bold text-rose-600 tabular-nums">
                ₱{{ number_format((float)$m['balance'], 2) }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="px-3.5 py-6 text-center text-slate-400">No transactions in period.</td>
            </tr>
            @endforelse
          </tbody>
          <tfoot class="bg-slate-50 border-t border-slate-200 font-mono font-bold text-xs">
            <tr>
              <td colspan="4" class="px-3.5 py-2.5 uppercase font-sans text-slate-600">TOTALS:</td>
              <td class="px-3.5 py-2.5 text-right text-blue-600 tabular-nums">₱{{ number_format((float) $statement['total_debits'], 2) }}</td>
              <td class="px-3.5 py-2.5 text-right text-emerald-600 tabular-nums">₱{{ number_format((float) $statement['total_credits'], 2) }}</td>
              <td class="px-3.5 py-2.5 text-right text-rose-600 tabular-nums">₱{{ number_format((float) $statement['ending_balance'], 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Payment Remittance Notice & Signatures -->
      <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 mb-8 text-xs text-slate-500">
        <strong>Payment Remittance Notice:</strong> Please present this statement at the Cashier Desk or remit via Online Banking/EFT. Checks must be made payable to <em>Financial Management System</em>.
      </div>

      <div class="grid grid-cols-3 gap-6 text-center text-xs text-slate-500 pt-6">
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Prepared by: Billing Clerk</div>
        </div>
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Audited by: AR Accountant</div>
        </div>
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Approved by: Finance Manager</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
