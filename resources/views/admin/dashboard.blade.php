@extends('layouts.app')

@section('title', 'Panel de Administración — LearnKoreapp')

@section('content')
<div x-data="adminDashboard()" x-init="init()" style="max-width: 1200px; margin: 0 auto;">

    {{-- Header --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.3rem; color: var(--color-warning);">
            ⚙️ Panel de Administración
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Control total de usuarios, reportes, vocabulario y taxonomía
        </p>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:1rem; border-bottom:1px solid var(--color-border); margin-bottom:2rem; overflow-x:auto;">
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="currentTab = tab.id; if(tab.load) tab.load()" 
                    :class="currentTab === tab.id ? 'active-tab' : 'inactive-tab'"
                    style="padding:0.75rem 1.5rem; font-weight:600; font-size:0.95rem; background:none; border:none; cursor:pointer; border-bottom:2px solid transparent; transition:all 0.2s;"
                    x-text="tab.name"></button>
        </template>
    </div>
    
    <style>
        .active-tab { color: var(--color-warning) !important; border-bottom-color: var(--color-warning) !important; }
        .inactive-tab { color: var(--color-text-muted) !important; }
        .inactive-tab:hover { color: var(--color-text) !important; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .admin-table th { padding: 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text-muted); font-size: 0.85rem; text-transform: uppercase; }
        .admin-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .action-btn { background: none; border: none; cursor: pointer; color: var(--color-accent-soft); font-size: 0.85rem; margin-right: 0.5rem; transition:color 0.2s; }
        .action-btn:hover { color: var(--color-accent); }
        .danger-btn { color: var(--color-danger); }
        .danger-btn:hover { color: #fca5a5; }
    </style>

    {{-- Cola de Revisión (Livewire, Fases 1-2) --}}
    <div x-show="currentTab === 'queue'" class="card">
        @livewire('admin.pending-queue')
    </div>

    {{-- Reportes --}}
    <div x-show="currentTab === 'reports'" style="display:none;" class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem;">
            <h3>Tickets de Usuarios</h3>
            <select x-model="reports.filterStatus" @change="loadReports()" style="background:var(--color-bg); color:white; border:1px solid var(--color-border); border-radius:8px; padding:0.4rem 0.8rem;">
                <option value="">Todos los estados</option>
                <option value="pending">Pendientes</option>
                <option value="reviewing">En revisión</option>
                <option value="resolved">Resueltos</option>
                <option value="dismissed">Descartados</option>
            </select>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID / Fecha</th>
                    <th>Usuario</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="r in reports.data" :key="r.id">
                    <tr>
                        <td>
                            <div>#<span x-text="r.id"></span></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted)" x-text="new Date(r.created_at).toLocaleDateString()"></div>
                        </td>
                        <td x-text="r.user ? r.user.name : 'Anónimo'"></td>
                        <td>
                            <div x-text="r.category_label" style="font-weight:500;"></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted)" x-text="r.description.substring(0, 50) + '...'"></div>
                        </td>
                        <td>
                            <span class="badge" :style="'background:' + r.status_color + '22; color:' + r.status_color" x-text="r.status_label"></span>
                        </td>
                        <td>
                            <button class="action-btn" @click="viewReport(r)">Revisar</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="reports.data.length === 0"><td colspan="5" style="text-align:center; color:var(--color-text-muted)">No hay reportes.</td></tr>
            </tbody>
        </table>

        <!-- Paginación Reportes -->
        <div style="margin-top:1rem; display:flex; justify-content:space-between;" x-show="reports.meta.last_page > 1">
            <button class="action-btn" :disabled="reports.meta.current_page === 1" @click="loadReports(reports.meta.current_page - 1)">Anterior</button>
            <span style="font-size:0.9rem; color:var(--color-text-muted)">Pág <span x-text="reports.meta.current_page"></span> de <span x-text="reports.meta.last_page"></span></span>
            <button class="action-btn" :disabled="reports.meta.current_page === reports.meta.last_page" @click="loadReports(reports.meta.current_page + 1)">Siguiente</button>
        </div>
    </div>

    {{-- Usuarios --}}
    <div x-show="currentTab === 'users'" style="display:none;" class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem;">
            <h3>Gestión de Usuarios</h3>
            <input type="text" x-model="users.search" @input.debounce.500ms="loadUsers()" placeholder="Buscar por email o nombre..." style="background:var(--color-bg); color:white; border:1px solid var(--color-border); border-radius:8px; padding:0.4rem 0.8rem; width:300px;">
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID / Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Estadísticas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="u in users.data" :key="u.id">
                    <tr>
                        <td>
                            <div style="font-weight:600;" x-text="u.name"></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted)">ID: <span x-text="u.id"></span></div>
                        </td>
                        <td x-text="u.email"></td>
                        <td>
                            <span class="badge" :style="u.role === 'admin' ? 'background:rgba(251,191,36,0.2); color:var(--color-warning)' : 'background:rgba(148,163,184,0.2); color:var(--color-text-muted)'" x-text="u.role.toUpperCase()"></span>
                        </td>
                        <td>
                            <span class="badge" :style="u.is_active ? 'background:rgba(52,211,153,0.2); color:var(--color-success)' : 'background:rgba(248,113,113,0.2); color:var(--color-danger)'" x-text="u.is_active ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td>
                            <div style="font-size:0.8rem; color:var(--color-text-muted)">
                                Repasos: <span x-text="u.logs_count || 0"></span><br>
                                Reportes: <span x-text="u.reports_count || 0"></span>
                            </div>
                        </td>
                        <td>
                            <button class="action-btn" @click="toggleUserRole(u)" x-text="u.role === 'admin' ? 'Quitar Admin' : 'Hacer Admin'"></button>
                            <button class="action-btn" :class="u.is_active ? 'danger-btn' : ''" @click="toggleUserActive(u)" x-text="u.is_active ? 'Desactivar' : 'Activar'"></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Paginación Usuarios -->
        <div style="margin-top:1rem; display:flex; justify-content:space-between;" x-show="users.meta.last_page > 1">
            <button class="action-btn" :disabled="users.meta.current_page === 1" @click="loadUsers(users.meta.current_page - 1)">Anterior</button>
            <span style="font-size:0.9rem; color:var(--color-text-muted)">Pág <span x-text="users.meta.current_page"></span> de <span x-text="users.meta.last_page"></span></span>
            <button class="action-btn" :disabled="users.meta.current_page === users.meta.last_page" @click="loadUsers(users.meta.current_page + 1)">Siguiente</button>
        </div>
    </div>

    {{-- Vocabulario --}}
    <div x-show="currentTab === 'compounds'" style="display:none;" class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem;">
            <h3>Diccionario Global</h3>
            <input type="text" x-model="compounds.search" @input.debounce.500ms="loadCompounds()" placeholder="Buscar término..." style="background:var(--color-bg); color:white; border:1px solid var(--color-border); border-radius:8px; padding:0.4rem 0.8rem; width:300px;">
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hangul</th>
                    <th>Traducción</th>
                    <th>Estado</th>
                    <th>Progreso (Usos)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="c in compounds.data" :key="c.id">
                    <tr>
                        <td x-text="c.id"></td>
                        <td x-text="c.full_text" style="font-weight:600; font-size:1.1rem;"></td>
                        <td>
                            <input type="text" x-model="c.translation" @blur="updateCompound(c)" style="background:transparent; border:1px solid var(--color-border); color:white; padding:0.2rem 0.4rem; border-radius:4px; width:100%;">
                        </td>
                        <td>
                            <select x-model="c.status" @change="updateCompound(c)" style="background:transparent; color:var(--color-text-muted); border:none; outline:none;">
                                <option value="pending_review">Pendiente</option>
                                <option value="verified">Verificado</option>
                                <option value="rejected">Rechazado</option>
                            </select>
                        </td>
                        <td x-text="c.users_count"></td>
                        <td>
                            <button class="action-btn danger-btn" @click="deleteCompound(c)">Eliminar</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Paginación Compounds -->
        <div style="margin-top:1rem; display:flex; justify-content:space-between;" x-show="compounds.meta.last_page > 1">
            <button class="action-btn" :disabled="compounds.meta.current_page === 1" @click="loadCompounds(compounds.meta.current_page - 1)">Anterior</button>
            <span style="font-size:0.9rem; color:var(--color-text-muted)">Pág <span x-text="compounds.meta.current_page"></span> de <span x-text="compounds.meta.last_page"></span></span>
            <button class="action-btn" :disabled="compounds.meta.current_page === compounds.meta.last_page" @click="loadCompounds(compounds.meta.current_page + 1)">Siguiente</button>
        </div>
    </div>

    {{-- Tags --}}
    <div x-show="currentTab === 'tags'" style="display:none;" class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:1.5rem;">
            <h3>Taxonomía & Tags</h3>
            <div style="color:var(--color-text-muted); font-size:0.9rem;">
                Fusión y renombre de etiquetas globales
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
            <div>
                <h4 style="margin-bottom:1rem;">Lista de Tags</h4>
                <div style="max-height:500px; overflow-y:auto; border:1px solid var(--color-border); border-radius:8px;">
                    <table class="admin-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Capa</th>
                                <th>Usos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="t in tags.data" :key="t.id">
                                <tr>
                                    <td>
                                        <input type="text" x-model="t.name" @blur="updateTag(t)" style="background:transparent; border:1px solid transparent; color:white; padding:0.2rem; border-radius:4px; width:100%;">
                                    </td>
                                    <td>
                                        <select x-model="t.layer" @change="updateTag(t)" style="background:transparent; color:var(--color-text-muted); border:none; outline:none;">
                                            <option value="grammar">Grammar</option>
                                            <option value="register">Register</option>
                                            <option value="thematic">Thematic</option>
                                        </select>
                                    </td>
                                    <td x-text="t.usage_count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div style="background:rgba(255,255,255,0.02); padding:1.5rem; border-radius:12px; border:1px solid var(--color-border);">
                <h4 style="margin-bottom:1rem; color:var(--color-warning);">Fusionar Tags</h4>
                <p style="font-size:0.85rem; color:var(--color-text-muted); margin-bottom:1rem;">
                    Mueve todas las relaciones de un tag de origen a un tag de destino, y elimina el tag de origen. Útil para limpiar duplicados.
                </p>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Tag Origen (Se eliminará)</label>
                        <select x-model="tags.mergeSource" class="w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white">
                            <option value="">Selecciona...</option>
                            <template x-for="t in tags.data"><option :value="t.id" x-text="t.name + ' (' + t.usage_count + ' usos)'"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Tag Destino (Se conservará)</label>
                        <select x-model="tags.mergeTarget" class="w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white">
                            <option value="">Selecciona...</option>
                            <template x-for="t in tags.data"><option :value="t.id" x-text="t.name"></option></template>
                        </select>
                    </div>
                    <button @click="mergeTags()" style="padding:0.6rem; background:var(--color-warning); color:#000; font-weight:700; border:none; border-radius:8px; cursor:pointer; margin-top:0.5rem;">
                        Ejecutar Fusión
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Log --}}
    <div x-show="currentTab === 'log'" style="display:none;" class="card">
        <h3 style="margin-bottom:1.5rem;">Registro de Auditoría (Logs)</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Admin</th>
                    <th>Acción</th>
                    <th>Target</th>
                    <th>Detalles JSON</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="l in auditLog.data" :key="l.id">
                    <tr>
                        <td x-text="new Date(l.created_at).toLocaleString()" style="font-size:0.8rem;"></td>
                        <td x-text="l.admin ? l.admin.name : 'Desconocido'"></td>
                        <td><span class="badge" style="background:rgba(124,110,245,0.2); color:var(--color-accent-soft);" x-text="l.action_type"></span></td>
                        <td x-text="l.target_type + ' #' + l.target_id"></td>
                        <td><pre style="font-size:0.7rem; color:var(--color-text-muted); background:var(--color-bg); padding:0.5rem; border-radius:4px; max-width:250px; overflow-x:auto;" x-text="JSON.stringify(l.payload, null, 2)"></pre></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <!-- Paginación Logs -->
        <div style="margin-top:1rem; display:flex; justify-content:space-between;" x-show="auditLog.meta.last_page > 1">
            <button class="action-btn" :disabled="auditLog.meta.current_page === 1" @click="loadLog(auditLog.meta.current_page - 1)">Anterior</button>
            <span style="font-size:0.9rem; color:var(--color-text-muted)">Pág <span x-text="auditLog.meta.current_page"></span> de <span x-text="auditLog.meta.last_page"></span></span>
            <button class="action-btn" :disabled="auditLog.meta.current_page === auditLog.meta.last_page" @click="loadLog(auditLog.meta.current_page + 1)">Siguiente</button>
        </div>
    </div>

