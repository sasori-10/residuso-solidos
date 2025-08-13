<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive, onMounted } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { router, Head } from '@inertiajs/vue3';

const props = defineProps({
    zonas: Array,
    sectores: Array,
    zonasOptions: Object,
});

const zonas = ref(props.zonas ?? []);
const sectores = ref(props.sectores ?? []);
const zonasOptions = ref(props.zonasOptions ?? {});

// Estados para modales
const showZonaModal = ref(false);
const showSectorModal = ref(false);
const isLoading = ref(false);
const isEditZona = ref(false);
const isEditSector = ref(false);

// Formularios reactivos
const zonaForm = reactive({
    id: null,
    nombre: '',
});

const sectorForm = reactive({
    id: null,
    nombre: '',
    zona_id: '',
});

// Tab activo
const activeTab = ref('zonas');

// Configuración personalizada de SweetAlert2
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  iconColor: '#002F6C',
  customClass: {
    popup: 'colored-toast'
  },
});

// Funciones para Zonas
function openCreateZona() {
    isEditZona.value = false;
    Object.assign(zonaForm, { id: null, nombre: '' });
    showZonaModal.value = true;
}

function openEditZona(zona) {
    isEditZona.value = true;
    Object.assign(zonaForm, { id: zona.id, nombre: zona.nombre });
    showZonaModal.value = true;
}

