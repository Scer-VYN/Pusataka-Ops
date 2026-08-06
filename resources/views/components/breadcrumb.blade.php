@props([
    'current',
    'homeLabel' => 'HOME',
    'homeUrl' => null,
])

<nav {{ $attributes->merge(['class' => 'breadcrumb']) }} aria-label="Breadcrumb">
    @if ($homeUrl)
        <a href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        <i aria-hidden="true">/</i>
    @endif
    <strong aria-current="page">{{ $current }}</strong>
</nav>
