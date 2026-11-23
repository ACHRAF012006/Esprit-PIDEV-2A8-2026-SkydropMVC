/*
 * SKYDROP - applies the saved theme before the page paints (same localStorage key as TailAdmin: "darkMode").
 * Loaded in <head> without defer, so there is no flash of the wrong theme. A file instead of an inline script,
 * so the Content-Security-Policy can forbid inline scripts.
 */
document.documentElement.classList.add('ds-js');
try {
    if (JSON.parse(localStorage.getItem('darkMode')) === true) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark');
    }
} catch (e) { /* no storage: light theme */ }
