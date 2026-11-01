@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-ios-label mb-1']) }}>
    {{ $value ?? $slot }}
</label>