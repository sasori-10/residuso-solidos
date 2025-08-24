<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const props = defineProps({
  users: { type: Array, required: true },
  zonas: { type: Array, required: true },
  programaciones: { type: Array, required: true },
});

// Permisos (usando los compartidos por Inertia en HandleInertiaRequests)
import { usePage } from '@inertiajs/vue3';
const page = usePage();
const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
const canDelete = computed(() => userPermissions.value.includes('edit.recoleccion'));

// Estados
const editId = ref(null);
const isEdit = ref(false);
const showModal = ref(false);
const sectores = ref({}); // objeto id => nombre

const form = useForm({
  user_id: '',
  zona_id: '',
  sector_id: '',
  dias: [],
  hora_inicio: '',
  hora_fin: '',
  descripcion: '',
});

// Toast (mismo estilo que Empadronados)
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  iconColor: '#002F6C',
  customClass: { popup: 'colored-toast' },
});

const diasSemana = [
  { v: 'Lunes', l: 'Lunes' },
  { v: 'Martes', l: 'Martes' },
  { v: 'Miércoles', l: 'Miércoles' },
  { v: 'Jueves', l: 'Jueves' },
  { v: 'Viernes', l: 'Viernes' },
  { v: 'Sábado', l: 'Sábado' },
  { v: 'Domingo', l: 'Domingo' },
];

async function cargarSectores(zonaId) {
  sectores.value = {};
  form.sector_id = '';
  if (!zonaId) return;
  try {
    const { data } = await axios.get(`/api/sectores-by-zona/${zonaId}`);
    sectores.value = (data && typeof data === 'object') ? (data.sectores ?? data) : {};
  } catch {
    sectores.value = {};
  }
}
watch(() => form.zona_id, (z) => cargarSectores(z));

function formatTimeForInput(time) {
  if (!time) return '';
  if (time.includes(':') && time.split(':').length === 3) return time.substring(0, 5);
  return time;
}

// Crear
function openCreate() {
  isEdit.value = false;
  editId.value = null;
  form.reset();
  form.clearErrors();
  sectores.value = {};
  showModal.value = true;
}

// Editar
function editar(p) {
  isEdit.value = true;
  editId.value = p.id;
  form.user_id = p.user_id;
  form.zona_id = p.zona_id;
  cargarSectores(p.zona_id).then(() => {
    form.sector_id = p.sector_id;
  });
  form.dias = Array.isArray(p.dias) ? p.dias : [];
  form.hora_inicio = formatTimeForInput(p.hora_inicio ?? '');
  form.hora_fin = formatTimeForInput(p.hora_fin ?? '');
  form.descripcion = p.descripcion ?? '';
  form.clearErrors();
  showModal.value = true;
}

// Guardar (create/update) con loader y toast
function submit() {
  Swal.fire({
    title: 'Procesando...',
    html: 'Por favor espera un momento',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  const opts = {
    onSuccess: () => {
      Swal.close();
      showModal.value = false;
      Toast.fire({ icon: 'success', title: isEdit.value ? 'Programación actualizada' : 'Programación creada' });
      resetForm();
    },
    onError: (errors) => {
      Swal.close();
      let errorMessage = 'Por favor verifica los datos ingresados';
      if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0];
        if (Array.isArray(first)) errorMessage = first[0];
        else if (typeof first === 'string') errorMessage = first;
      }
      Swal.fire({ icon: 'error', title: 'Error', text: errorMessage, confirmButtonColor: '#002F6C' });
    },
  };

  if (isEdit.value && editId.value) {
    form.put(route('programacion.update', editId.value), opts);
  } else {
    form.post(route('programacion.store'), opts);
  }
}

