// cockpit.js — Alpine.js components & helpers
// Loaded via CDN in layout.blade.php; this file is provided for
// projects that prefer a local build step.

// Re-export the cockpitFetch helper so it can be imported in custom builds
export async function cockpitFetch(url, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers ?? {}),
        },
    });
}
