const getTheme = () => localStorage.getItem('theme') ?? 'light';

const applyTheme = (theme) => {
    if (theme === 'light') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
    localStorage.setItem('theme', theme);
    syncLabel();
    closeMenu();
};

const syncLabel = () => {
    const theme = getTheme();
    const label = document.getElementById('theme-label');
    if (label) label.textContent = theme.charAt(0).toUpperCase() + theme.slice(1);
};

const closeMenu = () => document.getElementById('theme-menu')?.classList.add('hidden');
const toggleMenu = () => document.getElementById('theme-menu')?.classList.toggle('hidden');

// Runs on first load and after every Turbo navigation
document.addEventListener('turbo:load', syncLabel);

// Single delegated click handler — works after every body replacement
document.addEventListener('click', (e) => {
    const pick = e.target.closest('[data-theme-pick]');
    if (pick) { applyTheme(pick.dataset.themePick); return; }

    if (e.target.closest('#theme-toggle')) { toggleMenu(); return; }

    if (!document.getElementById('theme-container')?.contains(e.target)) closeMenu();
});
