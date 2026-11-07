// Helpers shared by the public site pages.

// Stock photos per service, matching the category tiles in the customer app.
const CATEGORY_PHOTOS = {
    haircut: 8867400,
    barber: 8867400,
    hair: 5368632,
    braid: 15576674,
    manicure: 16041439,
    nail: 16041439,
    pedicure: 9146381,
    spa: 9146381,
    facial: 3865548,
    makeup: 5149734,
    massage: 6629530,
    bridal: 2301842,
};

export function categoryPhoto(name, width = 800) {
    const key = Object.keys(CATEGORY_PHOTOS).find((k) => (name || '').toLowerCase().includes(k));
    const id = CATEGORY_PHOTOS[key] ?? CATEGORY_PHOTOS.hair;
    return `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&w=${width}`;
}

const tzs = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });

export function formatPrice(amount) {
    return `TZS ${tzs.format(amount)}`;
}

// Lowest non-zero service price, or null when none are priced.
export function startingPrice(services = []) {
    const prices = services.map((s) => Number(s.price)).filter((p) => p > 0);
    return prices.length ? Math.min(...prices) : null;
}

export function formatDuration(minutes) {
    if (!minutes) return null;
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m ? `${h} h ${m} min` : `${h} h`;
}

// Jetstream's default ui-avatars initials are blue; recolour them to the brand.
export function avatarUrl(url) {
    if (!url || !url.includes('ui-avatars.com')) return url;
    const u = new URL(url);
    u.searchParams.set('color', 'C99A3F');
    u.searchParams.set('background', '2E1B2E');
    return u.toString();
}

// "2026-11-08" -> "Sun, 8 Nov 2026" (parsed as a local date, not UTC midnight).
export function formatDate(isoDate) {
    if (!isoDate) return '';
    const [y, m, d] = isoDate.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

// Today's date as YYYY-MM-DD in the visitor's timezone, for <input type="date" min>.
export function todayIso() {
    const now = new Date();
    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}
