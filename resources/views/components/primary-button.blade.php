@props(['type' => 'submit'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'inline-flex items-center px-5 py-2.5 bg-ios-blue border border-transparent rounded-xl font-medium text-sm text-white tracking-wide hover:bg-ios-blue-hover active:bg-ios-blue-hover transition-colors duration-150 ease-out focus:outline-none focus:ring-2 focus:ring-ios-blue focus:ring-offset-2']) }}>
    {{ $slot }}
</button>