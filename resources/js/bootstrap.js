window.fetchWithXRequestedWith = (url, options = {}) => {
    const headers = new Headers(options.headers);
    headers.set('X-Requested-With', 'XMLHttpRequest');

    return fetch(url, {
        ...options,
        headers,
    });
};
