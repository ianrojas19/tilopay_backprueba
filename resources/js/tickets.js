// State
let currentPage = 1;
let lastPage = 1;
let currentDetailTicket = null;
let deleteTargetId = null;

// Helpers

function getUserId() {
    const val = document.getElementById('user-id-input').value.trim();
    return val ? parseInt(val, 10) : null;
}

function apiHeaders(extra = {}) {
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-User-Id': String(getUserId()),
        ...extra,
    };
    return headers;
}

function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('es-CR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function statusBadge(status) {
    if (status === 'open') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-emerald-600">Abierto</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-rose-600">Cerrado</span>';
}

// Toasts

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const colors = {
        success: 'bg-emerald-50 border-emerald-200 text-emerald-700',
        error: 'bg-rose-50 border-rose-200 text-rose-700',
        warning: 'bg-amber-50 border-amber-200 text-amber-700',
        info: 'bg-indigo-50 border-indigo-200 text-indigo-700',
    };

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-3 px-5 py-3 rounded-xl border text-sm font-medium shadow-md transition-all duration-300 translate-y-2 opacity-0 ${colors[type]}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// UI State

function showState(state) {
    ['empty-state', 'loading-state', 'error-state', 'tickets-table-wrapper'].forEach(id => {
        document.getElementById(id).classList.add('hidden');
    });
    if (state) document.getElementById(state).classList.remove('hidden');
}



// Fetch Tickets

async function loadTickets(page = 1) {
    const userId = getUserId();
    if (!userId) {
        showToast('Ingrese un User ID válido', 'warning');
        return;
    }

    currentPage = page;
    showState('loading-state');

    try {
        const res = await fetch(`/api/tickets?page=${page}`, {
            headers: apiHeaders(),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw { status: res.status, data: err };
        }

        const data = await res.json();
        renderTickets(data);
        showState('tickets-table-wrapper');

        // Update badge
        const badge = document.getElementById('user-badge');
        badge.textContent = `Usuario ${userId}`;
        badge.classList.remove('hidden');

    } catch (err) {
        handleApiError(err);
    }
}

function renderTickets(data) {
    const tbody = document.getElementById('tickets-body');
    const tickets = data.data || [];

    if (tickets.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                    No se encontraron tickets para este usuario.
                </td>
            </tr>`;
    } else {
        tbody.innerHTML = tickets.map(t => `
            <tr class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-3.5 text-gray-400 font-mono text-xs">#${t.id}</td>
                <td class="px-6 py-3.5">
                    <p class="text-gray-900 font-medium text-sm truncate max-w-xs">${escapeHtml(t.subject)}</p>
                </td>
                <td class="px-6 py-3.5">${statusBadge(t.status)}</td>
                <td class="px-6 py-3.5 text-gray-400 text-xs">${formatDate(t.created_at)}</td>
                <td class="px-6 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                        <button onclick='showTicketDetail(${t.id})' title="Ver detalle" class="p-1.5 text-gray-400 hover:text-indigo-600 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button onclick='openEditModalById(${t.id})' title="Editar" class="p-1.5 text-gray-400 hover:text-amber-600 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button onclick='confirmDelete(${t.id})' title="Eliminar" class="p-1.5 text-gray-400 hover:text-rose-600 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Pagination
    lastPage = data.last_page || 1;
    document.getElementById('btn-prev').disabled = currentPage <= 1;
    document.getElementById('btn-next').disabled = currentPage >= lastPage;
    document.getElementById('pagination-info').textContent = `${data.current_page} / ${data.last_page}`;
    document.getElementById('pagination-header').classList.remove('hidden');

    // Total count
    const countEl = document.getElementById('ticket-count');
    countEl.textContent = `(${data.total} tickets en total)`;
    countEl.classList.remove('hidden');
}

function changePage(delta) {
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= lastPage) {
        loadTickets(newPage);
    }
}

// Show Detail

async function showTicketDetail(id) {
    const userId = getUserId();
    if (!userId) { showToast('Ingrese un User ID', 'warning'); return; }

    try {
        const res = await fetch(`/api/tickets/${id}`, { headers: apiHeaders() });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw { status: res.status, data: err };
        }

        const ticket = await res.json();
        currentDetailTicket = ticket;

        document.getElementById('detail-id').textContent = ticket.id;
        document.getElementById('detail-subject').textContent = ticket.subject;
        document.getElementById('detail-body').textContent = ticket.body;
        document.getElementById('detail-created').textContent = formatDate(ticket.created_at);
        document.getElementById('detail-updated').textContent = formatDate(ticket.updated_at);
        document.getElementById('detail-status-badge').innerHTML = statusBadge(ticket.status);

        document.getElementById('modal-detail').classList.remove('hidden');
    } catch (err) {
        handleApiError(err);
    }
}

