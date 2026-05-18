@props([
    'icon'    => '📦',
    'label'   => 'Label',
    'value'   => '0',
    'color'   => 'pink',   // pink | indigo | green | orange | purple
    'trend'   => null,     // opsional: "+12%" dll
])

@php
$colors = [
    'pink'   => ['bg'=>'bg-paw-50',   'border'=>'border-paw-100',   'icon'=>'bg-paw-100',   'text'=>'text-paw-600',   'value'=>'text-paw-700'],
    'indigo' => ['bg'=>'bg-den-50',   'border'=>'border-den-100',   'icon'=>'bg-den-100',   'text'=>'text-den-600',   'value'=>'text-den-700'],
    'green'  => ['bg'=>'bg-emerald-50','border'=>'border-emerald-100','icon'=>'bg-emerald-100','text'=>'text-emerald-600','value'=>'text-emerald-700'],
    'orange' => ['bg'=>'bg-orange-50', 'border'=>'border-orange-100', 'icon'=>'bg-orange-100', 'text'=>'text-orange-600', 'value'=>'text-orange-700'],
    'purple' => ['bg'=>'bg-purple-50', 'border'=>'border-purple-100', 'icon'=>'bg-purple-100', 'text'=>'text-purple-600', 'value'=>'text-purple-700'],
];
$c = $colors[$color] ?? $colors['pink'];
@endphp

<div class="flex items-center gap-4 p-5 rounded-3xl border {{ $c['bg'] }} {{ $c['border'] }} shadow-card hover:shadow-card-lg hover:-translate-y-1 transition-all duration-200 animate-fade-in">
    <div class="w-12 h-12 rounded-2xl {{ $c['icon'] }} flex items-center justify-center text-2xl shrink-0">
        {{ $icon }}
    </div>
    <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wider {{ $c['text'] }}">{{ $label }}</p>
        <p class="font-display font-extrabold text-2xl {{ $c['value'] }} mt-0.5">{{ $value }}</p>
        @if($trend)
            <p class="text-xs text-gray-400 font-semibold mt-0.5">{{ $trend }}</p>
        @endif
    </div>
</div>