const PATHS = {
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    'check-circle': '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    'eye-off': '<path d="M3 3l18 18M10.7 10.7a2.5 2.5 0 0 0 3.6 3.6M9.9 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.1 6.1A17 17 0 0 0 2 12s3.5 7 10 7a10 10 0 0 0 4.1-.9"/>',
    hourglass: '<path d="M6 2h12M6 22h12M8 2v4l4 4 4-4V2M8 22v-4l4-4 4 4v4"/>',
    'rotate-ccw': '<path d="M1 4v6h6"/><path d="M3.5 15a9 9 0 1 0 .8-8.4L1 10"/>',
    'user-check': '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 11l2 2 4-4"/>',
    circle: '<circle cx="12" cy="12" r="9"/>',
};

const ALIAS = {
    'eye-slash': 'eye-off',
    'x-lg': 'x',
    'check2': 'check',
    'check2-circle': 'check-circle',
    'hourglass-split': 'hourglass',
    'arrow-counterclockwise': 'rotate-ccw',
    'person-check': 'user-check',
};

function clave(nombre) {
    let n = String(nombre || '').trim().toLowerCase().replace(/^bi[\s-]*/, '');
    return ALIAS[n] || n;
}

export function cdaIcon(nombre, className = '') {
    const key = clave(nombre);
    const inner = PATHS[key] || PATHS.circle;
    const cls = className ? ` class="${className}"` : '';

    return `<svg${cls} xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
}

window.cdaIcon = cdaIcon;