</div>

<!-- Modal Reporte Admin -->
<div id="reportModal" x-data="{ open: false, report: null }" @open-report.window="report = $event.detail; open = true" x-show="open" style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-700" x-show="open">
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4" x-if="report">
                <h3 class="text-xl leading-6 font-medium text-white mb-4">Revisar Reporte #<span x-text="report?.id"></span></h3>
                
                <div class="bg-gray-900 p-4 rounded-lg border border-gray-700 mb-4">
                    <div class="text-sm text-gray-400 mb-1">De: <span class="text-white" x-text="report?.user?.name"></span> (<span x-text="report?.user?.email"></span>)</div>
                    <div class="text-sm text-gray-400 mb-1">Categoría: <strong class="text-white" x-text="report?.category_label"></strong></div>
                    <p class="text-white mt-3" style="white-space: pre-wrap;" x-text="report?.description"></p>
                    
                    <template x-if="report?.related_item_type">
                        <div class="mt-3 text-sm text-indigo-400 font-medium">
                            Relacionado con: <span x-text="report?.related_item_type"></span> #<span x-text="report?.related_item_id"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Estado del Reporte</label>
                        <select x-model="report.status" class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white">
                            <option value="pending">Pendiente</option>
                            <option value="reviewing">En revisión</option>
                            <option value="resolved">Resuelto</option>
                            <option value="dismissed">Descartado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Notas internas (Admin)</label>
                        <textarea x-model="report.admin_notes" rows="3" class="mt-1 block w-full bg-gray-900 border border-gray-700 rounded-md py-2 px-3 text-white placeholder-gray-500" placeholder="Escribe la resolución o notas del equipo..."></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-gray-750 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                <button type="button" @click="saveReport(report); open = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Guardar Cambios
                </button>
                <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-gray-300 hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function adminDashboard() {
    return {
        currentTab: 'queue',
        tabs: [
            { id: 'queue', name: 'Pendientes IA' },
            { id: 'reports', name: 'Reportes', load: () => this.loadReports() },
            { id: 'users', name: 'Usuarios', load: () => this.loadUsers() },
            { id: 'compounds', name: 'Vocabulario', load: () => this.loadCompounds() },
            { id: 'tags', name: 'Tags', load: () => this.loadTags() },
            { id: 'log', name: 'Auditoría', load: () => this.loadLog() },
        ],
        reports: { data: [], meta: {}, filterStatus: '' },
        users: { data: [], meta: {}, search: '' },
        compounds: { data: [], meta: {}, search: '' },
        tags: { data: [], mergeSource: '', mergeTarget: '' },
        auditLog: { data: [], meta: {} },

        init() {
            // Check url hash for tab
            const hash = window.location.hash.replace('#', '');
            if (this.tabs.find(t => t.id === hash)) {
                this.currentTab = hash;
                const tab = this.tabs.find(t => t.id === hash);
                if(tab.load) tab.load();
            }
            this.$watch('currentTab', val => { window.location.hash = val; });
        },

        async fetchApi(url, options = {}) {
            options.headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            };
            const res = await fetch(url, options);
            if (!res.ok) {
                const err = await res.json();
                alert(err.error || err.message || 'Error en la petición');
                throw new Error('API Error');
            }
            return res.json();
        },

        // REPORTS
        async loadReports(page = 1) {
            let url = `/api/admin/reports?page=${page}`;
            if(this.reports.filterStatus) url += `&status=${this.reports.filterStatus}`;
            const res = await this.fetchApi(url);
            this.reports.data = res.data;
            this.reports.meta = res.meta;
        },
        viewReport(report) {
            this.$dispatch('open-report', report);
        },

        // USERS
        async loadUsers(page = 1) {
            let url = `/api/admin/users?page=${page}`;
            if(this.users.search) url += `&search=${this.users.search}`;
            const res = await this.fetchApi(url);
            this.users.data = res.data;
            this.users.meta = res.meta;
        },
        async toggleUserRole(u) {
            if(!confirm(`¿Cambiar rol de ${u.name}?`)) return;
            const newRole = u.role === 'admin' ? 'user' : 'admin';
            await this.fetchApi(`/api/admin/users/${u.id}/role`, { method:'PUT', body: JSON.stringify({role: newRole}) });
            this.loadUsers(this.users.meta.current_page);
        },
        async toggleUserActive(u) {
            if(!confirm(`¿${u.is_active ? 'Desactivar' : 'Activar'} a ${u.name}?`)) return;
            await this.fetchApi(`/api/admin/users/${u.id}/active`, { method:'PUT' });
            this.loadUsers(this.users.meta.current_page);
        },

        // COMPOUNDS
        async loadCompounds(page = 1) {
            let url = `/api/admin/compounds?page=${page}`;
            if(this.compounds.search) url += `&search=${this.compounds.search}`;
            const res = await this.fetchApi(url);
            this.compounds.data = res.data;
            this.compounds.meta = res.meta;
        },
        async updateCompound(c) {
            await this.fetchApi(`/api/admin/compounds/${c.id}`, { 
                method:'PUT', 
                body: JSON.stringify({ translation: c.translation, status: c.status }) 
            });
        },
        async deleteCompound(c) {
            if(!confirm(`¿Eliminar permanentemente ${c.full_text}?`)) return;
            try {
                await this.fetchApi(`/api/admin/compounds/${c.id}`, { method:'DELETE' });
                this.loadCompounds(this.compounds.meta.current_page);
            } catch(e) {} // Error handled in fetchApi
        },

        // TAGS
        async loadTags() {
            const res = await this.fetchApi('/api/admin/tags');
            this.tags.data = res.data;
        },
        async updateTag(t) {
            await this.fetchApi(`/api/admin/tags/${t.id}`, { 
                method:'PUT', 
                body: JSON.stringify({ name: t.name, layer: t.layer }) 
            });
        },
        async mergeTags() {
            if(!this.tags.mergeSource || !this.tags.mergeTarget) {
                alert('Selecciona origen y destino.'); return;
            }
            if(!confirm('¿Seguro? El tag origen se ELIMINARÁ y sus usos pasarán al destino.')) return;
            await this.fetchApi(`/api/admin/tags/merge`, { 
                method:'POST', 
                body: JSON.stringify({ source_id: this.tags.mergeSource, target_id: this.tags.mergeTarget }) 
            });
            alert('Tags fusionados con éxito.');
            this.tags.mergeSource = '';
            this.tags.mergeTarget = '';
            this.loadTags();
        },

        // LOG
        async loadLog(page = 1) {
            const res = await this.fetchApi(`/api/admin/log?page=${page}`);
            this.auditLog.data = res.data;
            this.auditLog.meta = res.meta;
        }
    }
}

// Escuchar guardado de reporte desde modal
window.saveReport = async function(report) {
    try {
        const res = await fetch(`/api/admin/reports/${report.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ status: report.status, admin_notes: report.admin_notes })
        });
        if(!res.ok) throw new Error('Error al guardar reporte');
        // Recargar la tabla si la función adminDashboard loadReports está disponible
        // Esto es un poco hacky porque está fuera del scope de x-data, pero funcionará en Alpine re-triggering events
        window.dispatchEvent(new Event('hashchange')); // Forzamos recarga si el hash triggerea
        alert('Reporte actualizado.');
    } catch(e) {
        alert(e.message);
    }
};
</script>
@endpush
@endsection
