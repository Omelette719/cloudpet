@props(['icon' => '🐾', 'title' => '', 'subtitle' => null, 'admin' => false])

<div class="flex items-start gap-3 mb-4">
    <span class="text-2xl">{{ $icon }}</span>
    <div>
        <h2 class="font-display font-extrabold text-lg {{ $admin ? 'text-den-800' : 'text-paw-700' }}">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="text-xs text-gray-400 font-semibold">{{ $subtitle }}</p>
        @endif
    </div>
</div>