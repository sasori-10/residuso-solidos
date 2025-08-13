<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// References
const guestLayoutRef = ref(null);

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Animate 3D object when input focus changes
const handleFocus = () => {
    if (guestLayoutRef.value) {
        guestLayoutRef.value.animateObject(true);
    }
};

const handleBlur = () => {
    if (guestLayoutRef.value) {
        guestLayoutRef.value.animateObject(false);
    }
};
</script>

<template>
    <GuestLayout ref="guestLayoutRef">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-chancay-verde">
            {{ status }}
        </div>
        
        <h1 class="mb-6 text-center text-2xl font-bold text-chancay-azul">
            Bienvenido
        </h1>

        <form @submit.prevent="submit" class="chancay-form">
            <div class="mb-6">
                <InputLabel for="email" value="Correo Electrónico" class="text-chancay-azul font-medium" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full chancay-input"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    placeholder="correo@ejemplo.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mb-6">
                <InputLabel for="password" value="Contraseña" class="text-chancay-azul font-medium" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full chancay-input"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mb-6 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" 
                      class="chancay-checkbox" />
                    <span class="ms-2 text-sm text-chancay-azul">
                        Recordarme
                    </span>
                </label>
            </div>

            <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-chancay-verde hover:text-chancay-azul transition-colors"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton
                    class="chancay-button w-full sm:w-auto"
                    :class="{ 'opacity-70': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
    
    <!-- Footer posicionado detrás del formulario -->
    <footer class="wave-footer">
        <div class="wave-container">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
            <div class="wave wave4"></div>
        </div>
        <div class="footer-content">
            <p>© 2025 Municipalidad Distrital de Chancay — Oficina de Tecnología de la Información y Gobierno Electrónico</p>
        </div>
    </footer>
</template>

<style scoped>
.chancay-form {
    padding: 1rem;
    transition: all 0.3s ease;
}

.chancay-input {
    background-color: var(--chancay-blanco);
    border: 1px solid var(--chancay-gris);
    color: var(--chancay-azul);
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.chancay-input:focus {
    background: white;
    border-color: var(--chancay-dorado);
    outline: none;
    box-shadow: 0 0 0 3px rgba(244, 197, 66, 0.2);
}

.chancay-input::placeholder {
    color: var(--chancay-gris);
    opacity: 0.7;
}

.chancay-button {
    background: var(--chancay-azul);
    color: white;
    border: none;
    padding: 0.75rem 1.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.chancay-button:hover:not(:disabled) {
    background: var(--chancay-verde);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.chancay-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.chancay-button::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(
        circle,
        rgba(244, 197, 66, 0.1) 0%,
        rgba(244, 197, 66, 0) 70%
    );
    transform: scale(0);
    transition: transform 0.5s ease;
    border-radius: 50%;
    z-index: 0;
}

.chancay-button:hover::after {
    transform: scale(1);
}

.chancay-checkbox:checked {
    background-color: var(--chancay-dorado);
    border-color: var(--chancay-dorado);
}

.chancay-checkbox:focus {
    box-shadow: 0 0 0 2px rgba(244, 197, 66, 0.3);
}

/* Footer Styles - updated to be smaller and behind the form */
.wave-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 80px; /* Altura reducida */
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    z-index: 5; /* Reducido para estar detrás del formulario */
    overflow: hidden;
}

.wave-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 200%;
    height: 100%;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' fill='%23004996' opacity='.8'%3E%3C/path%3E%3C/svg%3E");
    background-size: 1200px 100%;
    animation: wave-animation 15s linear infinite;
}

.wave1 {
    opacity: 0.6;  /* Más visible */
    background-color: rgba(60, 110, 180, 0.6); /* Azul más claro y visible */
    animation: wave-animation 10s linear infinite;
    z-index: 1;
}

.wave2 {
    opacity: 0.7;  /* Más visible */
    background-color: rgba(50, 100, 165, 0.65); /* Azul intermedio */
    animation: wave-animation 13s linear reverse infinite;
    animation-delay: -5s;
    z-index: 2;
}

.wave3 {
    opacity: 0.6;  /* Más visible */
    background-color: rgba(40, 90, 150, 0.7); /* Azul medio */
    animation: wave-animation 8s linear infinite;
    animation-delay: -2s;
    z-index: 3;
}

.wave4 {
    background-color: rgba(0, 47, 108, 0.75); /* Azul institucional más claro */
    animation: wave-animation 15s linear reverse infinite;
    animation-delay: -7s;
    z-index: 4;
}

.footer-content {
    position: relative;
    width: 100%;
    padding: 10px 20px; /* Padding reducido */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
}

.footer-content p {
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.8rem; /* Fuente más pequeña */
    font-weight: 500;
    letter-spacing: 0.5px;
    text-align: center;
    margin: 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
}

/* Media queries ajustados para el footer más pequeño */
@media (max-width: 1024px) {
    .wave-footer {
        height: 70px;
    }
}

@media (max-width: 768px) {
    .wave-footer {
        height: 60px;
    }
    
    .footer-content p {
        font-size: 0.7rem;
    }
}

@media (max-width: 640px) {
    .wave-footer {
        height: 55px;
    }
}

@media (max-width: 480px) {
    .wave-footer {
        height: 50px;
    }
    
    .footer-content p {
        font-size: 0.6rem;
        max-width: 90%;
    }
}

/* Soporte para pantallas muy antiguas o pequeñas */
@media (max-width: 360px) {
    .wave-footer {
        height: 45px;
    }
    
    .footer-content p {
        font-size: 0.55rem;
        max-width: 95%;
    }
}

/* Ajustes para pantallas grandes */
@media (min-width: 1440px) {
    .wave-footer {
        height: 90px;
    }
}

/* Ajuste para asegurar que el formulario quede por encima del footer */
.chancay-card {
    position: relative;
    z-index: 10; /* Mayor que el z-index del footer */
    margin-bottom: 2rem; /* Espacio adicional en la parte inferior */
    background-color: rgba(255, 255, 255, 0.95); /* Fondo más opaco */
}
</style>
