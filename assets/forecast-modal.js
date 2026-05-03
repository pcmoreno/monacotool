import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';
import { icons, showDeleteConfirm } from 'delete-confirm';

const openForecast = () => {
    document.getElementById('forecast-modal').classList.remove('hidden');
    document.getElementById('forecast-target-output').focus();
};

const closeForecast = () => {
    const modal = document.getElementById('forecast-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    const output = document.getElementById('forecast-target-output');
    const iterations = document.getElementById('forecast-target-iterations');
    if (output) output.value = '';
    if (iterations) iterations.value = '';
};

const submitForecast = async () => {
    const btn = document.getElementById('forecast-submit');
    const teamId = btn.dataset.teamId;
    const targetOutput = parseInt(document.getElementById('forecast-target-output').value, 10);
    const targetIterations = parseInt(document.getElementById('forecast-target-iterations').value, 10);

    if (!targetOutput || targetOutput < 1 || targetOutput > 100000 || !targetIterations || targetIterations < 1 || targetIterations > 100) return;

    btn.disabled = true;

    try {
        const response = await apiFetch(`/team/${teamId}/forecast`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetOutput, targetIterations }),
        });

        if (response.ok) {
            const data = await response.json();
            closeForecast();
            addForecastRow(data);
        } else {
            showToast(await errorMessageFromResponse(response, 'Could not run forecast.'));
        }
    } catch {
        showToast('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
};

const deleteForecast = (forecastId, row) => {
    showDeleteConfirm(async () => {
        try {
            const response = await apiFetch(`/forecast/${forecastId}`, { method: 'DELETE' });

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
            } else {
                showToast(await errorMessageFromResponse(response, 'Could not delete forecast.'));
            }
        } catch {
            showToast('Network error. Please try again.');
        }
    });
};

const closeForecastDetail = () => {
    document.getElementById('forecast-detail-modal')?.classList.add('hidden');
};

const probabilityColor = (p) => {
    if (p <= 0.5) {
        const lightness = 28 + (p / 0.5) * 14;
        return `hsl(0, 65%, ${lightness.toFixed(1)}%)`;
    }
    if (p >= 0.9) {
        const lightness = 42 - ((p - 0.9) / 0.1) * 10;
        return `hsl(120, 65%, ${lightness.toFixed(1)}%)`;
    }
    const hue = ((p - 0.5) / 0.4) * 120;
    return `hsl(${hue}, 65%, 42%)`;
};

const renderSensitivityTable = (sensitivityTable, targetIterations, targetResult) => {
    const thead = document.getElementById('fd-sensitivity-thead');
    const tbody = document.getElementById('fd-sensitivity-tbody');
    thead.innerHTML = '';
    tbody.innerHTML = '';

    const sorted = [];
    for (let i = targetIterations - 5; i <= targetIterations + 5; i++) {
        sorted.push(i);
    }

    const sooner = sorted.filter(i => i < targetIterations);
    const later = sorted.filter(i => i > targetIterations);

    const groupRow = document.createElement('tr');

    if (sooner.length) {
        const th = document.createElement('th');
        th.colSpan = sooner.length;
        th.className = 'px-3 py-1 text-center text-xs font-semibold text-graphite-400 border-b border-graphite-100';
        th.textContent = 'Sooner';
        groupRow.appendChild(th);
    }

    const targetGroupTh = document.createElement('th');
    targetGroupTh.className = 'px-3 py-1 text-center text-xs font-bold text-graphite-900 border-x-2 border-t-2 border-graphite-300';
    targetGroupTh.textContent = targetIterations;
    groupRow.appendChild(targetGroupTh);

    if (later.length) {
        const th = document.createElement('th');
        th.colSpan = later.length;
        th.className = 'px-3 py-1 text-center text-xs font-semibold text-graphite-400 border-b border-graphite-100';
        th.textContent = 'Later';
        groupRow.appendChild(th);
    }

    const numberRow = document.createElement('tr');
    const valueRow = document.createElement('tr');

    sorted.forEach((iter) => {
        const isTarget = iter === targetIterations;
        const isInvalid = iter <= 0;
        const val = isTarget ? targetResult : (sensitivityTable[String(iter)] ?? null);

        const th = document.createElement('th');
        th.className = 'px-2 py-1 text-center text-xs font-medium' + (isTarget ? ' border-x-2 border-b-2 border-graphite-300' : ' text-graphite-400');
        th.textContent = isTarget ? '' : (isInvalid ? '' : iter);
        numberRow.appendChild(th);

        const td = document.createElement('td');
        td.className = 'px-2 py-2 text-center text-xs font-bold text-white' + (isTarget ? ' border-x-2 border-b-2 border-graphite-300' : '');

        if (isInvalid) {
            td.style.backgroundColor = '#1a1a1a';
            td.textContent = '✕';
        } else {
            td.style.backgroundColor = probabilityColor(val);
            td.textContent = (val * 100).toFixed(1) + '%';
        }

        valueRow.appendChild(td);
    });

    thead.appendChild(groupRow);
    thead.appendChild(numberRow);
    tbody.appendChild(valueRow);

    document.getElementById('fd-sensitivity-loading').classList.add('hidden');
    document.getElementById('fd-sensitivity-table').classList.remove('hidden');
};

