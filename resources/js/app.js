// Alpine store para el sidebar — persiste en localStorage
document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        open: localStorage.getItem('sidebar-open') !== 'false',
        toggle() {
            this.open = !this.open;
            localStorage.setItem('sidebar-open', String(this.open));
        },
    });
});
