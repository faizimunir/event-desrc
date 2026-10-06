function extractSpreadsheetId(raw) {
    const value = String(raw || '').trim();
    const match = value.match(/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/);

    return match ? match[1] : value;
}

function csrfToken(form) {
    return (
        form.querySelector('input[name="_token"]')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        document.querySelector('input[name="_token"]')?.value ||
        ''
    );
}

function toast(text, variant) {
    if (window.Livewire) {
        window.Livewire.dispatch('toast-show', {
            duration: 5000,
            slots: { text },
            dataset: { variant },
        });
    }
}

function setStatus(message, variant) {
    const statusEl = document.getElementById('fetch-sheets-status');
    if (!statusEl) {
        return;
    }

    if (!message) {
        statusEl.textContent = '';
        statusEl.classList.add('hidden');
        statusEl.classList.remove('text-red-600', 'dark:text-red-400', 'text-zinc-600', 'dark:text-zinc-300');
        return;
    }

    statusEl.textContent = message;
    statusEl.classList.remove('hidden');
    statusEl.classList.toggle('text-red-600', variant === 'danger');
    statusEl.classList.toggle('dark:text-red-400', variant === 'danger');
    statusEl.classList.toggle('text-zinc-600', variant !== 'danger');
    statusEl.classList.toggle('dark:text-zinc-300', variant !== 'danger');
}

function previouslySelectedSheets(form) {
    try {
        const parsed = JSON.parse(form.dataset.previouslySelected || '[]');

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function setBusy(button, loadingEl, busy) {
    button.disabled = busy;
    button.setAttribute('aria-busy', busy ? 'true' : 'false');
    loadingEl?.classList.toggle('hidden', !busy);
}

async function fetchSheets(form, button) {
    const spreadsheetInput = form.querySelector('input[name="spreadsheet_id"]');
    const loadingEl = document.getElementById('fetch-loading');
    const container = document.getElementById('sheets-container');
    const checkboxesEl = document.getElementById('sheets-checkboxes');
    const spreadsheetId = extractSpreadsheetId(spreadsheetInput?.value);
    const fetchUrl = form.dataset.fetchSheetsUrl;
    const previouslySelected = previouslySelectedSheets(form);
    const emptyIdMessage = form.dataset.emptyIdMessage || 'Silakan masukkan Spreadsheet ID terlebih dahulu';
    const noneFoundMessage = form.dataset.noneFoundMessage || 'Tidak ada sheet ditemukan di spreadsheet ini.';
    const errorMessage = form.dataset.errorMessage || 'Terjadi kesalahan saat mengambil data.';

    if (!spreadsheetId) {
        setStatus(emptyIdMessage, 'danger');
        toast(emptyIdMessage, 'danger');
        return;
    }

    if (!fetchUrl) {
        setStatus(errorMessage, 'danger');
        toast(errorMessage, 'danger');
        return;
    }

    if (spreadsheetInput) {
        spreadsheetInput.value = spreadsheetId;
    }

    setBusy(button, loadingEl, true);
    setStatus('', '');

    try {
        const response = await fetch(fetchUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(form),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ spreadsheet_id: spreadsheetId }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data.success || !Array.isArray(data.sheets) || data.sheets.length === 0) {
            throw new Error(data.error || data.message || noneFoundMessage);
        }

        if (checkboxesEl) {
            checkboxesEl.innerHTML = '';
            data.sheets.forEach((sheet) => {
                const label = document.createElement('label');
                label.className = 'flex cursor-pointer items-center gap-2';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'selected_sheets[]';
                checkbox.value = sheet;
                checkbox.checked = previouslySelected.includes(sheet);
                checkbox.className = 'rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500';

                const span = document.createElement('span');
                span.className = 'text-sm text-zinc-700 dark:text-zinc-300';
                span.textContent = sheet;

                label.appendChild(checkbox);
                label.appendChild(span);
                checkboxesEl.appendChild(label);
            });
        }

        container?.classList.remove('hidden');
        setStatus('', '');
    } catch (error) {
        const message = error?.message || errorMessage;
        setStatus(message, 'danger');
        toast(message, 'danger');
    } finally {
        setBusy(button, loadingEl, false);
    }
}

document.addEventListener('click', (event) => {
    const fetchBtn = event.target.closest?.('[data-fetch-sheets], #fetch-sheets-btn');
    if (fetchBtn) {
        const form = fetchBtn.closest('#live-result-category-form');
        if (!form) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        fetchSheets(form, fetchBtn);
        return;
    }

    const printBtn = event.target.closest?.('[data-print-preview], #print-preview-btn');
    if (!printBtn) {
        return;
    }

    const select = document.getElementById('print-round-select');
    const url = select?.getAttribute('data-print-url');
    const round = select?.value;

    if (url && round) {
        window.open(`${url}?round=${encodeURIComponent(round)}`, '_blank', 'noopener');
    }
});
