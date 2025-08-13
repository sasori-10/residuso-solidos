<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref } from 'vue';
import * as THREE from 'three';
import gsap from 'gsap';

let scene, camera, renderer, icosahedron;
let animationId = null;
const mouseX = ref(0);
const mouseY = ref(0);
const cursorX = ref(0);
const cursorY = ref(0);
const cursorVisible = ref(true);
const cursorEnlarged = ref(false);

onMounted(() => {
  initThreeJS();
  animate();
  
  window.addEventListener('resize', onWindowResize);
  
  // Track mouse position for 3D object interaction
  document.addEventListener('mousemove', onMouseMove);
  
  // Add cursor visibility handling
  document.addEventListener('mouseenter', () => cursorVisible.value = true);
  document.addEventListener('mouseleave', () => cursorVisible.value = false);
  
  // Custom cursor animation for links and inputs
  const hoverables = document.querySelectorAll('a, button, input, [role="button"]');
  hoverables.forEach(element => {
    element.addEventListener('mouseenter', () => cursorEnlarged.value = true);
    element.addEventListener('mouseleave', () => cursorEnlarged.value = false);
  });
  
  // Initialize cursor animation
  animateCursor();
});

onBeforeUnmount(() => {
  if (animationId) {
    cancelAnimationFrame(animationId);
  }
  window.removeEventListener('resize', onWindowResize);
  document.removeEventListener('mousemove', onMouseMove);
  document.removeEventListener('mouseenter', () => cursorVisible.value = true);
  document.removeEventListener('mouseleave', () => cursorVisible.value = false);
  
  if (renderer) {
    renderer.dispose();
  }
});

function onMouseMove(e) {
  // Update cursor position
  cursorX.value = e.clientX;
  cursorY.value = e.clientY;
  
  // Normalize mouse coordinates for 3D effect
  // (-1 to 1 range for both X and Y)
  mouseX.value = (e.clientX / window.innerWidth) * 2 - 1;
  mouseY.value = -(e.clientY / window.innerHeight) * 2 + 1;
}

function animateCursor() {
  const cursorFollower = document.querySelector(".cursor-follower");
  
  if (cursorFollower) {
    // Animate follower with delay for trailing effect
    // Using a slow follow effect for subtle aura
    gsap.to(cursorFollower, {
      duration: 0.6,
      x: cursorX.value,
      y: cursorY.value,
      ease: "power2.out"
    });
  }
  
  requestAnimationFrame(animateCursor);
}

function initThreeJS() {
  // Create scene
  scene = new THREE.Scene();
  
  // Create camera
  camera = new THREE.PerspectiveCamera(
    75, 
    window.innerWidth / window.innerHeight, 
    0.1, 
    1000
  );
  camera.position.z = 5;
  
  // Create renderer
  renderer = new THREE.WebGLRenderer({ 
    canvas: document.getElementById('bg-canvas'),
    alpha: true,
    antialias: true
  });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  
  // Updated lighting for Chancay colors
  const ambientLight = new THREE.AmbientLight(0xF4C542, 0.5); // Dorado ambient
  scene.add(ambientLight);
  
  const directionalLight = new THREE.DirectionalLight(0x002F6C, 0.8); // Azul light
  directionalLight.position.set(5, 5, 5);
  scene.add(directionalLight);

  const secondaryLight = new THREE.DirectionalLight(0x2E8B57, 0.4); // Verde
  secondaryLight.position.set(-5, -3, -5);
  scene.add(secondaryLight);
  
  // Create icosahedron with updated colors
  const geometry = new THREE.IcosahedronGeometry(2, 1);
  const material = new THREE.MeshPhongMaterial({
    color: 0x002F6C, // Azul Chancay
    wireframe: true,
    wireframeLinewidth: 1,
    transparent: true,
    opacity: 0.3,
    flatShading: true,
    specular: 0xF4C542, // Dorado specular
    shininess: 40
  });
  icosahedron = new THREE.Mesh(geometry, material);
  scene.add(icosahedron);
  
  // Starting rotation
  icosahedron.rotation.x = 0.5;
  icosahedron.rotation.y = 0.5;
}

