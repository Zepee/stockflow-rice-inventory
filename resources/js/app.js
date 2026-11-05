function setupTransactionRows(root = document) {
    const configurations = [
        {
            form: '[data-adjustment-form]',
            rows: '[data-adjustment-rows]',
            template: '#adjustment-row-template',
            addButton: '[data-add-adjustment-row]',
        },
        {
            form: '[data-purchase-form]',
            rows: '[data-purchase-rows]',
            template: '#purchase-row-template',
            addButton: '[data-add-purchase-row]',
        },
        {
            form: '[data-sale-form]',
            rows: '[data-sale-rows]',
            template: '#sale-row-template',
            addButton: '[data-add-sale-row]',
        },
    ];

    configurations.forEach((configuration) => {
        const form = root.querySelector(configuration.form);
        const rows = root.querySelector(configuration.rows);
        const template = root.querySelector(configuration.template);
        const addButton = root.querySelector(configuration.addButton);

        if (!form || !rows || !template || !addButton || form.dataset.rowsInitialized) return;

        form.dataset.rowsInitialized = 'true';
        let nextIndex = rows.querySelectorAll('[data-transaction-row]').length;

        addButton.addEventListener('click', () => {
            const row = template.content.cloneNode(true);
            row.querySelectorAll('[name], [id], label[for]').forEach((element) => {
                ['name', 'id', 'for'].forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(nextIndex)));
                    }
                });
            });
            rows.append(row);
            nextIndex += 1;
        });

        rows.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-transaction-row]');
            if (button && rows.querySelectorAll('[data-transaction-row]').length > 1) {
                button.closest('[data-transaction-row]').remove();
            }
        });
    });
}

function transactionTypeFromPath(pathname) {
    if (/\/purchases\/create(?:\/[^/]+)?\/?$/.test(pathname)) return 'purchase';
    if (/\/sales\/create(?:\/[^/]+)?\/?$/.test(pathname)) return 'sale';

    return null;
}

function transactionTitle(type) {
    return type === 'purchase' ? 'Catat barang masuk' : 'Catat penjualan';
}

