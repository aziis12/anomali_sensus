@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih',
    'label' => null,
    'autoSubmit' => true,
    'optionTitles' => [],
    'colorMap' => [],
    'width' => null,   // default width kalau gak dikasih
])

@php
    $labelTerpilih = $options[$selected] ?? $placeholder;
@endphp

<div
    x-data="{
        open: false,
        value: @js($selected),
        label: @js($labelTerpilih),
        colorMap: @js($colorMap),
        get kelasWarna() {
            return this.colorMap[this.value] ?? '';
        },
        pilih(val, lbl) {
            this.value = val;
            this.label = lbl;
            this.open = false;
            @if ($autoSubmit)
                this.$nextTick(() => this.$el.closest('form').submit());
            @endif
        }
    }"
     @keydown.escape.window="open = false"
    {{ $attributes->merge(['class' => trim('min-w-0 relative shrink-0 ' . $width)]) }}
>
    @if ($label)
        <label class="block text-xs text-ios-secondary mb-1">{{ $label }}</label>
    @endif

    <button type="button"
        @click="open = !open"
        @click.outside="open = false"
        class="w-full truncate text-left border rounded-xl text-sm px-3 py-2 flex items-center justify-between gap-2 focus:ring-1 transition-colors"
        :class="kelasWarna || 'bg-white border-ios-border text-ios-label focus:border-ios-blue focus:ring-ios-blue'">
        <span class="truncate" x-text="label"></span>
        <svg class="w-4 h-4 shrink-0 text-ios-secondary transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <input type="hidden" name="{{ $name }}" :value="value">

    <div x-show="open" x-cloak
        x-transition
        class="absolute z-30 mt-1 w-max min-w-full max-w-xs bg-white border border-ios-border rounded-xl shadow-lg max-h-60 overflow-auto">
        @foreach ($options as $value => $labelOpsi)
            <button type="button"
                @click="pilih(@js((string) $value), @js($labelOpsi))"
                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 truncate"
                title="{{ $optionTitles[$value] ?? $labelOpsi }}"
                :class="value === @js((string) $value) && 'bg-ios-blue/10 text-ios-blue font-medium'">
                {{ $labelOpsi }}
            </button>
        @endforeach
    </div>
</div>