/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';

export default class extends Controller {
    _onClick = (e) => {
        if (e.target.closest('[data-open-team-create]')) { this._openCreateModal(); return; }
        if (e.target.closest('#close-team-create-modal')) { this._resetCreateForm(); return; }
        if (e.target.closest('#team-create-backdrop')) { this._resetCreateForm(); return; }
        if (e.target.closest('#team-create-submit')) { this._submitCreate(); return; }

        if (e.target.closest('#close-welcome-modal') || e.target.closest('#welcome-dismiss')) {
            document.getElementById('welcome-modal')?.classList.add('hidden');
            return;
        }
        if (e.target.closest('#welcome-create-team')) {
            document.getElementById('welcome-modal')?.classList.add('hidden');
            this._openCreateModal();
        }
    };

    _onKeydown = (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('team-create-modal');
            if (modal && !modal.classList.contains('hidden')) this._resetCreateForm();
            return;
        }
        if (e.key === 'Enter' && e.target.id === 'team-create-name') {
            e.preventDefault();
            this._submitCreate();
        }
    };

    connect() {
        document.addEventListener('click', this._onClick);
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('click', this._onClick);
        document.removeEventListener('keydown', this._onKeydown);
    }

    _openCreateModal() {
        const el = document.getElementById('team-create-modal');
        this.application.getControllerForElementAndIdentifier(el, 'modal')?.open();
    }

    _resetCreateForm() {
        const input = document.getElementById('team-create-name');
        if (input) input.value = '';
    }

    async _submitCreate() {
        const input = document.getElementById('team-create-name');
        const btn = document.getElementById('team-create-submit');
        const name = input?.value.trim();
        if (!name) return;

        btn.disabled = true;

        try {
            const response = await apiFetch('/team', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/vnd.turbo-stream.html',
                },
                body: JSON.stringify({ name }),
            });

            if (response.ok) {
                const html = await response.text();
                const el = document.getElementById('team-create-modal');
                this.application.getControllerForElementAndIdentifier(el, 'modal')?.close();
                this._resetCreateForm();
                await Turbo.renderStreamMessage(html);
            } else {
                showToast(await errorMessageFromResponse(response, 'Could not create team.'));
            }
        } catch (e) {
            if (!(e instanceof TypeError)) throw e;
            showToast('Network error. Please try again.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }
}
