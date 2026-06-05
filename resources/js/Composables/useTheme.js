import { computed, onMounted, ref } from 'vue';

const THEME_KEY = 'payroll-theme';
const theme = ref('light');

function resolveInitialTheme() {
    if (typeof window === 'undefined') {
        return 'light';
    }

    const stored = window.localStorage.getItem(THEME_KEY);
    if (stored === 'dark' || stored === 'light') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyTheme(value) {
    if (typeof document === 'undefined') {
        return;
    }

    const nextTheme = value === 'dark' ? 'dark' : 'light';
    theme.value = nextTheme;
    document.documentElement.classList.toggle('dark', nextTheme === 'dark');
    document.documentElement.style.colorScheme = nextTheme;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(THEME_KEY, nextTheme);
    }
}

export function applyStoredTheme() {
    const initialTheme = resolveInitialTheme();
    applyTheme(initialTheme);
    return initialTheme;
}

export function useTheme() {
    onMounted(() => {
        theme.value = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    });

    const isDark = computed(() => theme.value === 'dark');

    function toggleTheme() {
        applyTheme(isDark.value ? 'light' : 'dark');
    }

    return {
        theme,
        isDark,
        toggleTheme,
        applyTheme,
    };
}
