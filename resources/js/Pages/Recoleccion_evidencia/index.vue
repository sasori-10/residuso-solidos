<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const props = defineProps({
  assignments: { type: Array, required: true }, // [{ programacion: {...}, empadronados: [{id,nombre,direccion,evidencia?}] }]
});

// Modal de subida
const showModal = ref(false);
const currentProg = ref(null);
const currentEmp = ref(null);
const previewUrl = ref('');

const uploadForm = useForm({
  programacion_id: null,
  empadronado_id: null,
  foto: null,
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
  if (!uploadForm.foto) {
    Swal.fire({ icon: 'warning', title: 'Selecciona una foto', confirmButtonColor: '#002F6C' });
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
      // Refrescar solo los datos necesarios
      router.reload({ only: ['assignments'] });
    },
    onError: (errors) => {
      Swal.close();
      let msg = 'Por favor verifica la imagen (formatos: jpg, jpeg, png, webp; tamaño <= 5MB).';
      if (errors && errors.foto) msg = Array.isArray(errors.foto) ? errors.foto[0] : errors.foto;
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
</script>

<template>
  <Head title="Mis Recolecciones" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-chancay-azul">
          Mis Recolecciones
        </h2>
        <div class="text-sm text-gray-600">
          Pendientes: <span class="font-semibold text-chancay-verde">{{ totalPendientes }}</span>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        <div v-if="!assignments.length" class="bg-white shadow-lg rounded-lg p-6 text-center text-gray-500">
          No tienes asignaciones.
        </div>

        <div v-for="a in assignments" :key="a.programacion.id" class="bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
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
              <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                      :class="getProgress(a).percent >= 100 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                  Avance: {{ getProgress(a).completed }}/{{ getProgress(a).total }} ({{ getProgress(a).percent }}%)
                </span>
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

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-chancay-azul text-white">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium">#</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Empadronado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Dirección</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Evidencia</th>
                    <th class="px-4 py-2 text-right text-xs font-medium">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr
                    v-for="(e, idx) in a.empadronados"
                    :key="e.id"
                    :class=" [
                      e.evidencia ? 'bg-green-50' : '',
                      e.evidencia ? 'border-l-4 border-green-500' : 'border-l-4 border-transparent'
                    ]"
                  >
                    <td class="px-4 py-2 text-xs text-gray-600">{{ idx + 1 }}</td>
                    <td class="px-4 py-2">
                      <div class="flex items-center gap-2">
                        <span class="font-medium">{{ e.nombre }}</span>
                        <span v-if="e.evidencia" class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">Completado</span>
                      </div>
                    </td>
                    <td class="px-4 py-2">{{ e.direccion }}</td>
                    <td class="px-4 py-2">
                      <div v-if="e.evidencia" class="flex items-center gap-3">
                        <a :href="e.evidencia.ruta_foto" target="_blank" rel="noopener">
                          <img :src="e.evidencia.ruta_foto" alt="evidencia" class="h-12 w-12 object-cover rounded ring-1 ring-gray-200 hover:ring-2 hover:ring-chancay-azul transition" />
                        </a>
                      </div>
                      <span v-else class="text-xs text-gray-500">Sin evidencia</span>
                    </td>
                    <td class="px-4 py-2">
                      <div class="flex justify-end">
                        <button
                          class="px-3 py-1.5 rounded-lg text-sm text-white"
                          :class="e.evidencia ? 'bg-chancay-verde hover:bg-chancay-azul' : 'bg-chancay-azul hover:bg-chancay-verde'"
                          @click="openUploadModal(a.programacion, e)"
                        >
                          {{ e.evidencia ? 'Re-subir' : 'Subir' }} foto
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

          </div>
        </div>

        <!-- Modal Subir Evidencia -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-[95vw] sm:max-w-lg mx-2 sm:mx-4 shadow-2xl transform transition-all">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-chancay-azul">Subir Evidencia</h3>
              <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="space-y-3 mb-3">
              <div class="text-sm text-gray-700">
                <div><span class="font-medium">Zona/Sector:</span> {{ currentProg?.zona }} - {{ currentProg?.sector }}</div>
                <div><span class="font-medium">Empadronado:</span> {{ currentEmp?.nombre }}</div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto de la recolección</label>
                <input type="file" accept="image/*" @change="onFileChange" class="w-full" />
                <div v-if="uploadForm.errors.foto" class="text-sm text-red-600 mt-1">{{ uploadForm.errors.foto }}</div>
              </div>
              <div v-if="previewUrl" class="mt-2">
                <img :src="previewUrl" class="w-full h-56 object-cover rounded border" alt="preview" />
              </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
              <button type="button" @click="showModal = false" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 transition">
                Cancelar
              </button>
              <button type="button" @click="submitUpload" :disabled="uploadForm.processing" class="inline-flex items-center rounded-lg bg-chancay-azul px-4 py-2 text-sm font-medium text-white hover:bg-chancay-verde disabled:opacity-50">
                Guardar
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Detalle de Avance -->
        <div v-if="showProgressModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
  </AuthenticatedLayout>
</template>
