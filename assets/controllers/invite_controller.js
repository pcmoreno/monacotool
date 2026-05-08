/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { apiFetch } from 'csrf';
import { errorMessageFromResponse } from 'toast';

export default class extends Controller {
    _onClick = (e) => {
        if (e.target.closest('#open-invite-modal')) { this._openInviteModal(); return; }
        if (e.target.closest('#close-invite-modal')) { this._resetInviteForm(); return; }
        if (e.target.closest('#invite-backdrop')) { this._resetInviteForm(); return; }
        if (e.target.closest('#invite-submit')) { this._submitInvite(); }
    };

    _onKeydown = (e) => {
        if (e.key !== 'Escape') return;
        const modal = document.getElementById('invite-modal');
        if (modal && !modal.classList.contains('hidden')) this._resetInviteForm();
    };

    connect() {
        document.addEventListener('click', this._onClick);
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('click', this._onClick);
        document.removeEventListener('keydown', this._onKeydown);
    }

    _openInviteModal() {
        const el = document.getElementById('invite-modal');
        this.application.getControllerForElementAndIdentifier(el, 'modal')?.open();
    }

    _resetInviteForm() {
        const name = document.getElementById('invite-name');
        const email = document.getElementById('invite-email');
        const errorEl = document.getElementById('invite-error');
        if (name) name.value = '';
        if (email) email.value = '';
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }
    }

    async _submitInvite() {
        const btn = document.getElementById('invite-submit');
        const teamId = btn?.dataset.teamId;
        const name = document.getElementById('invite-name')?.value.trim();
        const email = document.getElementById('invite-email')?.value.trim();
        const errorEl = document.getElementById('invite-error');

        errorEl?.classList.add('hidden');

        if (!name || !email) return;

        btn.disabled = true;

        const showError = (msg) => {
            if (!errorEl) return;
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        };

        try {
            const response = await apiFetch(`/team/${teamId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/vnd.turbo-stream.html',
                },
                body: JSON.stringify({ name, email }),
            });

            if (response.ok) {
                const html = await response.text();
                const el = document.getElementById('invite-modal');
                this.application.getControllerForElementAndIdentifier(el, 'modal')?.close();
                this._resetInviteForm();
                await Turbo.renderStreamMessage(html);
            } else {
                showError(await errorMessageFromResponse(response, 'Could not send invitation.'));
            }
        } catch (e) {
            if (!(e instanceof TypeError)) throw e;
            showError('Network error. Please try again.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }
}
