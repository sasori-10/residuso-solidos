<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const props = defineProps({
  assignments: { type: Array, required: true },
  viewingOther: { type: Boolean, default: false },
  targetUser: { type: Object, default: null },
});

// Permisos
const page = usePage();
// Siempre devolver un array (aunque props todavía no estén listos) para evitar errores.
const userPermissions = computed(() => Array.isArray(page.props.auth?.user?.permissions) ? page.props.auth.user.permissions : []);
// Helper seguro para verificar permisos sin lanzar errores si aún no se cargan.
function hasPerm(code) {
  return Array.isArray(userPermissions.value) && userPermissions.value.includes(code);
}
// Permiso actualizado y overrides para supervisor / gestión
const canViewRecoleccion = computed(() =>
  hasPerm('verMisRecolecciones') ||
  hasPerm('supervisor.recoleccion') ||
  hasPerm('manage.recoleccion')
);

// Modal de subida
const showModal = ref(false);
const currentProg = ref(null);
const currentEmp = ref(null);
const previewUrl = ref('');

// Modal de comentario
const showCommentModal = ref(false);
const commentContent = ref('');
const commentEstado = ref('');
const commentNombre = ref('');
function openCommentModal(comentario, estado, nombre) {
  commentContent.value = comentario;
  commentEstado.value = estado;
  commentNombre.value = nombre;
  showCommentModal.value = true;
}

const uploadForm = useForm({
  programacion_id: null,
  empadronado_id: null,
  foto: null,
  estado: 'completado', // 'completado', 'no_completado', 'no_encontrado'
  comentario: '',
});

// Toast global
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  iconColor: '#002F6C',
  customClass: { popup: 'colored-toast' },
});

function openUploadModal(programacion, emp) {
  currentProg.value = programacion;
  currentEmp.value = emp;
  uploadForm.reset();
  uploadForm.clearErrors();
  uploadForm.programacion_id = programacion.id;
  uploadForm.empadronado_id = emp.id;
  uploadForm.estado = 'completado';
  uploadForm.comentario = '';
  previewUrl.value = '';
  showModal.value = true;
}

function onFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) {
    uploadForm.foto = null;
    previewUrl.value = '';
    return;
  }
  uploadForm.foto = file;
  previewUrl.value = URL.createObjectURL(file);
}

function submitUpload() {
  // Si el estado es completado, la foto es obligatoria
  if (uploadForm.estado === 'completado' && !uploadForm.foto) {
    Swal.fire({ icon: 'warning', title: 'Selecciona una foto', confirmButtonColor: '#002F6C' });
    return;
  }
  // Si el estado es no_encontrado, el comentario es obligatorio
  if (uploadForm.estado === 'no_encontrado' && !uploadForm.comentario.trim()) {
    Swal.fire({ icon: 'warning', title: 'Agrega un comentario', confirmButtonColor: '#002F6C' });
    return;
  }

  Swal.fire({
    title: 'Subiendo evidencia...',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  uploadForm.post(route('recoleccion.store'), {
    onSuccess: () => {
      Swal.close();
      showModal.value = false;
      Toast.fire({ icon: 'success', title: 'Evidencia registrada' });
      router.reload({ only: ['assignments'] });
    },
    onError: (errors) => {
      Swal.close();
      let msg = 'Por favor verifica la imagen (formatos: jpg, jpeg, png, webp; tamaño <= 5MB).';
      if (errors && errors.foto) msg = Array.isArray(errors.foto) ? errors.foto[0] : errors.foto;
      if (errors && errors.comentario) msg = Array.isArray(errors.comentario) ? errors.comentario[0] : errors.comentario;
      if (errors && errors.estado) msg = Array.isArray(errors.estado) ? errors.estado[0] : errors.estado;
      Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: '#002F6C' });
    },
    preserveScroll: true,
  });
}

function formatTime(time) {
  if (!time) return '';
  if (time.includes(':') && time.split(':').length === 3) return time.substring(0, 5);
  return time;
}

const totalPendientes = computed(() => {
  let c = 0;
  props.assignments.forEach(a => {
    a.empadronados.forEach(e => { if (!e.evidencia) c++; });
  });
  return c;
});

// Estado para modal de progreso
const showProgressModal = ref(false);
const selectedAssignment = ref(null);