async function saveZona() {
    isLoading.value = true;
    
    Swal.fire({
        title: 'Procesando...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    if (isEditZona.value) {
        router.put(`/zonas/${zonaForm.id}`, {
            nombre: zonaForm.nombre,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showZonaModal.value = false;
                Swal.close();
                
                zonas.value = page.props.zonas;
                sectores.value = page.props.sectores;
                zonasOptions.value = page.props.zonasOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Zona actualizada correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor verifica los datos ingresados',
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    } else {
        router.post('/zonas', {
            nombre: zonaForm.nombre,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showZonaModal.value = false;
                Swal.close();
                
                zonas.value = page.props.zonas;
                sectores.value = page.props.sectores;
                zonasOptions.value = page.props.zonasOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Zona creada correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor verifica los datos ingresados',
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    }
}

function deleteZona(zona) {
    Swal.fire({
        title: '¿Eliminar zona?',
        text: `¿Estás seguro de eliminar la zona "${zona.nombre}"?`,
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
        if (result.isConfirmed) {
            router.delete(`/zonas/${zona.id}`, {
                onSuccess: (page) => {
                    zonas.value = page.props.zonas;
                    sectores.value = page.props.sectores;
                    zonasOptions.value = page.props.zonasOptions;
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Zona eliminada correctamente'
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar la zona',
                        confirmButtonColor: '#002F6C'
                    });
                }
            });
        }
    });
}

// Funciones para Sectores
function openCreateSector() {
    isEditSector.value = false;
    Object.assign(sectorForm, { id: null, nombre: '', zona_id: '' });
    showSectorModal.value = true;
}

function openEditSector(sector) {
    isEditSector.value = true;
    Object.assign(sectorForm, { 
        id: sector.id, 
        nombre: sector.nombre, 
        zona_id: sector.zona_id 
    });
    showSectorModal.value = true;
}

async function saveSector() {
    isLoading.value = true;
    
    Swal.fire({
        title: 'Procesando...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    if (isEditSector.value) {
        router.put(`/sectores/${sectorForm.id}`, {
            nombre: sectorForm.nombre,
            zona_id: sectorForm.zona_id,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showSectorModal.value = false;
                Swal.close();
                
                zonas.value = page.props.zonas;
                sectores.value = page.props.sectores;
                zonasOptions.value = page.props.zonasOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Sector actualizado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor verifica los datos ingresados',
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    } else {
        router.post('/sectores', {
            nombre: sectorForm.nombre,
            zona_id: sectorForm.zona_id,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showSectorModal.value = false;
                Swal.close();
                
                zonas.value = page.props.zonas;
                sectores.value = page.props.sectores;
                zonasOptions.value = page.props.zonasOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Sector creado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor verifica los datos ingresados',
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    }
}

function deleteSector(sector) {
    Swal.fire({
        title: '¿Eliminar sector?',
        text: `¿Estás seguro de eliminar el sector "${sector.nombre}"?`,
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
        if (result.isConfirmed) {
            router.delete(`/sectores/${sector.id}`, {
                onSuccess: (page) => {
                    zonas.value = page.props.zonas;
                    sectores.value = page.props.sectores;
                    zonasOptions.value = page.props.zonasOptions;
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Sector eliminado correctamente'
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el sector',
                        confirmButtonColor: '#002F6C'
                    });
                }
            });
        }
    });
}
</script>

<template>
    <Head title="Gestión de Zonas y Sectores" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-chancay-azul">
                    Gestión de Zonas y Sectores
                </h2>
                <div class="flex gap-2">
                    <button @click="openCreateZona" 
                        class="px-4 py-2 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nueva Zona
                    </button>
                    <button @click="openCreateSector" 
                        class="px-4 py-2 rounded-lg bg-chancay-verde text-white font-medium hover:bg-chancay-azul transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nuevo Sector
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button 
                                @click="activeTab = 'zonas'"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'zonas' 
                                        ? 'border-chancay-azul text-chancay-azul' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]">
                                Zonas ({{ zonas.length }})
                            </button>
                            <button 
                                @click="activeTab = 'sectores'"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'sectores' 
                                        ? 'border-chancay-azul text-chancay-azul' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]">
                                Sectores ({{ sectores.length }})
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Contenido de Zonas -->
                <div v-if="activeTab === 'zonas'" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-chancay-azul">Lista de Zonas</h3>
                        </div>
                        
                        <!-- Tabla de zonas -->
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-chancay-azul text-white">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 rounded-tl-lg">Nombre de la Zona</th>
                                        <th scope="col" class="py-3 px-4">Cantidad de Sectores</th>
                                        <th scope="col" class="py-3 px-4 rounded-tr-lg text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="zona in zonas" :key="zona.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ zona.nombre }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ zona.sectores_count }} sectores
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button @click="openEditZona(zona)" class="text-chancay-verde hover:text-chancay-dorado transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteZona(zona)" class="text-red-600 hover:text-red-800 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="zonas.length === 0">
                                        <td colspan="3" class="text-center py-6 text-gray-500">No hay zonas registradas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contenido de Sectores -->
                <div v-if="activeTab === 'sectores'" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-chancay-azul">Lista de Sectores</h3>
                        </div>
                        
                        <!-- Tabla de sectores -->
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-chancay-azul text-white">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 rounded-tl-lg">Nombre del Sector</th>
                                        <th scope="col" class="py-3 px-4">Zona</th>
                                        <th scope="col" class="py-3 px-4 rounded-tr-lg text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sector in sectores" :key="sector.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ sector.nombre }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ sector.zona_nombre }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button @click="openEditSector(sector)" class="text-chancay-verde hover:text-chancay-dorado transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteSector(sector)" class="text-red-600 hover:text-red-800 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="sectores.length === 0">
                                        <td colspan="3" class="text-center py-6 text-gray-500">No hay sectores registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Zonas -->
        <div v-if="showZonaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-2xl transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-chancay-azul">
                        {{ isEditZona ? 'Editar Zona' : 'Nueva Zona' }}
                    </h3>
                    <button @click="showZonaModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveZona" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Nombre de la Zona</label>
                        <input v-model="zonaForm.nombre" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <button type="button" @click="showZonaModal = false" 
                            class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isLoading"
                            class="px-4 py-2.5 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ isEditZona ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Sectores -->
        <div v-if="showSectorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-2xl transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-chancay-azul">
                        {{ isEditSector ? 'Editar Sector' : 'Nuevo Sector' }}
                    </h3>
                    <button @click="showSectorModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveSector" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Nombre del Sector</label>
                        <input v-model="sectorForm.nombre" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Zona</label>
                        <select v-model="sectorForm.zona_id" required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                            <option value="">Seleccionar zona...</option>
                            <option v-for="(nombre, id) in zonasOptions" :key="id" :value="id">{{ nombre }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <button type="button" @click="showSectorModal = false" 
                            class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isLoading"
                            class="px-4 py-2.5 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ isEditSector ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Paleta Chancay */
:root {
    --chancay-dorado: #F4C542;
    --chancay-azul: #002F6C;
    --chancay-verde: #2E8B57;
    --chancay-marron: #A0522D;
    --chancay-gris: #C0C0C0;
    --chancay-blanco: #F8F8F8;
}

.bg-chancay-dorado { background-color: var(--chancay-dorado); }
.bg-chancay-azul { background-color: var(--chancay-azul); }
.bg-chancay-verde { background-color: var(--chancay-verde); }
.bg-chancay-marron { background-color: var(--chancay-marron); }
.bg-chancay-gris { background-color: var(--chancay-gris); }
.text-chancay-dorado { color: var(--chancay-dorado); }
.text-chancay-azul { color: var(--chancay-azul); }
.text-chancay-verde { color: var(--chancay-verde); }
.text-chancay-marron { color: var(--chancay-marron); }
.text-chancay-gris { color: var(--chancay-gris); }

/* Loader animado */
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}

/* Estilos para SweetAlert2 */
.colored-toast {
  background-color: white !important;
  border-left: 4px solid #002F6C !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
}

.colored-toast .swal2-title {
  color: #002F6C !important;
}

/* Mejoras responsivas */
@media (max-width: 640px) {
  .sm\:rounded-lg {
    border-radius: 0;
  }
}
</style>
