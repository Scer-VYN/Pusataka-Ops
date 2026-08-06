const serverPreferences = window.libraryPreferencesConfig;
const preferenceStoragePrefix = serverPreferences?.userId
    ? `stack-preferences:${serverPreferences.userId}`
    : 'stack-preferences:guest';
const themeStorageKey = `${preferenceStoragePrefix}:theme`;
const notificationStorageKey = `${preferenceStoragePrefix}:notifications`;
const guestPaths = ['/login', '/register', '/forgot-password'];
const savedTheme = window.localStorage.getItem(themeStorageKey);
const savedNotifications = window.localStorage.getItem(notificationStorageKey);
const configuredTheme = serverPreferences?.theme ?? savedTheme;
const theme = configuredTheme === 'light' ? 'light' : 'dark';
const notificationsEnabled = serverPreferences?.notificationsEnabled ?? savedNotifications !== 'off';

if (theme === 'light') {
    document.documentElement.dataset.theme = 'light';
}

window.localStorage.setItem(themeStorageKey, theme);
window.localStorage.setItem(notificationStorageKey, notificationsEnabled ? 'on' : 'off');

const isApiRequest = (input) => {
    const requestUrl = typeof input === 'string' ? input : input?.url;

    if (!requestUrl) {
        return false;
    }

    return new URL(requestUrl, window.location.origin).pathname.startsWith('/api/');
};

const isGuestPage = () => guestPaths.includes(window.location.pathname)
    || window.location.pathname.startsWith('/reset-password/');

const fetchWithSessionExpiryHandling = window.fetch.bind(window);
window.fetch = async (...args) => {
    const response = await fetchWithSessionExpiryHandling(...args);

    if (response.status === 401 && isApiRequest(args[0]) && !isGuestPage()) {
        window.location.replace('/login?session=expired');
    }

    return response;
};

window.libraryPreferences = {
    getNotificationsEnabled: () => window.localStorage.getItem(notificationStorageKey) !== 'off',
    setNotificationsEnabled: (enabled) => {
        window.localStorage.setItem(notificationStorageKey, enabled ? 'on' : 'off');
    },
    setTheme: (theme) => {
        if (theme === 'light') {
            document.documentElement.dataset.theme = 'light';
        } else {
            delete document.documentElement.dataset.theme;
        }

        window.localStorage.setItem(themeStorageKey, theme === 'light' ? 'light' : 'dark');
    },
};
