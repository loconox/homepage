document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-email-user][data-email-domain]').forEach((el) => {
        const user = el.dataset.emailUser;
        const domain = el.dataset.emailDomain;
        const email = `${user}@${domain}`;
        const span = el.querySelector('[data-email-text]');
        if (span) {
            span.textContent = email;
        }
        if (el.tagName === 'A') {
            el.setAttribute('href', `mailto:${email}`);
        }
    });
});
