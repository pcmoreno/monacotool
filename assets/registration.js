import { apiFetch } from 'csrf';

let pendingEmail = '';

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

const openCheckInbox = (email) => {
    pendingEmail = email;
    document.getElementById('check-inbox-email').textContent = email;
    const feedback = document.getElementById('resend-feedback');
    if (feedback) {
        feedback.classList.add('hidden');
        feedback.textContent = '';
    }
    document.getElementById('check-inbox-modal').classList.remove('hidden');
};

const closeCheckInbox = () => {
    document.getElementById('check-inbox-modal').classList.add('hidden');
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
            closeRegister();
            openCheckInbox(data.email);
        } else {
            const errors = data.errors
                ?? data.violations?.map(v => v.title)
                ?? ['Something went wrong. Please try again.'];
            showErrors(errors);
        }
    } catch {
        showErrors(['Something went wrong. Please try again.']);
    } finally {
        btn.disabled = false;
    }
};

const resendVerification = async () => {
    const feedback = document.getElementById('resend-feedback');
    const btn = document.getElementById('resend-verification-btn');

    feedback.classList.add('hidden');
    btn.disabled = true;

    try {
        await apiFetch('/resend-verification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: pendingEmail }),
        });

        feedback.textContent = 'A new link has been sent.';
        feedback.classList.remove('hidden');
    } catch {
        feedback.textContent = 'Something went wrong. Please try again.';
        feedback.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
};

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-register-modal')) { openRegister(); return; }
    if (e.target.closest('#close-register-modal')) { closeRegister(); return; }
    if (e.target.closest('#register-submit')) { submitRegister(); return; }
    if (e.target.closest('#close-check-inbox-modal')) { closeCheckInbox(); return; }
    if (e.target.closest('#resend-verification-btn')) { resendVerification(); return; }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('check-inbox-modal')?.classList.contains('hidden')) {
        closeCheckInbox();
    }
});
