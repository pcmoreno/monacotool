/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';

export default class extends Controller {
    _onClick = (e) => {
        if (e.target.closest('#open-forecast-modal')) { this._openForecastModal(); return; }
        if (e.target.closest('#close-forecast-modal')) { this._resetForecastInputs(); return; }
        if (e.target.closest('#forecast-backdrop')) { this._resetForecastInputs(); return; }
        if (e.target.closest('#forecast-submit')) { this._submitForecast(); return; }

        const showBtn = e.target.closest('[data-show-forecast]');
        if (showBtn) {
            try {
                const forecast = JSON.parse(showBtn.closest('tr').dataset.forecast);
                this._openForecastDetail(forecast);
            } catch {
                showToast('Could not read forecast data.');
            }
        }
    };

    _onKeydown = (e) => {
        if (e.key !== 'Escape') return;
        const modal = document.getElementById('forecast-modal');
        if (modal && !modal.classList.contains('hidden')) this._resetForecastInputs();
    };

    connect() {
        document.addEventListener('click', this._onClick);
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('click', this._onClick);
        document.removeEventListener('keydown', this._onKeydown);
    }

    _modalCtrl(id) {
        const el = document.getElementById(id);
        return el && this.application.getControllerForElementAndIdentifier(el, 'modal');
    }

    _openForecastModal() {
        this._modalCtrl('forecast-modal')?.open();
    }

    _resetForecastInputs() {
        const output = document.getElementById('forecast-target-output');
        const iterations = document.getElementById('forecast-target-iterations');
        if (output) output.value = '';
        if (iterations) iterations.value = '';
    }

    async _submitForecast() {
        const btn = document.getElementById('forecast-submit');
        const teamId = btn?.dataset.teamId;
        const targetOutput = parseInt(document.getElementById('forecast-target-output')?.value, 10);
        const targetIterations = parseInt(document.getElementById('forecast-target-iterations')?.value, 10);

        if (!targetOutput || targetOutput < 1 || targetOutput > 100000 ||
            !targetIterations || targetIterations < 1 || targetIterations > 100) return;

        btn.disabled = true;

        try {
            const response = await apiFetch(`/team/${teamId}/forecast`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/vnd.turbo-stream.html',
                },
                body: JSON.stringify({ targetOutput, targetIterations }),
            });

            if (response.ok) {
                const html = await response.text();
                this._modalCtrl('forecast-modal')?.close();
                this._resetForecastInputs();
                await Turbo.renderStreamMessage(html);
            } else {
                showToast(await errorMessageFromResponse(response, 'Could not run forecast.'));
            }
        } catch (e) {
            if (!(e instanceof TypeError)) throw e;
            showToast('Network error. Please try again.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async _openForecastDetail(forecast) {
        this._modalCtrl('forecast-detail-modal')?.open();

        document.getElementById('fd-created-at').textContent = forecast.createdAt;
        document.getElementById('fd-simulations').textContent = forecast.numberOfSimulations.toLocaleString();
        document.getElementById('fd-target-output').textContent = forecast.targetOutput;
        document.getElementById('fd-target-iterations').textContent = forecast.targetIterations;

        const snap = forecast.teamStatsSnapshot;
        document.getElementById('fd-mean').textContent = snap ? snap.mean.toFixed(1) : '—';
        document.getElementById('fd-std-dev').textContent = snap ? snap.std_dev.toFixed(3) : '—';

        const resultVal = forecast.result ?? 0;
        const resultEl = document.getElementById('fd-result');
        resultEl.textContent = (resultVal * 100).toFixed(1) + '%';
        resultEl.style.color = this._probabilityColor(resultVal);

        const labelEl = document.getElementById('fd-result-label');
        if (labelEl) labelEl.textContent = resultVal >= 0.9 ? 'Likely' : resultVal >= 0.5 ? 'Possible' : 'Unlikely';

        const loading = document.getElementById('fd-sensitivity-loading');
        if (loading) loading.classList.remove('hidden');
        const table = document.getElementById('fd-sensitivity-table');
        if (table) table.classList.add('hidden');

        await this._loadSensitivity(forecast);
    }

    async _loadSensitivity(forecast) {
        try {
            const response = await apiFetch(`/forecast/${forecast.id}/sensitivity`, {
                method: 'POST',
                headers: { 'Accept': 'text/vnd.turbo-stream.html' },
            });
            if (response.ok) {
                await Turbo.renderStreamMessage(await response.text());
            } else {
                const loading = document.getElementById('fd-sensitivity-loading');
                if (loading) loading.textContent = 'Could not load sensitivity data.';
            }
        } catch (e) {
            if (!(e instanceof TypeError)) throw e;
            const loading = document.getElementById('fd-sensitivity-loading');
            if (loading) loading.textContent = 'Network error.';
        }
    }

    _probabilityColor(p) {
        if (p <= 0.5) return `hsl(0, 65%, ${(28 + (p / 0.5) * 14).toFixed(1)}%)`;
        if (p >= 0.9) return `hsl(120, 65%, ${(42 - ((p - 0.9) / 0.1) * 10).toFixed(1)}%)`;
        return `hsl(${((p - 0.5) / 0.4 * 120).toFixed(1)}, 65%, 42%)`;
    }
}