// Progreso resumido para una card (x/y y %)
function getProgress(a) {
  const emps = a?.empadronados || [];
  const total = emps.length;
  const completed = emps.filter(e => e.evidencia && e.evidencia.completado).length;
  const percent = total ? Math.round((completed / total) * 100) : 0;
  return { total, completed, percent };
}

// Datos detallados para el modal
const progressData = computed(() => {
  const a = selectedAssignment.value;
  if (!a) return null;
  const emps = a.empadronados || [];
  const total = emps.length;
  const done = emps.filter(e => e.evidencia && e.evidencia.completado);
  const pendingList = emps.filter(e => !e.evidencia).map(e => e.nombre);
  const completedList = done.map(e => e.nombre);
  const completed = done.length;
  const pending = total - completed;
  const percent = total ? Math.round((completed / total) * 100) : 0;
  // Última evidencia por fecha (si existe)
  const lastAt = done.map(e => e.evidencia.created_at).sort().slice(-1)[0] || null;
  return { total, completed, pending, percent, pendingList, completedList, lastAt };
});

function openProgress(a) {
  selectedAssignment.value = a;
  showProgressModal.value = true;
}

// --- Colapsar / Expandir asignaciones ---
const expandedIds = ref(new Set());

function isExpanded(id) {
  return expandedIds.value.has(id);
}

function toggleAssignment(id) {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id);
  } else {
    expandedIds.value.add(id);
  }
  // Forzar reactividad (Set no es profundamente reactivo en mutaciones directas)
  expandedIds.value = new Set(expandedIds.value);
}

function expandAll(assignments) {
  const s = new Set();
  assignments.forEach(a => s.add(a.programacion.id));
  expandedIds.value = s;
}
function collapseAll() {
  expandedIds.value = new Set();
}

onMounted(() => {
  // Estrategia: si hay <= 3 asignaciones, expandir todas; si más, solo la primera.
  if (props.assignments.length <= 3) {
    expandAll(props.assignments);
  } else if (props.assignments.length) {
    expandedIds.value.add(props.assignments[0].programacion.id);
    expandedIds.value = new Set(expandedIds.value);
  }
});

// Si el supervisor cambia de usuario y llegan nuevas asignaciones, auto-expandir (mejor UX)
// Nota: evitamos expandir automáticamente todo en modo supervisor para no congelar la vista si hay muchas filas.
</script>

