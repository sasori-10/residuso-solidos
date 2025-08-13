<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const empadronadosData = ref([]);
const residuosData = ref([]);
const zonasData = ref([]);

onMounted(() => {
    // Fetch data for charts
    axios.get('/api/empadronados-stats').then(response => {
        empadronadosData.value = response.data;
        renderEmpadronadosChart();
    }).catch(error => {
        console.error('Error fetching empadronados stats:', error);
    });

    axios.get('/api/tipo-residuos-stats').then(response => {
        residuosData.value = response.data;
        renderResiduosChart();
    }).catch(error => {
        console.error('Error fetching tipo residuos stats:', error);
    });

    axios.get('/api/zonas-stats').then(response => {
        zonasData.value = response.data;
        renderZonasChart();
    }).catch(error => {
        console.error('Error fetching zonas stats:', error);
    });
});

function renderEmpadronadosChart() {
    const ctx = document.getElementById('empadronadosChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: empadronadosData.value.map(item => item.nombre),
            datasets: [{
                label: 'Cantidad de Empadronados',
                data: empadronadosData.value.map(item => item.empadronados_count),
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        }
    });
}

function renderResiduosChart() {
    const ctx = document.getElementById('residuosChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: residuosData.value.map(item => item.tipo_residuos), // Correct property name
            datasets: [{
                label: 'Distribución de Tipos de Residuos',
                data: residuosData.value.map(item => item.count),
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
            }]
        }
    });
}

function renderZonasChart() {
    const ctx = document.getElementById('zonasChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: zonasData.value.map(item => item.nombre),
            datasets: [{
                label: 'Evolución de Sectores',
                data: zonasData.value.map(item => item.sectores_count),
                borderColor: 'rgba(153, 102, 255, 1)',
                tension: 0.1
            }]
        }
    });
}
</script>

<template>
    <Head title="Dasdhboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
                    <div class="bg-white shadow-lg sm:rounded-lg p-8">
                        <h3 class="text-lg font-semibold mb-4">Empadronados por Tipo</h3>
                        <canvas id="empadronadosChart" class="h-96"></canvas>
                    </div>
                    <div class="bg-white shadow-lg sm:rounded-lg p-8">
                        <h3 class="text-lg font-semibold mb-4">Distribución de Tipos de Residuos</h3>
                        <canvas id="residuosChart" class="h-96"></canvas>
                    </div>
                    <div class="bg-white shadow-lg sm:rounded-lg p-8">
                        <h3 class="text-lg font-semibold mb-4">Evolución de Sectores</h3>
                        <canvas id="zonasChart" class="h-96"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
