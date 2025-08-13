<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive, onMounted, watch, computed } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { router, Head } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    tiposEmpadronados: Array,
    empadronados: Array,
    pagination: Object,
    filters: Object,
    tiposOptions: Object,
    zonasOptions: Object,
    sectoresOptions: Object,
    sectoresForFilter: Object,
    tiposForFilter: Object,
});

const tiposEmpadronados = ref(props.tiposEmpadronados ?? []);
const empadronados = ref(props.empadronados ?? []);
const pagination = ref(props.pagination ?? {});
const tiposOptions = ref(props.tiposOptions ?? {});
const zonasOptions = ref(props.zonasOptions ?? {});
const sectoresOptions = ref(props.sectoresOptions ?? {});
const sectoresFiltrados = ref({});
// Bandera para evitar limpiar sector_id al precargar en edición
const suppressZonaWatch = ref(false);

// Filtros reactivos
const filters = reactive({
    sector_filter: props.filters?.sector_filter || '',
    tipo_filter: props.filters?.tipo_filter || '',
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

// Estados para modales
const showTipoModal = ref(false);
const showEmpadronadoModal = ref(false);
const isLoading = ref(false);
const isEditTipo = ref(false);
const isEditEmpadronado = ref(false);

// Formularios reactivos
const tipoForm = reactive({
    id: null,
    nombre: '',
});

const empadronadoForm = reactive({
    id: null,
    codigo: '',
    dni: '',
    nombre: '',
    direccion: '',
    celular: '',
    zona_id: '',
    sector_id: '',
    tipo_empadronado: '',
    tipo_residuos: '',
    horario_inicio: '',
    horario_fin: '',
    dias_recoleccion: [],
    // Campos dinámicos
    n_habitantes: '',
    codigo_ruta: '',
    placa: '',
    nombre_establecimiento: '',
    tipo_establecimiento: '',
    tipo_empadronado_mercado: '',
    n_puesto_mercado: '',
    nombre_institucion: '',
    tipo_institucion: '',
});

// Configuración dinámica de campos
const fieldConfig = ref({
    showHorarios: false,
    showHabitantes: false,
    showCodigoRuta: false,
    showPlaca: false,
    showNombreEstablecimiento: false,
    showTipoEstablecimiento: false,
    showNombreInstitucion: false,
    showTipoInstitucion: false,
    showNumeroPuestos: false,
    showTipoMercado: false,
    labels: {}
});

// Tab activo (por defecto mostrar Empadronados)
const activeTab = ref('empadronados');

// Días de la semana
const diasSemana = [
    { value: 'LUNES', label: 'LUNES' },
    { value: 'MARTES', label: 'MARTES' },
    { value: 'MIERCOLES', label: 'MIÉRCOLES' },
    { value: 'JUEVES', label: 'JUEVES' },
    { value: 'VIERNES', label: 'VIERNES' },
    { value: 'SABADO', label: 'SÁBADO' },
    { value: 'DOMINGO', label: 'DOMINGO' },
];

// Tipos de residuos
const tiposResiduos = [
    'Orgánicos',
    'Inorgánico',
    'Mixtos',
    'Reciclables',
    'Peligrosos'
];

// Tipos de establecimientos comerciales
const tiposEstablecimientos = [
    'Bodega',
    'Carnicería',
    'Clínica',
    'Consultorio',
    'Farmacia',
    'Ferretería',
    'Hostal',
    'Panadería',
    'Peluquería',
    'Restaurante',
    'Verdulería',
    'Otros'
];

// Tipos de instituciones
const tiposInstituciones = [
    'Educativa',
    
    'Salud',
    'Bancaria',
    'Pública',
    'Privada',
    'Religiosa',
    'Deportiva',
    'Cultural',
    'Otros'
];

// Tipos de mercados
const tiposMercados = [
    'Municipal',
    'Minorista',

    'Privado',
    'Temporal',
    'Otros'
];

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

// Watch para cargar sectores cuando cambie la zona
watch(() => empadronadoForm.zona_id, async (newZonaId) => {
    try {
        if (newZonaId) {
            const response = await axios.get(`/api/sectores-by-zona/${newZonaId}`);
            sectoresFiltrados.value = response.data;
            // Si no estamos en la fase inicial de edición, limpiar selección de sector
            if (!suppressZonaWatch.value) {
                empadronadoForm.sector_id = '';
            }
        } else {
            sectoresFiltrados.value = {};
            if (!suppressZonaWatch.value) {
                empadronadoForm.sector_id = '';
            }
        }
    } catch (error) {
        console.error('Error al cargar sectores:', error);
        sectoresFiltrados.value = {};
        if (!suppressZonaWatch.value) {
            empadronadoForm.sector_id = '';
        }
    } finally {
        // Restablecer bandera después de la primera carga en edición
        if (suppressZonaWatch.value) suppressZonaWatch.value = false;
    }
});

// Watch para cargar configuración de campos cuando cambie el tipo de empadronado
watch(() => empadronadoForm.tipo_empadronado, async (newTipoId) => {
    if (newTipoId) {
        try {
            const response = await axios.get(`/api/field-config-by-type/${newTipoId}`);
            fieldConfig.value = response.data;
            
            // Si no es edición, generar vista previa del código
            if (!isEditEmpadronado.value) {
                empadronadoForm.codigo = generateCodigoPreview(newTipoId);
            }
        } catch (error) {
            console.error('Error al cargar configuración de campos:', error);
        }
    } else {
        // Reset configuration
        fieldConfig.value = {
            showHorarios: false,
            showHabitantes: false,
            showCodigoRuta: false,
            showPlaca: false,
            showNombreEstablecimiento: false,
            showTipoEstablecimiento: false,
            showNombreInstitucion: false,
            showTipoInstitucion: false,
            showNumeroPuestos: false,
            showTipoMercado: false,
            labels: {}
        };
        empadronadoForm.codigo = '';
    }
});

// Watch para aplicar filtros
// Debounce simple para búsqueda; cambios en per_page/filters también disparan
let searchDebounce;
watch(filters, (newFilters, oldFilters) => {
    // Si cambia la búsqueda, aplicar debounce
    if (newFilters.search !== oldFilters?.search) {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => applyFilters(), 300);
    } else {
        applyFilters();
    }
}, { deep: true });

// Función para aplicar filtros
function applyFilters() {
    const params = {
        sector_filter: filters.sector_filter,
        tipo_filter: filters.tipo_filter,
        search: filters.search,
        per_page: filters.per_page,
    };
    
    // Remover parámetros vacíos
    Object.keys(params).forEach(key => {
        if (!params[key]) delete params[key];
    });
    
    router.get('/empadronados', params, {
        preserveState: true,
        preserveScroll: true,
        only: ['empadronados', 'pagination', 'filters'],
        onSuccess: (page) => {
            // Actualizar refs locales para reflejar resultados sin recargar
            empadronados.value = page.props.empadronados;
            pagination.value = page.props.pagination;
            // Si el backend devuelve filtros normalizados
            if (page.props.filters) {
                filters.sector_filter = page.props.filters.sector_filter || '';
                filters.tipo_filter = page.props.filters.tipo_filter || '';
                filters.search = page.props.filters.search || '';
                filters.per_page = page.props.filters.per_page || filters.per_page;
            }
        },
    });
}

// Función para cambiar página
function changePage(page) {
    if (page >= 1 && page <= pagination.value.last_page) {
        const params = {
            page: page,
            sector_filter: filters.sector_filter,
            tipo_filter: filters.tipo_filter,
            search: filters.search,
            per_page: filters.per_page,
        };
        
        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });
        
        router.get('/empadronados', params, {
            preserveState: true,
            preserveScroll: true,
            only: ['empadronados', 'pagination', 'filters'],
            onSuccess: (page) => {
                empadronados.value = page.props.empadronados;
                pagination.value = page.props.pagination;
                if (page.props.filters) {
                    filters.sector_filter = page.props.filters.sector_filter || '';
                    filters.tipo_filter = page.props.filters.tipo_filter || '';
                    filters.search = page.props.filters.search || '';
                    filters.per_page = page.props.filters.per_page || filters.per_page;
                }
            },
        });
    }
}

