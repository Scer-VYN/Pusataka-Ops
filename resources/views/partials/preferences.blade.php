@php
    $preferenceUser = auth()->user();
    $preferenceConfig = $preferenceUser ? [
        'userId' => (string) $preferenceUser->id,
        'theme' => $preferenceUser->theme ?: 'dark',
        'notificationsEnabled' => (bool) $preferenceUser->notifications_enabled,
    ] : null;
@endphp
<script>
    window.libraryPreferencesConfig = @json($preferenceConfig);
</script>
