import { apiFetch } from 'csrf';

const closeRegister = () => {
    const modal = document.getElementById('register-modal');
    if (!modal) return;
    modal.classList.add('hidden');

    const errors = document.getElementById('register-errors');
    if (errors) {
        errors.classList.add('hidden');
        errors.replaceChildren();
    }

    ['register-name', 'register-email', 'register-password', 'register-confirm'].forEach(id => {
        const i = document.getElementById(id);
        if (i) i.value = '';
    });
};

const openRegister = () => {
    document.getElementById('register-modal').classList.remove('hidden');
    document.getElementById('register-name').focus();
};

const showErrors = (errors) => {
    const container = document.getElementById('register-errors');
    container.replaceChildren(...errors.map(msg => {
        const p = document.createElement('p');
        p.textContent = msg;
        return p;
    }));
    container.classList.remove('hidden');
};

const submitRegister = async () => {
    const errorsEl = document.getElementById('register-errors');
    const btn = document.getElementById('register-submit');

    errorsEl.classList.add('hidden');
    btn.disabled = true;

    try {
        const response = await apiFetch('/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: document.getElementById('register-email').value.trim(),
                name: document.getElementById('register-name').value.trim(),
                password: document.getElementById('register-password').value,
                confirmPassword: document.getElementById('register-confirm').value,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            window.location.href = data.redirect;
        } else {
            showErrors(data.errors ?? ['Something went wrong. Please try again.']);
        }
    } catch {
        showErrors(['Something went wrong. Please try again.']);
    } finally {
        btn.disabled = false;
    }
};

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-register-modal')) { openRegister(); return; }
    if (e.target.closest('#close-register-modal')) { closeRegister(); return; }
    if (e.target.closest('#register-backdrop')) { closeRegister(); return; }
    if (e.target.closest('#register-submit')) { submitRegister(); return; }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('register-modal')?.classList.contains('hidden')) {
        closeRegister();
    }
});
