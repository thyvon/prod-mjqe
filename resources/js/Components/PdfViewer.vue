<template>
  <div>
    <iframe :src="pdfUrl" width="100%" height="600px"></iframe>
  </div>
</template>

<script setup>
const props = defineProps({
  pdfUrl: {
    type: String,
    required: true,
  },
});
</script>

<style scoped>
iframe {
  border: none;
}
</style>