<template>
  <Head title="Mis Recolecciones" />
  <AuthenticatedLayout>
    <template #header v-if="canViewRecoleccion">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-chancay-azul">Mis Recolecciones</h2>
        <div class="text-sm text-gray-600">
          Pendientes: <span class="font-semibold text-chancay-verde">{{ totalPendientes }}</span>
        </div>
      </div>
    </template>
    <template v-if="!canViewRecoleccion">
      <div class="max-w-3xl mx-auto mt-16 bg-white shadow-lg rounded-xl p-8 border border-red-200 text-center">
        <div class="mx-auto h-16 w-16 mb-4 rounded-full bg-red-50 flex items-center justify-center ring-2 ring-red-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" /></svg>
        </div>
        <h1 class="text-xl font-bold text-chancay-azul mb-2">Acceso restringido</h1>
  <p class="text-sm text-gray-600 leading-relaxed max-w-md mx-auto">Esta sección está diseñada para usuarios con permiso <span class="font-semibold">verMisRecolecciones</span> o perfil de supervisión. Si necesitas acceso, contacta a un administrador.</p>
      </div>
    </template>
    <template v-else>
  <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        <div v-if="props.viewingOther" class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-800 flex items-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 10s2-6 8-6 8 6 8 6-2 6-8 6-8-6-8-6zm8 4a4 4 0 100-8 4 4 0 000 8z"/></svg>
          <span>Vista supervisión: revisando evidencias de <strong>{{ targetUser?.name }}</strong> (ID: {{ targetUser?.id }}).</span>
          <button @click="router.get(route('recoleccion.index'))" class="ml-auto inline-flex items-center gap-1 px-2 py-1 rounded bg-white border border-blue-300 text-blue-700 hover:bg-blue-100 transition text-[11px] font-semibold" title="Salir de supervisión">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4.5A1.5 1.5 0 014.5 3h6A1.5 1.5 0 0112 4.5v1a.5.5 0 01-1 0v-1a.5.5 0 00-.5-.5h-6a.5.5 0 00-.5.5v11a.5.5 0 00.5.5h6a.5.5 0 00.5-.5v-1a.5.5 0 011 0v1A1.5 1.5 0 0110.5 17h-6A1.5 1.5 0 013 15.5v-11z"/><path d="M14.854 10.354a.5.5 0 000-.708L12.672 7.464a.5.5 0 10-.708.708L13.293 9.5H8.5a.5.5 0 000 1h4.793l-1.329 1.329a.5.5 0 10.708.708l2.182-2.182z"/></svg>
            Salir
          </button>
        </div>
        <div v-if="!assignments.length" class="bg-white shadow-lg rounded-lg p-6 text-center text-gray-500">
          No tienes asignaciones.
        </div>

        <!-- Controles globales -->
        <div v-if="assignments.length" class="flex flex-wrap items-center gap-2 justify-end">
          <button @click="expandAll(assignments)" class="px-3 py-1.5 text-xs font-semibold rounded bg-chancay-azul text-white hover:bg-chancay-verde transition">Expandir todo</button>
            <button @click="collapseAll()" class="px-3 py-1.5 text-xs font-semibold rounded bg-gray-200 text-gray-800 hover:bg-gray-300 transition">Colapsar todo</button>
        </div>

        <div v-for="a in assignments" :key="a.programacion.id" class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-100">
          <div class="p-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-2">
              <div>
                <h3 class="text-lg font-semibold text-chancay-azul">
                  {{ a.programacion.zona }} - {{ a.programacion.sector }}
                </h3>
                <div class="text-sm text-gray-600">
                  {{ Array.isArray(a.programacion.dias) ? a.programacion.dias.join(', ') : '' }}
                  · {{ formatTime(a.programacion.hora_inicio) }} - {{ formatTime(a.programacion.hora_fin) }}
                </div>
                <div v-if="a.programacion.descripcion" class="text-sm text-gray-600">
                  {{ a.programacion.descripcion }}
                </div>
              </div>

              <!-- Acciones de supervisor: ver avance -->
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                      :class="getProgress(a).percent >= 100 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                  Avance: {{ getProgress(a).completed }}/{{ getProgress(a).total }} ({{ getProgress(a).percent }}%)
                </span>
                <button
                  @click="toggleAssignment(a.programacion.id)"
                  :class="isExpanded(a.programacion.id) ? 'bg-gray-200 text-gray-800 hover:bg-gray-300' : 'bg-chancay-verde text-white hover:bg-chancay-azul'"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-xs font-semibold transition"
                  :title="isExpanded(a.programacion.id) ? 'Colapsar' : 'Expandir'"
                >
                  <svg v-if="isExpanded(a.programacion.id)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  {{ isExpanded(a.programacion.id) ? 'Ocultar' : 'Ver' }}
                </button>
                <button
                  @click="openProgress(a)"
                  class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-chancay-azul text-white text-sm hover:bg-chancay-verde transition"
                  title="Ver detalle de avance"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3C5 3 1.73 7.11 1 10c.73 2.89 4 7 9 7s8.27-4.11 9-7c-.73-2.89-4-7-9-7zm0 12c-3.31 0-6-3.13-6.92-5 .92-1.87 3.61-5 6.92-5 3.31 0 6 3.13 6.92 5-.92 1.87-3.61 5-6.92 5zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                  </svg>
                  Ver avance
                </button>
              </div>
            </div>

            <transition name="fade">
            <div v-if="isExpanded(a.programacion.id)" class="overflow-x-auto mt-3">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-chancay-azul text-white">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium">#</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Empadronado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Dirección</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Evidencia</th>
                    <th class="px-4 py-2 text-right text-xs font-medium">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr
                    v-for="(e, idx) in a.empadronados"
                    :key="e.id"
                    :class="[
                      e.evidencia && e.evidencia.estado === 'completado' ? 'bg-green-50 border-l-4 border-green-500' :
                      e.evidencia && e.evidencia.estado === 'no_completado' ? 'bg-yellow-50 border-l-4 border-yellow-400' :
                      e.evidencia && e.evidencia.estado === 'no_encontrado' ? 'bg-red-50 border-l-4 border-red-400' :
                      'border-l-4 border-transparent',
                    ]"
                  >
                    <td class="px-4 py-2 text-xs text-gray-600">{{ idx + 1 }}</td>
                    <td class="px-4 py-2">
                      <div class="flex items-center gap-2">
                        <span class="font-medium">{{ e.nombre }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-2">{{ e.direccion }}</td>
                    <!-- Estado -->
                    <td class="px-4 py-2">
                      <span v-if="e.evidencia" :class="[
                        'px-2 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1',
                        e.evidencia.estado === 'completado' ? 'bg-green-100 text-green-800 border border-green-300' :
                        e.evidencia.estado === 'no_completado' ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' :
                        e.evidencia.estado === 'no_encontrado' ? 'bg-red-100 text-red-800 border border-red-300' :
                        'bg-gray-100 text-gray-600 border border-gray-300'
                      ]">
                        <template v-if="e.evidencia.estado === 'completado'">
                          <svg class="inline h-4 w-4 mr-1 align-text-bottom text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12.5l2.5 2.5L16 9" /></svg>
                          Completado
                        </template>
                        <template v-else-if="e.evidencia.estado === 'no_completado'">
                          <svg class="inline h-4 w-4 mr-1 align-text-bottom text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2" /></svg>
                          No completado
                          <button v-if="e.evidencia.comentario" @click="openCommentModal(e.evidencia.comentario, e.evidencia.estado, e.nombre)" class="ml-1 text-yellow-600 hover:text-yellow-800 focus:outline-none" title="Ver comentario">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </button>
                        </template>
                        <template v-else-if="e.evidencia.estado === 'no_encontrado'">
                          <svg class="inline h-4 w-4 mr-1 align-text-bottom text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9l-6 6M9 9l6 6" /></svg>
                          No encontrado
                          <button v-if="e.evidencia.comentario" @click="openCommentModal(e.evidencia.comentario, e.evidencia.estado, e.nombre)" class="ml-1 text-red-600 hover:text-red-800 focus:outline-none" title="Ver comentario">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </button>
                        </template>
                        <template v-else>
                          Sin estado
                        </template>
                      </span>
                      <span v-else class="text-xs text-gray-500">Sin estado</span>
                    </td>
  <!-- (Se movió el modal de comentario fuera de la tabla) -->
                    <!-- Evidencia -->
                    <td class="px-4 py-2">
                      <div v-if="e.evidencia && e.evidencia.ruta_foto" class="flex items-center gap-3">
                        <a :href="e.evidencia.ruta_foto" target="_blank" rel="noopener">
                          <img :src="e.evidencia.ruta_foto" alt="evidencia" class="h-12 w-12 object-cover rounded ring-1 ring-gray-200 hover:ring-2 hover:ring-chancay-azul transition" />
                        </a>
                      </div>
                      <span v-else class="text-xs text-gray-500">Sin evidencia</span>
                    </td>
                    <!-- Acciones -->
                    <td class="px-4 py-2">
                      <div class="flex justify-end">
                        <button
                          class="px-3 py-1.5 rounded-lg text-sm text-white"
                          :class="e.evidencia ? 'bg-chancay-verde hover:bg-chancay-azul' : 'bg-chancay-azul hover:bg-chancay-verde'"
                          @click="openUploadModal(a.programacion, e)"
                          :disabled="props.viewingOther && !hasPerm('manage.recoleccion')"
                          :title="props.viewingOther && !hasPerm('manage.recoleccion') ? 'Solo lectura en modo supervisión' : ''"
                        >
                          {{ e.evidencia ? 'Re-registrar' : 'Registrar' }}
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!a.empadronados.length">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay empadronados en esta asignación.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            </transition>

          </div>
        </div>

        <!-- Modal Subir Evidencia Mejorado -->
  <div v-if="showModal" class="fixed inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white rounded-2xl p-0 w-full max-w-[95vw] sm:max-w-xl mx-2 sm:mx-4 shadow-2xl transform transition-all overflow-hidden border-2 border-chancay-azul">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 bg-chancay-azul">
              <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-chancay-verde/90">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                </span>
                <div>
                  <h3 class="text-xl font-bold text-white leading-tight">Registrar Recolección</h3>
                  <div class="text-xs text-chancay-verde font-semibold">Completa la información de la visita</div>
                </div>
              </div>
              <button @click="showModal = false" class="text-white hover:text-chancay-verde transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 space-y-5 bg-white">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <div>
                  <div class="text-chancay-azul font-semibold text-base">{{ currentEmp?.nombre }}</div>
                  <div class="text-xs text-gray-500">Dirección: {{ currentEmp?.direccion }}</div>
                </div>
                <div class="text-xs text-gray-700 bg-chancay-verde/10 px-3 py-1 rounded-full font-semibold">
                  {{ currentProg?.zona }} - {{ currentProg?.sector }}
                </div>
              </div>
              <div v-if="currentProg?.descripcion" class="text-xs text-gray-600 italic mb-2">
                {{ currentProg.descripcion }}
              </div>

              <!-- Estado visual -->
              <div>
                <label class="block text-sm font-medium text-chancay-azul mb-2">Tipo de registro</label>
                <div class="flex gap-2">
                  <button type="button" @click="uploadForm.estado = 'completado'" :class="[uploadForm.estado === 'completado' ? 'ring-2 ring-chancay-verde bg-chancay-verde/10' : 'bg-gray-100', 'flex-1 rounded-lg px-3 py-2 flex flex-col items-center gap-1 border border-chancay-verde/30 hover:bg-chancay-verde/20 transition']">
                    <svg class="h-7 w-7 text-chancay-verde" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12.5l2.5 2.5L16 9" /></svg>
                    <span class="text-xs font-semibold mt-1">Completado</span>
                  </button>
                  <button type="button" @click="uploadForm.estado = 'no_completado'" :class="[uploadForm.estado === 'no_completado' ? 'ring-2 ring-yellow-400 bg-yellow-50' : 'bg-gray-100', 'flex-1 rounded-lg px-3 py-2 flex flex-col items-center gap-1 border border-yellow-400/30 hover:bg-yellow-100 transition']">
                    <svg class="h-7 w-7 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2" /></svg>
                    <span class="text-xs font-semibold mt-1">No completado</span>
                  </button>
                  <button type="button" @click="uploadForm.estado = 'no_encontrado'" :class="[uploadForm.estado === 'no_encontrado' ? 'ring-2 ring-red-400 bg-red-50' : 'bg-gray-100', 'flex-1 rounded-lg px-3 py-2 flex flex-col items-center gap-1 border border-red-400/30 hover:bg-red-100 transition']">
                    <svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9l-6 6M9 9l6 6" /></svg>
                    <span class="text-xs font-semibold mt-1">No encontrado</span>
                  </button>
                </div>
                <div v-if="uploadForm.errors.estado" class="text-sm text-red-600 mt-1">{{ uploadForm.errors.estado }}</div>
              </div>

              <!-- Foto -->
              <div v-if="uploadForm.estado === 'completado'">
                <label class="block text-sm font-medium text-chancay-azul mb-1">Foto de la recolección <span class="text-red-500">*</span></label>
                <input type="file" accept="image/*" @change="onFileChange" class="w-full border rounded px-2 py-1" />
                <div v-if="uploadForm.errors.foto" class="text-sm text-red-600 mt-1">{{ uploadForm.errors.foto }}</div>
                <div v-if="previewUrl" class="mt-2">
                  <img :src="previewUrl" class="w-full h-56 object-cover rounded border-2 border-chancay-verde/40 shadow" alt="preview" />
                </div>
              </div>

              <!-- Comentario para no completado o no encontrado -->
              <div v-if="uploadForm.estado === 'no_completado' || uploadForm.estado === 'no_encontrado'">
                <label class="block text-sm font-medium text-chancay-azul mb-1">Comentario <span class="text-red-500">*</span></label>
                <textarea v-model="uploadForm.comentario" rows="3" class="w-full border rounded px-2 py-1" placeholder="Describe la situación..."></textarea>
                <div v-if="uploadForm.errors.comentario" class="text-sm text-red-600 mt-1">{{ uploadForm.errors.comentario }}</div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2 border-t">
              <button type="button" @click="showModal = false" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 transition">
                Cancelar
              </button>
              <button type="button" @click="submitUpload" :disabled="uploadForm.processing" class="inline-flex items-center rounded-lg bg-chancay-azul px-4 py-2 text-sm font-medium text-white hover:bg-chancay-verde disabled:opacity-50">
                Guardar
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Comentario (reposicionado fuera de la tabla) -->
        <div v-if="showCommentModal" @click.self="showCommentModal = false" class="fixed inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-0 overflow-hidden border border-gray-200 animate-fadeIn">
            <div :class="[
              'flex items-center gap-3 px-5 py-3',
              commentEstado === 'no_encontrado' ? 'bg-red-100/70 border-b border-red-300' : 'bg-yellow-100/70 border-b border-yellow-300'
            ]">
              <div class="shrink-0">
                <svg v-if="commentEstado === 'no_encontrado'" class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9l-6 6M9 9l6 6" /></svg>
                <svg v-else class="h-7 w-7 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2" /></svg>
              </div>
              <div class="flex-1">
                <div class="font-semibold text-sm text-chancay-azul leading-tight">Comentario de {{ commentNombre }}</div>
                <div class="text-[10px] uppercase tracking-wide font-medium" :class="commentEstado === 'no_encontrado' ? 'text-red-600' : 'text-yellow-600'">
                  {{ commentEstado === 'no_encontrado' ? 'No encontrado' : 'No completado' }}
                </div>
              </div>
              <button @click="showCommentModal = false" class="ml-auto text-gray-400 hover:text-gray-600 transition" aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="px-5 py-5 text-gray-700 text-sm whitespace-pre-line min-h-[60px]">
              <p v-if="commentContent" class="leading-relaxed">{{ commentContent }}</p>
              <p v-else class="italic text-gray-400">(Sin comentario)</p>
            </div>
            <div class="px-5 py-3 bg-gray-50 flex justify-end">
              <button @click="showCommentModal = false" class="px-4 py-1.5 rounded-lg bg-chancay-azul text-white text-xs font-semibold hover:bg-chancay-verde transition">Cerrar</button>
            </div>
          </div>
        </div>

        <!-- Modal Detalle de Avance -->
  <div v-if="showProgressModal" class="fixed inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-[95vw] sm:max-w-2xl mx-2 sm:mx-4 shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-chancay-azul">
                Avance · {{ selectedAssignment?.programacion?.zona }} - {{ selectedAssignment?.programacion?.sector }}
              </h3>
              <button @click="showProgressModal = false" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-if="progressData" class="space-y-4">
              <!-- KPIs -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-lg bg-blue-50">
                  <div class="text-xs text-gray-600">Total asignados</div>
                  <div class="text-xl font-semibold text-chancay-azul">{{ progressData.total }}</div>
                </div>
                <div class="p-3 rounded-lg bg-green-50">
                  <div class="text-xs text-gray-600">Completados</div>
                  <div class="text-xl font-semibold text-green-700">{{ progressData.completed }}</div>
                </div>
                <div class="p-3 rounded-lg bg-yellow-50">
                  <div class="text-xs text-gray-600">Pendientes</div>
                  <div class="text-xl font-semibold text-yellow-700">{{ progressData.pending }}</div>
                </div>
              </div>

              <!-- Barra de progreso -->
              <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                  <span>Progreso</span>
                  <span>{{ progressData.percent }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-3 bg-chancay-azul rounded-full transition-all"
                    :style="{ width: (progressData.percent || 0) + '%' }"
                  />
                </div>
              </div>

              <!-- Última evidencia -->
              <div v-if="progressData.lastAt" class="text-xs text-gray-600">
                Última evidencia: <span class="font-medium">{{ progressData.lastAt }}</span>
              </div>

              <!-- Listados -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="border rounded-lg p-3">
                  <div class="text-sm font-semibold text-chancay-azul mb-2">Pendientes ({{ progressData.pending }})</div>
                  <ul class="space-y-1 max-h-48 overflow-y-auto">
                    <li v-for="(n, i) in progressData.pendingList" :key="'p-'+i" class="text-sm text-gray-700 flex items-center gap-2">
                      <span class="h-2 w-2 rounded-full bg-yellow-500 inline-block"></span>
                      {{ n }}
                    </li>
                    <li v-if="!progressData.pendingList.length" class="text-xs text-gray-500">Sin pendientes.</li>
                  </ul>
                </div>
                <div class="border rounded-lg p-3">
                  <div class="text-sm font-semibold text-chancay-azul mb-2">Completados ({{ progressData.completed }})</div>
                  <ul class="space-y-1 max-h-48 overflow-y-auto">
                    <li v-for="(n, i) in progressData.completedList" :key="'c-'+i" class="text-sm text-gray-700 flex items-center gap-2">
                      <span class="h-2 w-2 rounded-full bg-green-600 inline-block"></span>
                      {{ n }}
                    </li>
                    <li v-if="!progressData.completedList.length" class="text-xs text-gray-500">Sin completados aún.</li>
                  </ul>
                </div>
              </div>

              <div class="flex justify-end">
                <button @click="showProgressModal = false" class="mt-2 px-4 py-2 rounded-lg bg-gray-200 text-gray-800 text-sm hover:bg-gray-300">
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- /Modal Detalle de Avance -->

    </div>
  </div>
  </template>
  </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
