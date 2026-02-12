@extends('layouts.app')

@section('title', 'Gestión de Tickets')

@section('content')
{{-- User ID Panel --}}
<div class="mb-8 rounded-2xl border border-gray-200 bg-white p-6">
    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-1">
            <label for="user-id-input" class="block text-sm font-semibold text-gray-700 mb-1.5">
                Identificación de Usuario
            </label>
            <p class="text-xs text-gray-500 mb-3">Ingrese un ID de usuario (1–10) para simular el header <code class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">X-User-Id</code></p>
            <input
                id="user-id-input"
                type="number"
                min="1"
                value="1"
                placeholder="Ej: 1"
                class="w-full sm:w-48 bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
            >
        </div>
        <div class="flex gap-3">
            <button
                id="btn-load-tickets"
                onclick="loadTickets()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Cargar Tickets
            </button>
            <button
                onclick="openCreateModal()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Ticket
            </button>
        </div>
    </div>
</div>


{{-- Tickets Table --}}
<div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
    {{-- Table header with inline pagination --}}
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-base font-bold text-gray-900">Mis Tickets</h2>
            <span id="ticket-count" class="hidden text-xs text-gray-400"></span>
            <span id="user-badge" class="hidden text-xs bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-full px-3 py-1 font-medium"></span>
        </div>
        <div id="pagination-header" class="hidden flex items-center gap-2">
            <button id="btn-prev" onclick="changePage(-1)" disabled class="px-2 py-1 text-xs text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">&lt;</button>
            <span id="pagination-info" class="text-xs text-gray-500"></span>
            <button id="btn-next" onclick="changePage(1)" disabled class="px-2 py-1 text-xs text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer">&gt;</button>
        </div>
    </div>

    {{-- Empty state --}}
    <div id="empty-state" class="py-20 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <p class="text-gray-400 text-sm">Ingrese su User ID y presione <strong class="text-gray-600">"Cargar Tickets"</strong></p>
    </div>

    {{-- Loading spinner --}}
    <div id="loading-state" class="hidden py-20 text-center">
        <div class="inline-block w-8 h-8 border-2 border-indigo-200 border-t-indigo-500 rounded-full animate-spin"></div>
        <p class="text-gray-400 text-sm mt-4">Cargando tickets...</p>
    </div>

    {{-- Error state --}}
    <div id="error-state" class="hidden py-20 text-center">
        <svg class="w-16 h-16 mx-auto text-rose-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-rose-600 text-sm font-medium" id="error-message">Error</p>
        <p class="text-gray-400 text-xs mt-1" id="error-detail"></p>
    </div>

    {{-- Table --}}
    <div id="tickets-table-wrapper" class="hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Asunto</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tickets-body" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>

    </div>
</div>

{{-- IDOR Test Section --}}
<div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <h3 class="text-sm font-bold text-amber-700 flex items-center gap-2 mb-2">
        Prueba de Seguridad IDOR
    </h3>
    <p class="text-xs text-gray-600 mb-4">Intente acceder a un <strong class="text-gray-900">ticket</strong> que pertenece a <strong class="text-gray-900">otro usuario</strong>. La API debería responder con <span class="text-red-600 font-medium">404 Not Found</span>.</p>
    <div class="flex flex-col sm:flex-row gap-3">
        <input
            id="idor-ticket-id"
            type="number"
            min="1"
            placeholder="ID del ticket ajeno"
            class="w-full sm:w-48 bg-white border border-amber-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all"
        >
        <button
            onclick="testIdor()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Probar IDOR
        </button>
    </div>
    <div id="idor-result" class="hidden mt-4 rounded-xl p-4 text-sm font-mono"></div>
</div>

{{-- Create / Edit Modal --}}
<div id="modal-form" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white border border-gray-200 rounded-2xl w-full max-w-lg shadow-lg animate-[fadeInUp_0.2s_ease-out]">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 id="modal-form-title" class="text-base font-bold text-gray-900">Nuevo Ticket</h3>
                <button onclick="closeModals()" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="ticket-form" onsubmit="submitTicketForm(event)" class="p-6 space-y-5">
                <input type="hidden" id="form-ticket-id" value="">
                <div>
                    <label for="form-subject" class="block text-sm font-medium text-gray-700 mb-1.5">Asunto</label>
                    <input
                        id="form-subject"
                        type="text"
                        maxlength="120"
                        required
                        placeholder="Descripción breve del problema"
                        class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                    >
                    <p class="text-xs text-gray-400 mt-1">Máximo 120 caracteres</p>
                </div>
                <div>
                    <label for="form-body" class="block text-sm font-medium text-gray-700 mb-1.5">Contenido</label>
                    <textarea
                        id="form-body"
                        rows="4"
                        required
                        placeholder="Descripción detallada del ticket..."
                        class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all resize-none"
                    ></textarea>
                </div>
                <div id="status-field" class="hidden">
                    <label for="form-status" class="block text-sm font-medium text-gray-700 mb-1.5">Estado</label>
                    <select
                        id="form-status"
                        class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                    >
                        <option value="open">Abierto</option>
                        <option value="closed">Cerrado</option>
                    </select>
                </div>
                <div id="form-errors" class="hidden rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-600"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModals()" class="px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">Cancelar</button>
                    <button type="submit" id="form-submit-btn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 cursor-pointer">
                        Crear Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div id="modal-detail" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white border border-gray-200 rounded-2xl w-full max-w-lg shadow-lg animate-[fadeInUp_0.2s_ease-out]">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span id="detail-status-badge"></span>
                    Ticket #<span id="detail-id"></span>
                </h3>
                <button onclick="closeModals()" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Asunto</p>
                    <p class="text-gray-900 font-medium" id="detail-subject"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Contenido</p>
                    <p class="text-gray-600 text-sm whitespace-pre-wrap leading-relaxed" id="detail-body"></p>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Creado</p>
                        <p class="text-gray-600 text-sm" id="detail-created"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Actualizado</p>
                        <p class="text-gray-600 text-sm" id="detail-updated"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button onclick="openEditModal(currentDetailTicket)" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium rounded-xl transition-all active:scale-95 cursor-pointer">Editar</button>
                    <button onclick="confirmDelete(currentDetailTicket.id)" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition-all active:scale-95 cursor-pointer">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete confirmation Modal --}}
<div id="modal-delete" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white border border-gray-200 rounded-2xl w-full max-w-sm shadow-lg animate-[fadeInUp_0.2s_ease-out]">
            <div class="p-6 text-center space-y-4">
                <div class="w-14 h-14 mx-auto rounded-full bg-rose-50 border border-rose-200 flex items-center justify-center">
                    <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">¿Eliminar ticket?</h3>
                <p class="text-sm text-gray-500">Ticket #<span id="delete-ticket-id"></span> será eliminado permanentemente.</p>
                <div class="flex justify-center gap-3 pt-2">
                    <button onclick="closeModals()" class="px-5 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">Cancelar</button>
                    <button onclick="executeDelete()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl transition-all active:scale-95 cursor-pointer">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
