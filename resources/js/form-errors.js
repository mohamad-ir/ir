/*
 * Accessible form errors: every ".field-error" message is linked to its field (aria-invalid,
 * aria-describedby), and after a failed submission the first invalid field receives focus.
 * Works for normal forms and for Livewire forms (errors appear after an update).
 */
let counter = 0;

function fieldFor(error) {
    const wrapper = error.closest('div, fieldset, label') || error.parentElement;
    return wrapper?.querySelector('input:not([type=hidden]), select, textarea');
}

function link(root = document) {
    let first = null;
    root.querySelectorAll('.field-error').forEach((error) => {
        const field = fieldFor(error);
        if (!field) return;
        error.id ||= `field-error-${++counter}`;
        field.setAttribute('aria-invalid', 'true');
        const ids = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        ids.add(error.id);
        field.setAttribute('aria-describedby', [...ids].join(' '));
        if (!error.dataset.linked) {
            error.dataset.linked = '1';
            first ||= field;
        }
    });
    // Fields whose error disappeared are valid again.
    root.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
        const ids = (field.getAttribute('aria-describedby') || '').split(/\s+/);
        if (!ids.some((id) => id.startsWith('field-error-') && document.getElementById(id))) field.removeAttribute('aria-invalid');
    });
    return first;
}

document.addEventListener('DOMContentLoaded', () => {
    link()?.focus({ preventScroll: false });
    let timer;
    new MutationObserver(() => {
        clearTimeout(timer);
        timer = setTimeout(() => link()?.focus({ preventScroll: false }), 50);
    }).observe(document.body, { childList: true, subtree: true });
});
