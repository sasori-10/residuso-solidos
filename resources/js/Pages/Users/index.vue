<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive, onMounted } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { router, Head } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
    roles: Array,
});

const users = ref(props.users ?? []);
const roles = ref(props.roles ?? []);
const showModal = ref(false);
const isLoading = ref(false);
const isEdit = ref(false);
const form = reactive({
    id: null,
    name: '',
    email: '',
    password: '',
    role: roles.value[0] ?? '',
});

// Abrir modal para crear usuario
function openCreate() {
    isEdit.value = false;
    Object.assign(form, { id: null, name: '', email: '', password: '', role: roles.value[0] ?? '' });
    showModal.value = true;
}

// Abrir modal para editar usuario
function openEdit(user) {
    isEdit.value = true;
    Object.assign(form, { id: user.id, name: user.name, email: user.email, password: '', role: user.role });
    showModal.value = true;
}

// Configuración personalizada de SweetAlert2 con tema moderno
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

// Guardar usuario con notificaciones mejoradas
async function saveUser() {
    isLoading.value = true;
    
    // Mostrar loader mientras se procesa
    Swal.fire({
        title: 'Procesando...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    if (isEdit.value) {
        router.put(`/users/${form.id}`, {
            name: form.name,
            email: form.email,
            password: form.password,
            role: form.role,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showModal.value = false;
                Swal.close(); // Cerrar el loader
                
                // Actualizar la lista de usuarios desde la respuesta del servidor
                users.value = page.props.users;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Usuario actualizado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close(); // Cerrar el loader
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor verifica los datos ingresados',
                    confirmButtonColor: '#002F6C'
                });
            }
        });
    } else {
        router.post('/users', {
            name: form.name,
            email: form.email,
            password: form.password,
            role: form.role,
        }, {
            onSuccess: (page) => {
                isLoading.value = false;
                showModal.value = false;
                Swal.close(); // Cerrar el loader
                
                // Actualizar la lista de usuarios desde la respuesta del servidor
                users.value = page.props.users;
                
                Toast.fire({
                    icon: 'success',
                    title: 'Usuario creado correctamente'
                });
            },
            onError: (errors) => { 
                isLoading.value = false;
                Swal.close(); // Cerrar el loader
                
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

// Eliminar usuario con confirmación moderna
function deleteUser(user) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: `¿Estás seguro de eliminar a ${user.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#002F6C',
        cancelButtonColor: '#A0522D',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: `rgba(0, 47, 108, 0.4)`,
        showClass: {
            popup: 'animate__animated animate__fadeIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOut animate__faster'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
            
            router.delete(`/users/${user.id}`, {
                onSuccess: (page) => {
                    // Actualizar la lista de usuarios desde la respuesta del servidor
                    users.value = page.props.users;
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Usuario eliminado correctamente'
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el usuario',
                        confirmButtonColor: '#002F6C'
                    });
                }
            });
        }
    });
}
</script>

<template>
    <Head title="Gestión de Usuarios" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestión de Usuarios
                </h2>
                <button @click="openCreate"
                    class="bg-chancay-azul hover:bg-chancay-verde text-white font-semibold px-4 py-2 rounded-lg shadow transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nuevo Usuario
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Card con búsqueda y filtros -->
                        <div class="mb-6 bg-chancay-blanco p-4 rounded-lg shadow-sm">
                            <div class="flex flex-col md:flex-row gap-4 justify-between">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                        </svg>
                                    </div>
                                    <input type="search" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-chancay-dorado focus:border-chancay-dorado" placeholder="Buscar usuarios..." />
                                </div>
                                <div class="flex gap-2">
                                    <select class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-chancay-dorado focus:border-chancay-dorado p-2">
                                        <option selected>Filtrar por rol</option>
                                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de usuarios moderna -->
                        <div class="overflow-x-auto relative">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-chancay-azul text-white">
                                    <tr>
                                        <th scope="col" class="py-3 px-4 rounded-tl-lg">Nombre</th>
                                        <th scope="col" class="py-3 px-4">Email</th>
                                        <th scope="col" class="py-3 px-4">Rol</th>
                                        <th scope="col" class="py-3 px-4 rounded-tr-lg text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ user.name }}</td>
                                        <td class="py-3 px-4">{{ user.email }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="{
                                                    'bg-blue-100 text-blue-800': user.role === 'Admin',
                                                    'bg-green-100 text-green-800': user.role === 'Usuario',
                                                    'bg-yellow-100 text-yellow-800': user.role === 'Invitado',
                                                }">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button @click="openEdit(user)" class="text-chancay-verde hover:text-chancay-dorado transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteUser(user)" class="text-red-600 hover:text-red-800 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="users.length === 0">
                                        <td colspan="4" class="text-center py-6 text-gray-500">No hay usuarios registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <div class="flex items-center justify-between mt-6">
                            <div class="text-sm text-gray-500">
                                Mostrando <span class="font-medium">{{ users.length }}</span> usuarios
                            </div>
                            <div class="flex gap-1">
                                <button class="px-3 py-1 rounded border text-sm hover:bg-gray-50">Anterior</button>
                                <button class="px-3 py-1 rounded border text-sm bg-chancay-azul text-white">1</button>
                                <button class="px-3 py-1 rounded border text-sm hover:bg-gray-50">Siguiente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal moderno de Crear/Editar Usuario -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative animate__animated animate__fadeInUp animate__faster">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-xl font-bold text-chancay-azul">
                        {{ isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveUser" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Nombre</label>
                        <input v-model="form.name" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Email</label>
                        <input v-model="form.email" type="email" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div v-if="!isEdit">
                        <label class="block text-gray-700 font-medium mb-1">Contraseña</label>
                        <input v-model="form.password" type="password" required 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div v-else>
                        <label class="block text-gray-700 font-medium mb-1">Nueva Contraseña (opcional)</label>
                        <input v-model="form.password" type="password" 
                            placeholder="Dejar vacío para mantener la actual"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition" />
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Rol</label>
                        <select v-model="form.role" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-chancay-dorado focus:ring-2 focus:ring-chancay-dorado/50 transition">
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <button type="button" @click="showModal = false" 
                            class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isLoading"
                            class="px-4 py-2.5 rounded-lg bg-chancay-azul text-white font-medium hover:bg-chancay-verde transition flex items-center gap-2">
                            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ isEdit ? 'Guardar cambios' : 'Crear usuario' }}
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

/* Importar Animate.css para animaciones */
@import "https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css";

/* Mejoras responsivas */
@media (max-width: 640px) {
  .sm\:rounded-lg {
    border-radius: 0.5rem;
  }
}
</style>