function initTransactionDialog() {
    const dialog = document.querySelector('#transaction-dialog');
    const title = document.querySelector('#transaction-dialog-title');
    const step = document.querySelector('#transaction-dialog-step');
    const description = document.querySelector('#transaction-dialog-description');
    const content = document.querySelector('#transaction-dialog-content');

    if (!dialog || !title || !step || !description || !content || typeof dialog.showModal !== 'function') return;

    let previousFocus = null;
    let requestVersion = 0;
    let isSubmitting = false;

    const setLoading = (type) => {
        step.textContent = 'Langkah 1 dari 2 · ' + (type === 'purchase' ? 'Stok masuk' : 'Stok keluar');
        title.textContent = transactionTitle(type);
        description.textContent = 'Isi rincian transaksi, lalu periksa sebelum mengonfirmasi.';
        content.setAttribute('aria-busy', 'true');
        content.scrollTop = 0;
        content.innerHTML = '<p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" role="status">Memuat formulir transaksi…</p>';
    };

    const focusDialogHeading = () => title.focus({ preventScroll: true });

    const showRequestError = () => {
        content.removeAttribute('aria-busy');
        const error = document.createElement('p');
        error.className = 'mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800';
        error.setAttribute('role', 'alert');
        error.textContent = 'Transaksi belum terkirim. Periksa koneksi, lalu coba lagi. Isi formulir tetap tersedia.';
        content.prepend(error);
        const submitButton = content.querySelector('form button[type="submit"]:not([disabled])');
        if (submitButton) submitButton.focus({ preventScroll: true });
    };

    const renderPage = (documentPage, responseUrl) => {
        const main = documentPage.querySelector('main');
        if (!main) {
            showRequestError();
            return false;
        }

        content.innerHTML = main.innerHTML;
        setupTransactionRows(content);

        const intro = content.querySelector('[data-transaction-intro]');
        const heading = intro?.querySelector('h1') ?? content.querySelector('h1');
        const stepText = intro?.querySelector('[data-transaction-step]')?.textContent.trim();
        const descriptionText = intro?.querySelector('[data-transaction-description]')?.textContent.trim();
        if (heading?.textContent.trim()) title.textContent = heading.textContent.trim();
        if (stepText) step.textContent = stepText;
        if (descriptionText) description.textContent = descriptionText;

        intro?.remove();
        content.querySelector('[data-transaction-dialog-back]')?.remove();

        const destination = new URL(responseUrl);
        const hasPendingReview = /\/(?:purchases|sales)\/drafts\/[^/]+\/review\/?$/.test(destination.pathname);
        const hasValidationErrors = Boolean(content.querySelector('form .text-red-700'));
        dialog.dataset.transactionDirty = hasPendingReview || hasValidationErrors ? 'true' : 'false';
        dialog.dataset.transactionCompleted = 'false';
        dialog.dataset.transactionCurrentUrl = responseUrl;
        content.removeAttribute('aria-busy');
        content.scrollTop = 0;

        focusDialogHeading();

        return true;
    };

    const loadPage = async (url, type) => {
        const thisRequest = ++requestVersion;
        setLoading(type);

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Request failed: ' + response.status);

            const responseUrl = response.url || url;
            const destination = new URL(responseUrl, window.location.href);
            if (destination.origin !== window.location.origin) throw new Error('Unexpected transaction response.');
            if (destination.pathname.replace(/\/$/, '') === '/login') {
                window.location.assign(destination.href);
                return;
            }

            const html = await response.text();
            if (thisRequest !== requestVersion) return;

            const documentPage = new DOMParser().parseFromString(html, 'text/html');
            renderPage(documentPage, destination.href);
        } catch {
            if (thisRequest === requestVersion) {
                content.replaceChildren();
                showRequestError();
            }
        }
    };

    const renderSuccess = (type, detailUrl) => {
        dialog.dataset.transactionCompleted = 'true';
        dialog.dataset.transactionDirty = 'false';
        step.textContent = 'Selesai';
        title.textContent = 'Transaksi berhasil';
        description.textContent = '';
        content.removeAttribute('aria-busy');
        content.replaceChildren();
        content.scrollTop = 0;

        const panel = document.createElement('section');
        panel.className = 'mx-auto max-w-lg py-8 text-center sm:py-12';
        panel.setAttribute('role', 'status');

        const mark = document.createElement('span');
        mark.className = 'mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-800';
        mark.setAttribute('aria-hidden', 'true');
        mark.innerHTML = '<svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>';

        const heading = document.createElement('h3');
        heading.className = 'mt-4 text-lg font-semibold text-slate-950';
        heading.textContent = type === 'purchase' ? 'Barang masuk berhasil dicatat' : 'Penjualan berhasil dicatat';

        const description = document.createElement('p');
        description.className = 'mt-2 text-sm leading-6 text-slate-600';
        description.textContent = 'Stok sudah diperbarui. Tutup dialog untuk melihat halaman yang diperbarui.';

        const actions = document.createElement('div');
        actions.className = 'mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center';

        const detailLink = document.createElement('a');
        detailLink.href = detailUrl;
        detailLink.className = 'inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50';
        detailLink.textContent = type === 'purchase' ? 'Lihat detail pembelian' : 'Lihat detail penjualan';

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.dataset.transactionDialogClose = '';
        closeButton.className = 'inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition hover:bg-emerald-900';
        closeButton.textContent = 'Tutup';

        actions.append(detailLink, closeButton);
        panel.append(mark, heading, description, actions);
        content.append(panel);
        focusDialogHeading();
    };

    const submitForm = async (form) => {
        if (isSubmitting) return;
        isSubmitting = true;

        const submitButton = form.querySelector('button[type="submit"]:not([disabled])');
        const originalLabel = submitButton?.textContent;
        content.setAttribute('aria-busy', 'true');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.textContent = form.dataset.submitLabel || (form.dataset.transactionConfirm ? 'Memproses transaksi…' : 'Memeriksa transaksi…');
        }

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Request failed: ' + response.status);

            const responseUrl = response.url || form.action;
            const destination = new URL(responseUrl, window.location.href);
            if (destination.origin !== window.location.origin) throw new Error('Unexpected transaction response.');
            if (destination.pathname.replace(/\/$/, '') === '/login') {
                window.location.assign(destination.href);
                return;
            }

            if (form.dataset.transactionConfirm && response.redirected) {
                const detailPattern = form.dataset.transactionConfirm === 'purchase'
                    ? /^\/purchases\/\d+\/?$/
                    : /^\/sales\/\d+\/?$/;
                if (detailPattern.test(destination.pathname)) {
                    renderSuccess(form.dataset.transactionConfirm, destination.href);
                    return;
                }
            }

            const html = await response.text();
            const documentPage = new DOMParser().parseFromString(html, 'text/html');
            renderPage(documentPage, destination.href);
        } catch {
            showRequestError();
            if (submitButton?.isConnected) {
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                if (originalLabel) submitButton.textContent = originalLabel;
            }
            content.removeAttribute('aria-busy');
        } finally {
            isSubmitting = false;
        }
    };

    const closeDialog = () => {
        if (!dialog.open) return;

        if (isSubmitting) {
            const notice = document.createElement('p');
            notice.className = 'mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900';
            notice.setAttribute('role', 'status');
            notice.textContent = 'Transaksi sedang diproses. Tunggu sampai proses selesai sebelum menutup dialog.';
            content.prepend(notice);
            return;
        }

        if (dialog.dataset.transactionCompleted !== 'true' && dialog.dataset.transactionDirty === 'true') {
            const discard = window.confirm('Tutup dialog dan buang isi transaksi yang belum dikonfirmasi?');
            if (!discard) return;
        }

        requestVersion += 1;
        const completed = dialog.dataset.transactionCompleted === 'true';
        if (!completed) {
            content.replaceChildren();
            dialog.dataset.transactionDirty = 'false';
            dialog.dataset.transactionCurrentUrl = '';
        }
        dialog.close();
    };

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog();
    });

    dialog.addEventListener('close', () => {
        if (dialog.dataset.transactionCompleted === 'true') {
            window.location.reload();
            return;
        }
        if (previousFocus instanceof HTMLElement) previousFocus.focus({ preventScroll: true });
    });

    content.addEventListener('input', () => {
        dialog.dataset.transactionDirty = 'true';
    });
    content.addEventListener('change', () => {
        dialog.dataset.transactionDirty = 'true';
    });

    document.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-transaction-dialog-close]');
        if (closeButton && dialog.contains(closeButton)) {
            event.preventDefault();
            closeDialog();
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link) return;

        if (dialog.contains(link) && link.hasAttribute('data-transaction-dialog-cancel')) {
            event.preventDefault();
            closeDialog();
            return;
        }

        const url = new URL(link.href, window.location.href);
        const type = transactionTypeFromPath(url.pathname);
        if (!type) return;

        event.preventDefault();
        if (!dialog.open) {
            if (content.childElementCount && dialog.dataset.transactionDirty === 'true') {
                const discard = window.confirm('Ganti transaksi dan buang isi yang belum dikonfirmasi?');
                if (!discard) return;
            }
            previousFocus = link;
            dialog.dataset.transactionCompleted = 'false';
            dialog.showModal();
            loadPage(url.href, type);
            return;
        }

        if (dialog.contains(link)) loadPage(url.href, type);
    });

    document.addEventListener('submit', (event) => {
        if (!dialog.open || !content.contains(event.target)) return;

        event.preventDefault();
        submitForm(event.target);
    }, true);
}

document.addEventListener('DOMContentLoaded', () => {
    setupTransactionRows();
    initTransactionDialog();

    document.querySelectorAll('form[data-prevent-double-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-submit-button]');
            if (!button) return;

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = form.dataset.submitLabel || 'Memproses…';
        }, { once: true });
    });
});