const openForecastDetail = async (forecast) => {
    const modal = document.getElementById('forecast-detail-modal');
    modal.classList.remove('hidden');

    document.getElementById('fd-created-at').textContent = forecast.createdAt;
    document.getElementById('fd-simulations').textContent = forecast.numberOfSimulations.toLocaleString();
    document.getElementById('fd-target-output').textContent = forecast.targetOutput;
    document.getElementById('fd-target-iterations').textContent = forecast.targetIterations;

    const snap = forecast.teamStatsSnapshot;
    document.getElementById('fd-mean').textContent = snap ? snap.mean.toFixed(1) : '—';
    document.getElementById('fd-std-dev').textContent = snap ? snap.std_dev.toFixed(3) : '—';

    const resultEl = document.getElementById('fd-result');
    const resultVal = forecast.result ?? 0;
    resultEl.dataset.raw = resultVal;
    resultEl.textContent = (resultVal * 100).toFixed(1) + '%';
    resultEl.style.color = probabilityColor(resultVal);

    document.getElementById('fd-sensitivity-loading').classList.remove('hidden');
    document.getElementById('fd-sensitivity-table').classList.add('hidden');

    if (forecast.sensitivityTable) {
        renderSensitivityTable(forecast.sensitivityTable, forecast.targetIterations, forecast.result ?? 0);
        return;
    }

    try {
        const response = await apiFetch(`/forecast/${forecast.id}/sensitivity`, { method: 'POST' });
        if (response.ok) {
            const table = await response.json();
            forecast.sensitivityTable = table;
            const tr = document.querySelector(`[data-forecast-id="${forecast.id}"]`);
            if (tr) {
                const data = JSON.parse(tr.dataset.forecast);
                data.sensitivityTable = table;
                tr.dataset.forecast = JSON.stringify(data);
            }
            renderSensitivityTable(table, forecast.targetIterations, forecast.result ?? 0);
        } else {
            document.getElementById('fd-sensitivity-loading').textContent = 'Could not load sensitivity data.';
        }
    } catch {
        document.getElementById('fd-sensitivity-loading').textContent = 'Network error.';
    }
};

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-forecast-modal')) { openForecast(); return; }
    if (e.target.closest('#close-forecast-modal')) { closeForecast(); return; }
    if (e.target.closest('#forecast-backdrop')) { closeForecast(); return; }
    if (e.target.closest('#forecast-submit')) { submitForecast(); return; }
    if (e.target.closest('#close-forecast-detail-modal')) { closeForecastDetail(); return; }
    if (e.target.closest('#forecast-detail-backdrop')) { closeForecastDetail(); return; }

    const showBtn = e.target.closest('[data-show-forecast]');
    if (showBtn) {
        const forecast = JSON.parse(showBtn.closest('tr').dataset.forecast);
        openForecastDetail(forecast);
        return;
    }

    const deleteBtn = e.target.closest('[data-delete-forecast]');
    if (deleteBtn) {
        deleteForecast(deleteBtn.dataset.deleteForecast, deleteBtn.closest('tr'));
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (!document.getElementById('forecast-detail-modal')?.classList.contains('hidden')) {
            closeForecastDetail();
            return;
        }
        if (!document.getElementById('forecast-modal')?.classList.contains('hidden')) {
            closeForecast();
        }
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
    tr.dataset.forecast = JSON.stringify(forecast);

    const magnifierBtn = document.createElement('button');
    magnifierBtn.type = 'button';
    magnifierBtn.dataset.showForecast = '';
    magnifierBtn.className = 'text-graphite-400 hover:text-graphite-600 transition';
    magnifierBtn.innerHTML = icons.magnifier;

    const trashBtn = document.createElement('button');
    trashBtn.type = 'button';
    trashBtn.dataset.deleteForecast = forecast.id;
    trashBtn.className = 'text-graphite-400 hover:text-red-500 transition';
    trashBtn.innerHTML = icons.trash;

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'flex items-center gap-2';
    actionsDiv.appendChild(magnifierBtn);
    actionsDiv.appendChild(trashBtn);

    const actionsTd = document.createElement('td');
    actionsTd.className = 'py-2.5';
    actionsTd.appendChild(actionsDiv);

    const createdAtTd = document.createElement('td');
    createdAtTd.className = 'py-2.5 text-graphite-600';
    createdAtTd.textContent = forecast.createdAt;

    const targetOutputTd = document.createElement('td');
    targetOutputTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    targetOutputTd.textContent = forecast.targetOutput;

    const targetIterationsTd = document.createElement('td');
    targetIterationsTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    targetIterationsTd.textContent = forecast.targetIterations;

    const probabilityTd = document.createElement('td');
    probabilityTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    probabilityTd.textContent = probability;

    tr.appendChild(actionsTd);
    tr.appendChild(createdAtTd);
    tr.appendChild(targetOutputTd);
    tr.appendChild(targetIterationsTd);
    tr.appendChild(probabilityTd);

    document.getElementById('forecasts-tbody').prepend(tr);
};
