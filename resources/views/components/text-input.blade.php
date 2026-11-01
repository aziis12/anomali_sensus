@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-ios-border bg-white text-ios-label placeholder-ios-secondary rounded-xl shadow-sm focus:border-ios-blue focus:ring-ios-blue text-sm']) }}>