// Función para limpiar filtros
function clearFilters() {
    filters.sector_filter = '';
    filters.tipo_filter = '';
    filters.search = '';
    filters.per_page = 10;
}

// Funciones para Tipos de Empadronados
function openCreateTipo() {
    isEditTipo.value = false;
    Object.assign(tipoForm, { id: null, nombre: '' });
    showTipoModal.value = true;
}

function openEditTipo(tipo) {
    isEditTipo.value = true;
    Object.assign(tipoForm, { id: tipo.id, nombre: tipo.nombre });
    showTipoModal.value = true;
}

async function saveTipo() {
    isLoading.value = true;
    
    Swal.fire({
        title: 'Procesando...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    if (isEditTipo.value) {
        router.put(`/tipos-empadronados/${tipoForm.id}`, {
            nombre: tipoForm.nombre,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showTipoModal.value = false;
                Swal.close();
                
                tiposEmpadronados.value = page.props.tiposEmpadronados;
                empadronados.value = page.props.empadronados;
                tiposOptions.value = page.props.tiposOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Tipo de empadronado actualizado correctamente'
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
        router.post('/tipos-empadronados', {
            nombre: tipoForm.nombre,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showTipoModal.value = false;
                Swal.close();
                
                tiposEmpadronados.value = page.props.tiposEmpadronados;
                empadronados.value = page.props.empadronados;
                tiposOptions.value = page.props.tiposOptions;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Tipo de empadronado creado correctamente'
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

function deleteTipo(tipo) {
    Swal.fire({
        title: '¿Eliminar tipo de empadronado?',
        text: `¿Estás seguro de eliminar el tipo "${tipo.nombre}"?`,
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
            router.delete(`/tipos-empadronados/${tipo.id}`, {
                onSuccess: (page) => {
                    tiposEmpadronados.value = page.props.tiposEmpadronados;
                    empadronados.value = page.props.empadronados;
                    tiposOptions.value = page.props.tiposOptions;
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Tipo de empadronado eliminado correctamente'
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el tipo de empadronado',
                        confirmButtonColor: '#002F6C'
                    });
                }
            });
        }
    });
}

// Funciones para Empadronados
function openCreateEmpadronado() {
    isEditEmpadronado.value = false;
    Object.assign(empadronadoForm, { 
        id: null, codigo: '', dni: '', nombre: '', direccion: '', celular: '', 
        zona_id: '', sector_id: '', tipo_empadronado: '', tipo_residuos: '', 
        horario_inicio: '', horario_fin: '', dias_recoleccion: [],
        // Campos dinámicos
        n_habitantes: '',
        codigo_ruta: '',
        placa: '',
        nombre_establecimiento: '',
        tipo_establecimiento: '',
        tipo_empadronado_mercado: '',
        n_puesto_mercado: '',
        nombre_institucion: '',
        tipo_institucion: '',
    });
    sectoresFiltrados.value = {};
    fieldConfig.value = {
        showHorarios: false,
        showHabitantes: false,
        showCodigoRuta: false,
        showPlaca: false,
        showNombreEstablecimiento: false,
        showTipoEstablecimiento: false,
        showNombreInstitucion: false,
        showTipoInstitucion: false,
        showNumeroPuestos: false,
        showTipoMercado: false,
        labels: {}
    };
    showEmpadronadoModal.value = true;
}

function openEditEmpadronado(empadronado) {
    isEditEmpadronado.value = true;
    // Evitar que el watcher de zona limpie el sector durante la precarga
    suppressZonaWatch.value = true;
    Object.assign(empadronadoForm, { 
        id: empadronado.id, 
        codigo: empadronado.codigo || '',
        dni: empadronado.dni, 
        nombre: empadronado.nombre,
        direccion: empadronado.direccion,
        celular: empadronado.celular,
        zona_id: empadronado.zona_id,
        sector_id: empadronado.sector_id,
        tipo_empadronado: empadronado.tipo_empadronado,
        tipo_residuos: empadronado.tipo_residuos,
        horario_inicio: formatTimeForInput(empadronado.horario_inicio),
        horario_fin: formatTimeForInput(empadronado.horario_fin),
        dias_recoleccion: empadronado.dias_recoleccion ? empadronado.dias_recoleccion.split(',') : [],
        // Campos dinámicos
        n_habitantes: empadronado.n_habitantes || '',
        codigo_ruta: empadronado.codigo_ruta || '',
        placa: empadronado.placa || '',
        nombre_establecimiento: empadronado.nombre_establecimiento || '',
        tipo_establecimiento: empadronado.tipo_establecimiento || '',
        tipo_empadronado_mercado: empadronado.tipo_empadronado_mercado || '',
        n_puesto_mercado: empadronado.n_puesto_mercado || '',
        nombre_institucion: empadronado.nombre_institucion || '',
        tipo_institucion: empadronado.tipo_institucion || '',
    });
    
    // El watcher de zona se encargará de cargar sectores en esta primera vez (con supresión activa)

    // Cargar configuración de campos para el tipo seleccionado
    if (empadronado.tipo_empadronado) {
        axios.get(`/api/field-config-by-type/${empadronado.tipo_empadronado}`)
            .then(response => {
                fieldConfig.value = response.data;
            })
            .catch(error => {
                console.error('Error al cargar configuración de campos:', error);
            });
    }
    
    showEmpadronadoModal.value = true;
}

// Lista de páginas condensada para la paginación (1, 2, ..., n-1, n)
const pageList = computed(() => {
    const current = pagination.value?.current_page || 1;
    const last = pagination.value?.last_page || 1;
    const list = [];
    if (last <= 7) {
        for (let i = 1; i <= last; i++) list.push(i);
        return list;
    }
    list.push(1);
    if (current > 4) list.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);
    for (let i = start; i <= end; i++) list.push(i);
    if (current < last - 3) list.push('...');
    list.push(last);
    return list;
});

async function saveEmpadronado() {
    isLoading.value = true;
    
    Swal.fire({
        title: 'Procesando...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Convertir array de días a string
    const diasString = empadronadoForm.dias_recoleccion.join(',');
    
    const data = {
        codigo: empadronadoForm.codigo,
        dni: empadronadoForm.dni,
        nombre: empadronadoForm.nombre,
        direccion: empadronadoForm.direccion,
        celular: empadronadoForm.celular,
        zona_id: empadronadoForm.zona_id,
        sector_id: empadronadoForm.sector_id,
        tipo_empadronado: empadronadoForm.tipo_empadronado,
        tipo_residuos: empadronadoForm.tipo_residuos,
        horario_inicio: empadronadoForm.horario_inicio,
        horario_fin: empadronadoForm.horario_fin,
        dias_recoleccion: diasString,
        // Campos dinámicos
        n_habitantes: empadronadoForm.n_habitantes,
        codigo_ruta: empadronadoForm.codigo_ruta,
        placa: empadronadoForm.placa,
        nombre_establecimiento: empadronadoForm.nombre_establecimiento,
        tipo_establecimiento: empadronadoForm.tipo_establecimiento,
        tipo_empadronado_mercado: empadronadoForm.tipo_empadronado_mercado,
        n_puesto_mercado: empadronadoForm.n_puesto_mercado,
        nombre_institucion: empadronadoForm.nombre_institucion,
        tipo_institucion: empadronadoForm.tipo_institucion,
    };
    
    if (isEditEmpadronado.value) {
        router.put(`/empadronados/${empadronadoForm.id}`, data, {
            onSuccess: (page) => {
                isLoading.value = false;
                showEmpadronadoModal.value = false;
                Swal.close();
                
                tiposEmpadronados.value = page.props.tiposEmpadronados;
                empadronados.value = page.props.empadronados;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Empadronado actualizado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                // Mostrar errores específicos de validación para UPDATE
                let errorMessage = 'Errores encontrados en la edición:\n';
                if (errors && typeof errors === 'object') {
                    Object.keys(errors).forEach(field => {
                        if (Array.isArray(errors[field])) {
                            errorMessage += `• ${field}: ${errors[field][0]}\n`;
                        } else {
                            errorMessage += `• ${field}: ${errors[field]}\n`;
                        }
                    });
                } else {
                    errorMessage = 'Por favor verifica los datos ingresados';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación al Editar',
                    text: errorMessage,
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    } else {
        router.post('/empadronados', data, {
            onSuccess: (page) => {
                isLoading.value = false;
                showEmpadronadoModal.value = false;
                Swal.close();
                
                tiposEmpadronados.value = page.props.tiposEmpadronados;
                empadronados.value = page.props.empadronados;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Empadronado creado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close();
                
                // Mostrar errores específicos de validación para CREATE
                let errorMessage = 'Errores encontrados al crear:\n';
                if (errors && typeof errors === 'object') {
                    Object.keys(errors).forEach(field => {
                        if (Array.isArray(errors[field])) {
                            errorMessage += `• ${field}: ${errors[field][0]}\n`;
                        } else {
                            errorMessage += `• ${field}: ${errors[field]}\n`;
                        }
                    });
                } else {
                    errorMessage = 'Por favor verifica los datos ingresados';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación al Crear',
                    text: errorMessage,
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    }
}

function deleteEmpadronado(empadronado) {
    Swal.fire({
        title: '¿Eliminar empadronado?',
        text: `¿Estás seguro de eliminar a "${empadronado.nombre}"?`,
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
            router.delete(`/empadronados/${empadronado.id}`, {
                onSuccess: (page) => {
                    tiposEmpadronados.value = page.props.tiposEmpadronados;
                    empadronados.value = page.props.empadronados;
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Empadronado eliminado correctamente'
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el empadronado',
                        confirmButtonColor: '#002F6C'
                    });
                }
            });
        }
    });
}

// Función para formatear tiempo para input (eliminar segundos)
function formatTimeForInput(time) {
    if (!time) return '';
    // Si el tiempo viene con segundos (HH:MM:SS), eliminamos los segundos
    if (time.includes(':') && time.split(':').length === 3) {
        return time.substring(0, 5); // Tomar solo HH:MM
    }
    return time;
}

// Función para formatear días
function formatearDias(dias) {
    if (!dias) return '';
    return dias.split(',').map(dia => {
        const diaObj = diasSemana.find(d => d.value === dia);
        return diaObj ? diaObj.label : dia;
    }).join(', ');
}

// Función para generar vista previa del código
function generateCodigoPreview(tipoId) {
    const prefijos = {
        1: 'V', // VIVIENDAS
        2: 'C', // COMERCIO  
        3: 'M', // MERCADOS
        4: 'O', // VIVIENDAS-ORG
        5: 'E', // INSTITUCIONES EDUCATIVAS
        6: 'I', // INSTITUCIONES PUB Y PRIV
        7: 'X', // OTROS
    };
    
    const prefijo = prefijos[tipoId] || 'G';
    return `${prefijo}### (Se generará automáticamente)`;
}
</script>

<template>
    <Head title="Gestión de Empadronados" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-chancay-azul">
                    Gestión de Empadronados
                </h2>
                <div class="flex gap-2">
                    <button @click="openCreateTipo" 
                        class="px-4 py-2 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nuevo Tipo
                    </button>
                    <button @click="openCreateEmpadronado" 
                        class="px-4 py-2 rounded-lg bg-chancay-verde text-white font-medium hover:bg-chancay-azul transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Nuevo Empadronado
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
                                @click="activeTab = 'empadronados'"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'empadronados' 
                                        ? 'border-chancay-azul text-chancay-azul' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]">
                                Empadronados ({{ empadronados.length }})
                            </button>
                            <button 
                                @click="activeTab = 'tipos'"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'tipos' 
                                        ? 'border-chancay-azul text-chancay-azul' 
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]">
                                Tipos de Empadronados ({{ tiposEmpadronados.length }})
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Contenido de Tipos de Empadronados -->
                <div v-if="activeTab === 'tipos'" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-chancay-azul">Lista de Tipos de Empadronados</h3>
                        </div>
                        
                        <!-- Tabla de tipos -->
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-chancay-azul text-white">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 rounded-tl-lg">Nombre del Tipo</th>
                                        <th scope="col" class="py-3 px-4">Cantidad de Empadronados</th>
                                        <th scope="col" class="py-3 px-4 rounded-tr-lg text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tipo in tiposEmpadronados" :key="tipo.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ tipo.nombre }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ tipo.empadronados_count }} empadronados
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button @click="openEditTipo(tipo)" class="text-chancay-verde hover:text-chancay-dorado transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteTipo(tipo)" class="text-red-600 hover:text-red-800 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="tiposEmpadronados.length === 0">
                                        <td colspan="3" class="text-center py-6 text-gray-500">No hay tipos de empadronados registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contenido de Empadronados -->
                <div v-if="activeTab === 'empadronados'" class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-chancay-azul">Lista de Empadronados</h3>
                        </div>
                        
                        <!-- Filtros y controles -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Búsqueda general -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                    <input v-model="filters.search" 
                                        placeholder="DNI, nombre o dirección..."
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                                </div>
                                
                                <!-- Filtro por sector -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Sector</label>
                                    <select v-model="filters.sector_filter"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                        <option value="">Todos los sectores</option>
                                        <option v-for="(nombre, id) in sectoresForFilter" :key="id" :value="id">{{ nombre }}</option>
                                    </select>
                                </div>
                                
                                <!-- Filtro por tipo -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Tipo</label>
                                    <select v-model="filters.tipo_filter"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                        <option value="">Todos los tipos</option>
                                        <option v-for="(nombre, id) in tiposForFilter" :key="id" :value="id">{{ nombre }}</option>
                                    </select>
                                </div>
                                
                                <!-- Selector de registros por página -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mostrar</label>
                                    <select v-model="filters.per_page"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                        <option value="10">10 por página</option>
                                        <option value="20">20 por página</option>
                                        <option value="30">30 por página</option>
                                        <option value="50">50 por página</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Botón limpiar filtros -->
                            <div class="mt-4 flex justify-end">
                                <button @click="clearFilters" 
                                    class="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>
                        
                        <!-- Información de resultados -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-sm text-gray-600">
                                Mostrando {{ pagination.from || 0 }} a {{ pagination.to || 0 }} de {{ pagination.total || 0 }} resultados
                            </div>
                        </div>
                        
                        <!-- Tabla de empadronados -->
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-chancay-azul text-white">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 rounded-tl-lg">DNI</th>
                                        <th scope="col" class="py-3 px-4">Nombre</th>
                                        <th scope="col" class="py-3 px-4">Dirección</th>
                                        <th scope="col" class="py-3 px-4">Código</th>
                                        <th scope="col" class="py-3 px-4">Zona/Sector</th>
                                        <th scope="col" class="py-3 px-4">Tipo</th>
                                        <th scope="col" class="py-3 px-4">Horario</th>
                                        <th scope="col" class="py-3 px-4">Días</th>
                                        <th scope="col" class="py-3 px-4 rounded-tr-lg text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="empadronado in empadronados" :key="empadronado.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ empadronado.dni }}</td>
                                        <td class="py-3 px-4">{{ empadronado.nombre }}</td>
                                        <td class="py-3 px-4 max-w-xs truncate">{{ empadronado.direccion }}</td>
                                        <td class="py-3 px-4">
                                            <span v-if="empadronado.codigo" class="px-2 py-1 text-xs font-mono font-semibold rounded bg-chancay-dorado text-chancay-azul">
                                                {{ empadronado.codigo }}
                                            </span>
                                            <span v-else class="text-gray-400 text-xs">N/A</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-xs">
                                                <div class="text-green-600 font-semibold">{{ empadronado.zona_nombre }}</div>
                                                <div class="text-blue-600">{{ empadronado.sector_nombre }}</div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                {{ empadronado.tipo_empadronado_nombre }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-xs">
                                            {{ formatTimeForInput(empadronado.horario_inicio) }} - {{ formatTimeForInput(empadronado.horario_fin) }}
                                        </td>
                                        <td class="py-3 px-4 text-xs">
                                            {{ formatearDias(empadronado.dias_recoleccion) }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button @click="openEditEmpadronado(empadronado)" class="text-chancay-verde hover:text-chancay-dorado transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteEmpadronado(empadronado)" class="text-red-600 hover:text-red-800 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="empadronados.length === 0">
                                        <td colspan="9" class="text-center py-6 text-gray-500">No hay empadronados que coincidan con los filtros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <!-- Botón página anterior -->
                                <button @click="changePage(pagination.current_page - 1)" 
                                    :disabled="pagination.current_page <= 1"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 transition">
                                    Anterior
                                </button>
                                
                                <!-- Números de página -->
                                <div class="flex items-center gap-1">
                                    <template v-for="page in pageList" :key="`p-${page}`">
                                        <button v-if="typeof page === 'number'" 
                                            @click="changePage(page)"
                                            :class="[
                                                'px-3 py-2 text-sm border rounded-lg transition',
                                                page === (pagination.current_page || 1)
                                                    ? 'bg-chancay-azul text-white border-chancay-azul' 
                                                    : 'bg-white border-gray-300 hover:bg-gray-50'
                                            ]">
                                            {{ page }}
                                        </button>
                                        <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
                                    </template>
                                </div>
                                
                                <!-- Botón página siguiente -->
                                <button @click="changePage(pagination.current_page + 1)" 
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 transition">
                                    Siguiente
                                </button>
                            </div>
                            
                            <!-- Información de página -->
                            <div class="text-sm text-gray-600">
                                Página {{ pagination.current_page }} de {{ pagination.last_page }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Tipos de Empadronados -->
        <div v-if="showTipoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-2xl transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-chancay-azul">
                        {{ isEditTipo ? 'Editar Tipo de Empadronado' : 'Nuevo Tipo de Empadronado' }}
                    </h3>
                    <button @click="showTipoModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveTipo" class="space-y-4">

                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Nombre del Tipo</label>
                        <input v-model="tipoForm.nombre" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <button type="button" @click="showTipoModal = false" 
                            class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isLoading"
                            class="px-4 py-2.5 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ isEditTipo ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Empadronados -->
        <div v-if="showEmpadronadoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-5xl mx-4 shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-chancay-azul">
                        {{ isEditEmpadronado ? 'Editar Empadronado' : 'Nuevo Empadronado' }}
                    </h3>
                    <button @click="showEmpadronadoModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveEmpadronado" class="space-y-4">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Código -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">
                                Código 
                                <span v-if="!isEditEmpadronado" class="text-sm text-gray-500">(Se genera automáticamente)</span>
                            </label>
                            <input v-model="empadronadoForm.codigo" 
                                :readonly="!isEditEmpadronado"
                                :placeholder="isEditEmpadronado ? 'Código del empadronado' : 'Se generará automáticamente'"
                                :class="[
                                    'w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono',
                                    isEditEmpadronado 
                                        ? 'focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition' 
                                        : 'bg-gray-50 text-gray-600 cursor-not-allowed'
                                ]" />
                        </div>
                        
                        <!-- DNI -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">DNI</label>
                            <input v-model="empadronadoForm.dni" required maxlength="8"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Nombre -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.representante || 'Nombre Completo' }}
                            </label>
                            <input v-model="empadronadoForm.nombre" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Dirección -->
                        <div class="lg:col-span-1">
                            <label class="block text-gray-700 font-medium mb-1">Dirección</label>
                            <input v-model="empadronadoForm.direccion" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Celular -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Celular</label>
                            <input v-model="empadronadoForm.celular" 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Zona -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Zona</label>
                            <select v-model="empadronadoForm.zona_id" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar zona...</option>
                                <option v-for="(nombre, id) in zonasOptions" :key="id" :value="id">{{ nombre }}</option>
                            </select>
                        </div>
                        
                        <!-- Sector -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Sector</label>
                            <select v-model="empadronadoForm.sector_id" required
                                :disabled="!empadronadoForm.zona_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition disabled:bg-gray-100">
                                <option value="">Seleccionar sector...</option>
                                <option v-for="(nombre, id) in sectoresFiltrados" :key="id" :value="id">{{ nombre }}</option>
                            </select>
                        </div>
                        
                        <!-- Tipo de Empadronado -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Tipo de Empadronado</label>
                            <select v-model="empadronadoForm.tipo_empadronado" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar tipo...</option>
                                <option v-for="(nombre, id) in tiposOptions" :key="id" :value="id">{{ nombre }}</option>
                            </select>
                        </div>
                        
                        <!-- Tipo de Residuos -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Tipo de Residuos</label>
                            <select v-model="empadronadoForm.tipo_residuos" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar tipo de residuos...</option>
                                <option v-for="tipo in tiposResiduos" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        
                        <!-- Campos dinámicos según el tipo -->
                        
                        <!-- Número de Habitantes (Solo para VIVIENDAS) -->
                        <div v-if="fieldConfig.showHabitantes">
                            <label class="block text-gray-700 font-medium mb-1">Número de Habitantes</label>
                            <input v-model="empadronadoForm.n_habitantes" type="number" min="1" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Código de Ruta (VIVIENDAS y COMERCIO) -->
                        <div v-if="fieldConfig.showCodigoRuta">
                            <label class="block text-gray-700 font-medium mb-1">
                                Código de Ruta
                            </label>
                            <input v-model="empadronadoForm.codigo_ruta" 
                                required
                                placeholder="Ingrese el código de ruta"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Placa (VIVIENDAS y COMERCIO) -->
                        <div v-if="fieldConfig.showPlaca">
                            <label class="block text-gray-700 font-medium mb-1">Placa</label>
                            <input v-model="empadronadoForm.placa" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Nombre del Establecimiento (COMERCIO, MERCADOS, OTROS) -->
                        <div v-if="fieldConfig.showNombreEstablecimiento">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.establecimiento || 'Nombre del Establecimiento' }}
                            </label>
                            <input v-model="empadronadoForm.nombre_establecimiento" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Tipo de Establecimiento (COMERCIO, OTROS) -->
                        <div v-if="fieldConfig.showTipoEstablecimiento">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.tipo || 'Tipo de Establecimiento' }}
                            </label>
                            <select v-model="empadronadoForm.tipo_establecimiento" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar tipo...</option>
                                <option v-for="tipo in tiposEstablecimientos" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        
                        <!-- Tipo de Mercado (MERCADOS) -->
                        <div v-if="fieldConfig.showTipoMercado">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.tipo || 'Tipo de Mercado' }}
                            </label>
                            <select v-model="empadronadoForm.tipo_empadronado_mercado" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar tipo...</option>
                                <option v-for="tipo in tiposMercados" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        
                        <!-- Nombre de la Institución (INSTITUCIONES EDUCATIVAS, PUB Y PRIV) -->
                        <div v-if="fieldConfig.showNombreInstitucion">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.institucion || 'Nombre de la Institución' }}
                            </label>
                            <input v-model="empadronadoForm.nombre_institucion" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Tipo de Institución (INSTITUCIONES EDUCATIVAS, PUB Y PRIV) -->
                        <div v-if="fieldConfig.showTipoInstitucion">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.tipo || 'Tipo de Institución' }}
                            </label>
                            <select v-model="empadronadoForm.tipo_institucion" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                                <option value="">Seleccionar tipo...</option>
                                <option v-for="tipo in tiposInstituciones" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        
                        <!-- Número de Puestos (MERCADOS, INSTITUCIONES) -->
                        <div v-if="fieldConfig.showNumeroPuestos">
                            <label class="block text-gray-700 font-medium mb-1">
                                {{ fieldConfig.labels?.puestos || 'Número de Puestos' }}
                            </label>
                            <input v-model="empadronadoForm.n_puesto_mercado" type="text" min="1" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Horario Inicio (Solo si se muestran horarios) -->
                        <div v-if="fieldConfig.showHorarios">
                            <label class="block text-gray-700 font-medium mb-1">Horario de Inicio</label>
                            <input v-model="empadronadoForm.horario_inicio" type="time" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                        
                        <!-- Horario Fin (Solo si se muestran horarios) -->
                        <div v-if="fieldConfig.showHorarios">
                            <label class="block text-gray-700 font-medium mb-1">Horario de Fin</label>
                            <input v-model="empadronadoForm.horario_fin" type="time" required 
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                        </div>
                    </div>
                    
                    <!-- Días de Recolección (Solo si se muestran horarios) -->
                    <div v-if="fieldConfig.showHorarios" class="pt-4">
                        <label class="block text-gray-700 font-medium mb-3">Días de Recolección</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label v-for="dia in diasSemana" :key="dia.value" class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    :value="dia.value" 
                                    v-model="empadronadoForm.dias_recoleccion"
                                    class="rounded border-gray-300 text-chancay-azul focus:border-chancay-dorado focus:ring focus:ring-chancay-dorado focus:ring-opacity-50" 
                                />
                                <span class="text-sm text-gray-700">{{ dia.label }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <button type="button" @click="showEmpadronadoModal = false" 
                            class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isLoading"
                            class="px-4 py-2.5 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ isEditEmpadronado ? 'Actualizar' : 'Crear' }}
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
