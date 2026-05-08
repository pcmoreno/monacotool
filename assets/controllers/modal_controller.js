import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    _previousFocus = null;

    connect() {
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeydown);
        this._previousFocus = null;
    }

    open() {
        this._previousFocus = document.activeElement;
        this.element.classList.remove('hidden');
        this.element.setAttribute('aria-hidden', 'false');
        const first = this.element.querySelector('button:not([disabled]), input:not([disabled]), [tabindex="0"]');
        first?.focus();
    }

    close() {
        this.element.classList.add('hidden');
        this.element.setAttribute('aria-hidden', 'true');
        if (this._previousFocus && document.contains(this._previousFocus)) {
            this._previousFocus.focus();
        }
        this._previousFocus = null;
    }

    _onKeydown = (e) => {
        if (e.key === 'Escape' && !this.element.classList.contains('hidden')) {
            this.close();
        }
    }
}