// Create / Edit Modals

function openCreateModal() {
    const userId = getUserId();
    if (!userId) { showToast('Ingrese un User ID primero', 'warning'); return; }

    document.getElementById('form-ticket-id').value = '';
    document.getElementById('form-subject').value = '';
    document.getElementById('form-body').value = '';
    document.getElementById('form-status').value = 'open';
    document.getElementById('status-field').classList.add('hidden');
    document.getElementById('form-errors').classList.add('hidden');
    document.getElementById('modal-form-title').textContent = 'Nuevo Ticket';
    document.getElementById('form-submit-btn').textContent = 'Crear Ticket';
    document.getElementById('modal-form').classList.remove('hidden');
}

function openEditModal(ticket) {
    closeModals();
    document.getElementById('form-ticket-id').value = ticket.id;
    document.getElementById('form-subject').value = ticket.subject;
    document.getElementById('form-body').value = ticket.body;
    document.getElementById('form-status').value = ticket.status;
    document.getElementById('status-field').classList.remove('hidden');
    document.getElementById('form-errors').classList.add('hidden');
    document.getElementById('modal-form-title').textContent = `Editar Ticket #${ticket.id}`;
    document.getElementById('form-submit-btn').textContent = 'Guardar Cambios';
    document.getElementById('modal-form').classList.remove('hidden');
}

async function openEditModalById(id) {
    const userId = getUserId();
    if (!userId) { showToast('Ingrese un User ID', 'warning'); return; }

    try {
        const res = await fetch(`/api/tickets/${id}`, { headers: apiHeaders() });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw { status: res.status, data: err };
        }
        const ticket = await res.json();
        openEditModal(ticket);
    } catch (err) {
        handleApiError(err);
    }
}

// Submit Form

async function submitTicketForm(e) {
    e.preventDefault();
    const userId = getUserId();
    if (!userId) { showToast('Ingrese un User ID', 'warning'); return; }

    const ticketId = document.getElementById('form-ticket-id').value;
    const isEdit = !!ticketId;

    const body = {
        subject: document.getElementById('form-subject').value,
        body: document.getElementById('form-body').value,
    };
    if (isEdit) {
        body.status = document.getElementById('form-status').value;
    }

    try {
        const url = isEdit ? `/api/tickets/${ticketId}` : '/api/tickets';
        const method = isEdit ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: apiHeaders(),
            body: JSON.stringify(body),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            if (res.status === 422) {
                showFormErrors(err.messages || {});
                return;
            }
            throw { status: res.status, data: err };
        }

        closeModals();
        showToast(isEdit ? 'Ticket actualizado exitosamente' : 'Ticket creado exitosamente', 'success');
        loadTickets(isEdit ? currentPage : 1);
    } catch (err) {
        handleApiError(err);
    }
}

function showFormErrors(messages) {
    const el = document.getElementById('form-errors');
    const errs = Object.values(messages).flat();
    el.innerHTML = errs.map(e => `<p>• ${escapeHtml(e)}</p>`).join('');
    el.classList.remove('hidden');
}

// Delete

function confirmDelete(id) {
    deleteTargetId = id;
    document.getElementById('delete-ticket-id').textContent = id;
    closeModals();
    document.getElementById('modal-delete').classList.remove('hidden');
}

