export const $ = <T extends Element = Element>(selector: string): T | null => document.querySelector<T>(selector);

export const $$ = <T extends Element = Element>(selector: string): T[] => [...document.querySelectorAll<T>(selector)];

export const setText = (selector: string, value: unknown): void => {
    const element = $(selector);

    if (element) {
        element.textContent = String(value ?? '');
    }
};

export const escapeHtml = (value: unknown): string => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char] ?? char));
