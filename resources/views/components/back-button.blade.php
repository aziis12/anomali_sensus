@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-ios-secondary hover:text-ios-label bg-white border border-ios-border rounded-xl px-4 py-2 transition-colors']) }}>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
    </svg>
    {{ $slot }}
</a>