async function executeDelete() {
    const userId = getUserId();
    if (!userId || !deleteTargetId) return;

    try {
        const res = await fetch(`/api/tickets/${deleteTargetId}`, {
            method: 'DELETE',
            headers: apiHeaders(),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw { status: res.status, data: err };
        }

        closeModals();
        showToast('Ticket eliminado exitosamente', 'success');
        loadTickets(currentPage);
    } catch (err) {
        handleApiError(err);
    }
}

// IDOR Test

async function testIdor() {
    const userId = getUserId();
    const ticketId = document.getElementById('idor-ticket-id').value.trim();

    if (!userId) { showToast('Ingrese un User ID primero', 'warning'); return; }
    if (!ticketId) { showToast('Ingrese un ID de ticket para probar', 'warning'); return; }

    const resultEl = document.getElementById('idor-result');

    try {
        const res = await fetch(`/api/tickets/${ticketId}`, {
            headers: apiHeaders(),
        });

        const data = await res.json().catch(() => ({}));
        const correlationId = res.headers.get('X-Correlation-Id') || '—';

        resultEl.classList.remove('hidden');

        if (res.status === 404) {
            resultEl.className = 'mt-4 rounded-xl p-4 text-sm font-mono bg-emerald-50 border border-emerald-200 text-emerald-700';
            resultEl.innerHTML = `
                <p class="font-bold mb-1">Protección IDOR exitosa (${res.status})</p>
                <p class="text-xs opacity-80">El acceso fue correctamente bloqueado.</p>
                <p class="text-xs opacity-60 mt-1">Correlation-Id: ${escapeHtml(correlationId)}</p>
                <pre class="mt-2 text-xs opacity-70 whitespace-pre-wrap">${JSON.stringify(data, null, 2)}</pre>
            `;
        } else if (res.ok) {
            resultEl.className = 'mt-4 rounded-xl p-4 text-sm font-mono bg-amber-50 border border-amber-200 text-amber-700';
            resultEl.innerHTML = `
                <p class="font-bold mb-1">Acceso permitido (${res.status})</p>
                <p class="text-xs opacity-80">El ticket pertenece a este usuario, entonces el acceso es permitido.</p>
                <p class="text-xs opacity-60 mt-1">Correlation-Id: ${escapeHtml(correlationId)}</p>
            `;
        } else {
            resultEl.className = 'mt-4 rounded-xl p-4 text-sm font-mono bg-rose-50 border border-rose-200 text-rose-700';
            resultEl.innerHTML = `
                <p class="font-bold mb-1">Error (${res.status})</p>
                <pre class="mt-2 text-xs opacity-70 whitespace-pre-wrap">${JSON.stringify(data, null, 2)}</pre>
            `;
        }
    } catch (err) {
        handleApiError(err);
    }
}

// Error Handling

function handleApiError(err) {
    if (err.status) {
        const msg = err.data?.message || err.data?.error || `Error ${err.status}`;
        const detail = err.status === 401 ? 'Verifique que el X-User-Id sea válido' :
            err.status === 404 ? 'El recurso no fue encontrado' :
                err.status === 422 ? 'Datos de entrada inválidos' : '';

        if (err.status === 401 || err.status >= 500) {
            document.getElementById('error-message').textContent = msg;
            document.getElementById('error-detail').textContent = detail;
            showState('error-state');
        }

        showToast(msg, 'error');
    } else {
        showToast('Error de conexión con el servidor', 'error');
        console.error('API Error:', err);
    }
}

// Utilities

function closeModals() {
    ['modal-form', 'modal-detail', 'modal-delete'].forEach(id => {
        document.getElementById(id)?.classList.add('hidden');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Keyboard Shortcuts

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModals();
});

window.loadTickets = loadTickets;
window.openCreateModal = openCreateModal;
window.openEditModal = openEditModal;
window.openEditModalById = openEditModalById;
window.submitTicketForm = submitTicketForm;
window.showTicketDetail = showTicketDetail;
window.confirmDelete = confirmDelete;
window.executeDelete = executeDelete;
window.testIdor = testIdor;
window.changePage = changePage;
window.closeModals = closeModals;

Object.defineProperty(window, 'currentDetailTicket', {
    get: () => currentDetailTicket,
    set: (v) => { currentDetailTicket = v; },
});
