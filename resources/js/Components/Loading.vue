<template>
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </template>

  <script setup>
  import { ref } from 'vue';

  const isLoading = ref(false);

  defineExpose({ isLoading });
  </script>

  <style scoped>
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.062);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }
  </style>
