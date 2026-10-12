@props([
    'title',
    'value' => 0,
    'isCurrency' => true,
    'prefix' => '₱',
    'icon' => 'ph-receipt',
    'trend' => null,
    'trendUp' => true,
    'subtitle' => null,
    'badge' => null,
    'color' => 'emerald', // emerald, blue, purple, amber, rose, teal, indigo, slate
])

@php
    $colorMap = [
        'emerald' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-950/50',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'ring' => 'ring-emerald-500/20',
        ],
        'blue' => [
            'bg' => 'bg-blue-50 dark:bg-blue-950/50',
            'text' => 'text-blue-600 dark:text-blue-400',
            'ring' => 'ring-blue-500/20',
        ],
        'purple' => [
            'bg' => 'bg-purple-50 dark:bg-purple-950/50',
            'text' => 'text-purple-600 dark:text-purple-400',
            'ring' => 'ring-purple-500/20',
        ],
        'amber' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950/50',
            'text' => 'text-amber-600 dark:text-amber-400',
            'ring' => 'ring-amber-500/20',
        ],
        'rose' => [
            'bg' => 'bg-rose-50 dark:bg-rose-950/50',
            'text' => 'text-rose-600 dark:text-rose-400',
            'ring' => 'ring-rose-500/20',
        ],
        'teal' => [
            'bg' => 'bg-teal-50 dark:bg-teal-950/50',
            'text' => 'text-teal-600 dark:text-teal-400',
            'ring' => 'ring-teal-500/20',
        ],
        'indigo' => [
            'bg' => 'bg-indigo-50 dark:bg-indigo-950/50',
            'text' => 'text-indigo-600 dark:text-indigo-400',
            'ring' => 'ring-indigo-500/20',
        ],
        'slate' => [
            'bg' => 'bg-slate-100 dark:bg-slate-800',
            'text' => 'text-slate-600 dark:text-slate-300',
            'ring' => 'ring-slate-300/40 dark:ring-slate-700',
        ],
    ];

    $palette = $colorMap[$color] ?? $colorMap['emerald'];
    $iconName = str_starts_with($icon, 'ph-') ? $icon : 'ph-' . $icon;
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition-all hover:shadow-md dark:bg-slate-900 dark:ring-slate-800']) }}>
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $title }}</span>
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $palette['bg'] }} {{ $palette['text'] }} ring-1 {{ $palette['ring'] }}">
            <i class="ph-bold {{ $iconName }} text-lg"></i>
        </span>
    </div>
    <div class="mt-4 flex items-baseline justify-between gap-2">
        <div class="kpi-value font-sans text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
            @if($isCurrency)
                {{ $prefix }}{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}
            @else
                {{ is_numeric($value) ? number_format((float) $value) : $value }}
            @endif
        </div>
        <div class="flex flex-wrap items-center justify-end gap-1.5">
            @if($badge !== null)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $palette['bg'] }} {{ $palette['text'] }} ring-1 {{ $palette['ring'] }}">
                    {{ $badge }}
                </span>
            @endif
            @if($trend !== null)
                <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    <i class="ph-bold {{ $trendUp ? 'ph-trend-up' : 'ph-trend-down' }}"></i> {{ $trend }}
                </span>
            @endif
        </div>
    </div>
    @if($subtitle)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
    @endif
</div>
