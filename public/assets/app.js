document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.getAttribute('data-confirm') || 'Confirmar?')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-chart]').forEach((canvas) => {
    if (typeof Chart === 'undefined') {
        return;
    }
    const payload = JSON.parse(canvas.getAttribute('data-chart'));
    new Chart(canvas, payload);
});
