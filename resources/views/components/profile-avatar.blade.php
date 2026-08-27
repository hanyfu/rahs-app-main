@props(['profile' => null, 'size' => 'md', 'class' => ''])

@php
    $dimensions = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-14 w-14 text-lg'];
    $sizeClass = $dimensions[$size] ?? $dimensions['md'];
@endphp

@if ($profile && $profile->avatar_url)
    <img src="{{ $profile->avatar_url }}" alt="{{ $profile->full_name }}" class="{{ $sizeClass }} {{ $class }} rounded-full object-cover bg-muted" loading="lazy">
@else
    <div class="{{ $sizeClass }} {{ $class }} flex items-center justify-center rounded-full bg-primary/20 text-primary font-bold">
        {{ $profile ? $profile->initials : 'R' }}
    </div>
@endif