function animate() {
  animationId = requestAnimationFrame(animate);
  
  if (icosahedron) {
    // Base rotation - very subtle
    icosahedron.rotation.x += 0.0008;
    icosahedron.rotation.y += 0.001;
    
    // Mouse-influenced rotation - very gentle effect
    // Lerp (linear interpolation) towards mouse position
    const targetRotationX = icosahedron.rotation.x + (mouseY.value * 0.02 - icosahedron.rotation.x) * 0.02;
    const targetRotationY = icosahedron.rotation.y + (mouseX.value * 0.02 - icosahedron.rotation.y) * 0.02;
    
    icosahedron.rotation.x = targetRotationX;
    icosahedron.rotation.y = targetRotationY;
  }
  
  if (renderer) renderer.render(scene, camera);
}

function onWindowResize() {
  if (camera && renderer) {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  }
}

// Method to animate the 3D object when input focus changes
function animateObject(intensity) {
  if (icosahedron) {
    // Subtle focus animation with color change
    const targetScale = intensity ? 1.08 : 1;
    const targetColor = intensity ? new THREE.Color(0x2E8B57) : new THREE.Color(0x002F6C);
    const targetOpacity = intensity ? 0.4 : 0.3;
    
    // Animate scale
    gsap.to(icosahedron.scale, {
      x: targetScale,
      y: targetScale,
      z: targetScale,
      duration: 0.8,
      ease: "elastic.out(1, 0.3)"
    });
    
    // Animate color
    gsap.to(icosahedron.material.color, {
      r: targetColor.r,
      g: targetColor.g,
      b: targetColor.b,
      duration: 0.8
    });
    
    // Animate opacity
    gsap.to(icosahedron.material, {
      opacity: targetOpacity,
      duration: 0.8
    });
  }
}

// Expose method to child components
defineExpose({ animateObject });
</script>

<template>
  <!-- Regular cursor with subtle follower -->
  <div class="relative min-h-screen w-full overflow-hidden">
    <!-- Cursor follower only - don't hide original cursor -->
    <div v-show="cursorVisible" class="cursor-follower" :class="{ 'cursor-enlarged': cursorEnlarged }"></div>
    
    <!-- 3D Background -->
    <canvas 
      id="bg-canvas" 
      class="absolute top-0 left-0 -z-10 h-full w-full"
    ></canvas>
    
    <!-- Content Container -->
    <div class="relative z-10 flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0">
      <div>
        <Link href="/">
          <ApplicationLogo class="h-20 w-20 fill-current text-chancay-dorado" />
        </Link>
      </div>

      <div
        class="mt-6 w-full max-w-md overflow-hidden px-8 py-8 sm:rounded-xl chancay-card"
      >
        <slot />
      </div>
    </div>
  </div>
</template>

<style>
:root {
  --chancay-dorado: #F4C542;
  --chancay-azul: #002F6C;
  --chancay-verde: #2E8B57;
  --chancay-marron: #A0522D;
  --chancay-gris: #C0C0C0;
  --chancay-blanco: #F8F8F8;
}

body {
  background-color: var(--chancay-blanco);
  color: var(--chancay-azul);
  font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chancay-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(244, 197, 66, 0.15);
  box-shadow: 0 10px 25px rgba(0, 47, 108, 0.1);
  transform-style: preserve-3d;
  transition: all 0.4s ease;
  border-radius: 16px;
}

/* Cursor follower - subtle aura */
.cursor-follower {
  position: fixed;
  width: 30px;
  height: 30px;
  background: radial-gradient(circle, rgba(0, 47, 108, 0.15) 0%, rgba(0, 47, 108, 0) 70%);
  border-radius: 50%;
  pointer-events: none;
  transform: translate(-50%, -50%);
  z-index: 9998;
  transition: width 0.3s ease, height 0.3s ease;
}

.cursor-enlarged {
  width: 50px;
  height: 50px;
  background: radial-gradient(circle, rgba(244, 197, 66, 0.2) 0%, rgba(244, 197, 66, 0) 70%);
}

/* Text color classes with chancay colors */
.text-chancay-dorado {
  color: var(--chancay-dorado);
}

.text-chancay-azul {
  color: var(--chancay-azul);
}

.text-chancay-verde {
  color: var(--chancay-verde);
}

.text-chancay-marron {
  color: var(--chancay-marron);
}

.text-chancay-gris {
  color: var(--chancay-gris);
}

/* Add smooth scrolling to the page */
html {
  scroll-behavior: smooth;
}
</style>
