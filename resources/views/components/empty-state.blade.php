@props([
    'title' => 'No records found.',
    'message' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'messageId' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state', 'role' => 'status']) }}>
    <span class="empty-state-mark" aria-hidden="true">//</span>
    <strong>{{ $title }}</strong>
    @if ($message)
        <p @if ($messageId) id="{{ $messageId }}" @endif>{{ $message }}</p>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}">{{ $actionLabel }} <span aria-hidden="true">↗</span></a>
    @endif
</div>
