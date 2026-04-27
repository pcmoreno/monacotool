const modal = document.getElementById('forecast-modal');
const backdrop = document.getElementById('forecast-backdrop');
const openBtn = document.getElementById('open-forecast-modal');
const submitBtn = document.getElementById('forecast-submit');
const outputInput = document.getElementById('forecast-target-output');
const iterationsInput = document.getElementById('forecast-target-iterations');

const iconTrash = document.getElementById('icon-trash').innerHTML;
const iconMagnifier = document.getElementById('icon-magnifier').innerHTML;

const open = () => {
    modal.classList.remove('hidden');
    outputInput.focus();
};

const close = () => {
    modal.classList.add('hidden');
    outputInput.value = '';
    iterationsInput.value = '';
};

openBtn.addEventListener('click', open);
backdrop.addEventListener('click', close);
document.getElementById('close-forecast-modal').addEventListener('click', close);
document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

submitBtn.addEventListener('click', async () => {
    const teamId = submitBtn.dataset.teamId;
    const targetOutput = parseInt(outputInput.value, 10);
    const targetIterations = parseInt(iterationsInput.value, 10);

    if (!targetOutput || targetOutput < 1 || !targetIterations || targetIterations < 1) return;

    submitBtn.disabled = true;

    try {
        const response = await fetch(`/team/${teamId}/forecast`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetOutput, targetIterations }),
        });

        if (response.ok) {
            const data = await response.json();
            close();
            addForecastRow(data);
        }
    } finally {
        submitBtn.disabled = false;
    }
});

const addForecastRow = (forecast) => {
    const empty = document.getElementById('forecasts-empty');
    if (empty) empty.remove();

    const table = document.getElementById('forecasts-tbody').closest('table');
    table.classList.remove('hidden');

    const probability = forecast.result !== null
        ? (forecast.result * 100).toFixed(1) + '%'
        : '—';

    const tr = document.createElement('tr');
    tr.dataset.forecastId = forecast.id;
    tr.innerHTML = `
        <td class="py-2.5">
            <div class="flex items-center gap-2">
                <button type="button" class="text-graphite-400 hover:text-graphite-600 transition">${iconMagnifier}</button>
                <button type="button" data-delete-forecast="${forecast.id}" class="text-graphite-400 hover:text-red-500 transition">${iconTrash}</button>
            </div>
        </td>
        <td class="py-2.5 text-graphite-600">${forecast.createdAt}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${forecast.targetOutput}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${forecast.targetIterations}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${probability}</td>
    `;

    document.getElementById('forecasts-tbody').prepend(tr);
};

document.getElementById('forecasts-tbody').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-delete-forecast]');
    if (!btn) return;

    const forecastId = btn.dataset.deleteForecast;
    const row = btn.closest('tr');

    globalThis.showDeleteConfirm(async () => {
        const response = await fetch(`/forecast/${forecastId}`, { method: 'DELETE' });

        if (response.ok) {
            row.remove();

            const tbody = document.getElementById('forecasts-tbody');
            if (!tbody.querySelector('tr')) {
                tbody.closest('table').classList.add('hidden');
                const p = document.createElement('p');
                p.id = 'forecasts-empty';
                p.className = 'text-sm text-graphite-400';
                p.textContent = 'No forecasts yet.';
                tbody.closest('table').before(p);
            }
        }
    });
});