// Eliminar con confirm y toast
function eliminar(id) {
  Swal.fire({
    title: '¿Eliminar programación?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#002F6C',
    cancelButtonColor: '#A0522D',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    background: 'rgba(255, 255, 255, 0.95)',
    backdrop: `rgba(0, 47, 108, 0.4)`,
  }).then((result) => {
    if (!result.isConfirmed) return;
    Swal.fire({
      title: 'Eliminando...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });
    form.delete(route('programacion.destroy', id), {
      onSuccess: () => {
        Swal.close();
        Toast.fire({ icon: 'success', title: 'Programación eliminada' });
      },
      onError: () => {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar la programación', confirmButtonColor: '#002F6C' });
      },
    });
  });
}

function resetForm() {
  editId.value = null;
  isEdit.value = false;
  form.reset();
  sectores.value = {};
}

const tituloFormulario = computed(() => (isEdit.value ? 'Editar Programación' : 'Nueva Programación'));

// Estado para modal de progreso (supervisor)
const showProgressModal = ref(false);
const selectedProgramacion = ref(null);

function openProgress(p) {
  selectedProgramacion.value = p;
  showProgressModal.value = true;
}

// Helper de lectura segura
const progressData = computed(() => {
  const p = selectedProgramacion.value;
  if (!p?.progress) return null;
  return {
    total: p.progress.total || 0,
    completed: p.progress.completed || 0,
    pending: p.progress.pending || 0,
    percent: p.progress.percent || 0,
    lastAt: p.progress.lastAt || null,
    pendingList: Array.isArray(p.progress.pendingList) ? p.progress.pendingList : [],
    completedList: Array.isArray(p.progress.completedList) ? p.progress.completedList : [],
    zona: p.zona?.nombre || '',
    sector: p.sector?.nombre || '',
  };
});

function goToEvidencias() {
  const p = selectedProgramacion.value;
  if (!p) return;
  // Navegar pasando el user (recolector) como query param
  router.get(route('recoleccion.index'), { user: p.user_id });
}
</script>

<template>
  <Head title="Programación" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-chancay-azul">
          Programación
        </h2>
        <button
          @click="openCreate"
          class="px-4 py-2 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Nueva Programación
        </button>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        <!-- Listado -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="p-6 text-gray-900">
            <h3 class="text-lg font-medium mb-4">Programaciones</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-chancay-azul text-white">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium">Usuario</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Zona</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Sector</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Días</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Inicio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Fin</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Descripción</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Avance</th>
                    <th class="px-4 py-2 text-right text-xs font-medium">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="p in props.programaciones" :key="p.id">
                    <td class="px-4 py-2">{{ p.user?.name ?? '' }}</td>
                    <td class="px-4 py-2">{{ p.zona?.nombre ?? '' }}</td>
                    <td class="px-4 py-2">{{ p.sector?.nombre ?? '' }}</td>
                    <td class="px-4 py-2">
                      <span>{{ Array.isArray(p.dias) ? p.dias.join(', ') : '' }}</span>
                    </td>
                    <td class="px-4 py-2">{{ formatTimeForInput(p.hora_inicio) }}</td>
                    <td class="px-4 py-2">{{ formatTimeForInput(p.hora_fin) }}</td>
                    <td class="px-4 py-2">{{ p.descripcion }}</td>
                    <td class="px-4 py-2">
                      <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                              :class="(p.progress?.percent || 0) >= 100 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                          {{ p.progress?.completed || 0 }}/{{ p.progress?.total || 0 }} ({{ p.progress?.percent || 0 }}%)
                        </span>
                        <button
                          @click="openProgress(p)"
                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-chancay-azul text-white text-xs hover:bg-chancay-verde transition"
                          title="Ver detalle de avance"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3C5 3 1.73 7.11 1 10c.73 2.89 4 7 9 7s8.27-4.11 9-7c-.73-2.89-4-7-9-7zm0 12c-3.31 0-6-3.13-6.92-5 .92-1.87 3.61-5 6.92-5 3.31 0 6 3.13 6.92 5-.92 1.87-3.61 5-6.92 5zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                          </svg>
                          Ver
                        </button>
                      </div>
                    </td>
                    <td class="px-4 py-2">
                      <div class="flex justify-end gap-2">
                        <button
                          class="rounded-md bg-white px-3 py-1 text-sm text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                          @click="editar(p)"
                        >
                          Editar
                        </button>
                        <button
                          v-if="canDelete"
                          class="rounded-md bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                          @click="eliminar(p.id)"
                        >
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!props.programaciones.length">
                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">Sin registros</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Modal Crear/Editar -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <!-- Ajuste responsivo: usar ancho relativo a viewport en móviles -->
          <div class="bg-white rounded-lg p-6 w-full max-w-[95vw] sm:max-w-3xl mx-2 sm:mx-4 shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-chancay-azul">{{ tituloFormulario }}</h3>
              <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                  <select v-model="form.user_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                    <option value="" disabled>Seleccione usuario</option>
                    <option v-for="u in props.users" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                  <div v-if="form.errors.user_id" class="text-sm text-red-600 mt-1">{{ form.errors.user_id }}</div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Zona</label>
                  <select v-model="form.zona_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                    <option value="" disabled>Seleccione zona</option>
                    <option v-for="z in props.zonas" :key="z.id" :value="z.id">{{ z.nombre }}</option>
                  </select>
                  <div v-if="form.errors.zona_id" class="text-sm text-red-600 mt-1">{{ form.errors.zona_id }}</div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                  <select v-model="form.sector_id" required :disabled="!form.zona_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition disabled:bg-gray-100">
                    <option value="" disabled>Seleccione sector</option>
                    <option v-for="(nombre, id) in sectores" :key="id" :value="id">{{ nombre }}</option>
                  </select>
                  <div v-if="form.errors.sector_id" class="text-sm text-red-600 mt-1">{{ form.errors.sector_id }}</div>
                </div>

                <div class="md:col-span-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Días</label>
                  <div class="flex flex-wrap gap-4">
                    <label v-for="d in diasSemana" :key="d.v" class="inline-flex items-center space-x-2">
                      <input type="checkbox" :value="d.v" v-model="form.dias" class="rounded border-gray-300 text-chancay-azul focus:ring-chancay-dorado">
                      <span>{{ d.l }}</span>
                    </label>
                  </div>
                  <div v-if="form.errors.dias" class="text-sm text-red-600 mt-1">{{ form.errors.dias }}</div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
                  <input type="time" v-model="form.hora_inicio" step="60" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                  <div v-if="form.errors.hora_inicio" class="text-sm text-red-600 mt-1">{{ form.errors.hora_inicio }}</div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
                  <input type="time" v-model="form.hora_fin" step="60" required class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                  <div v-if="form.errors.hora_fin" class="text-sm text-red-600 mt-1">{{ form.errors.hora_fin }}</div>
                </div>

                <div class="md:col-span-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                  <input type="text" v-model="form.descripcion" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" placeholder="Opcional" />
                  <div v-if="form.errors.descripcion" class="text-sm text-red-600 mt-1">{{ form.errors.descripcion }}</div>
                </div>
              </div>

              <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="showModal = false" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 transition">
                  Cancelar
                </button>
                <button type="submit" :disabled="form.processing" class="inline-flex items-center rounded-lg bg-chancay-azul px-4 py-2 text-sm font-medium text-white hover:bg-chancay-verde disabled:opacity-50">
                  {{ isEdit ? 'Actualizar' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Modal Detalle de Avance -->
        <div v-if="showProgressModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 w-full max-w-[95vw] sm:max-w-2xl mx-2 sm:mx-4 shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-chancay-azul">
                Avance · {{ progressData?.zona }} - {{ progressData?.sector }}
              </h3>
              <button @click="showProgressModal = false" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-if="progressData" class="space-y-4">
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

              <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                  <span>Progreso</span>
                  <span>{{ progressData.percent }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-3 bg-chancay-azul rounded-full transition-all" :style="{ width: (progressData.percent || 0) + '%' }" />
                </div>
              </div>

              <div v-if="progressData.lastAt" class="text-xs text-gray-600">
                Última evidencia: <span class="font-medium">{{ progressData.lastAt }}</span>
              </div>

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

              <div class="flex justify-end gap-2">
                <button @click="goToEvidencias" class="mt-2 px-4 py-2 rounded-lg bg-chancay-azul text-white text-sm hover:bg-chancay-verde">
                  Ver evidencias
                </button>
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

<style>
.colored-toast {
  background-color: white !important;
  border-left: 4px solid #002F6C !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
}
.colored-toast .swal2-title { color: #002F6C !important; }
</style>
