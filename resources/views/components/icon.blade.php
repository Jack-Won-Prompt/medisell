@props(['name', 'size' => 24])
<svg {{ $attributes->merge(['class' => 'icon']) }} width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false"><use href="#i-{{ $name }}"/></svg>
