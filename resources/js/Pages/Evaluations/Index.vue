<template>
  <Main>
    <Head title="Evaluations List" />

    <div class="container mx-auto p-4">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Evaluations</h1>
        <inertia-link
          href="/evaluations/create"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          + Create New
        </inertia-link>
      </div>

      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead>
          <tr>
            <th class="py-2 px-4 border-b">#</th>
            <th class="py-2 px-4 border-b text-left">Title</th>
            <th class="py-2 px-4 border-b text-left">Created At</th>
            <th class="py-2 px-4 border-b">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(evaluation, index) in evaluations"
            :key="evaluation.id"
            class="hover:bg-gray-100"
          >
            <td class="py-2 px-4 border-b text-center">{{ index + 1 }}</td>
            <td class="py-2 px-4 border-b">{{ evaluation.title }}</td>
            <td class="py-2 px-4 border-b">{{ new Date(evaluation.created_at).toLocaleDateString() }}</td>
            <td class="py-2 px-4 border-b text-center space-x-2">
              <inertia-link
                :href="`/evaluations/${evaluation.id}/edit`"
                class="text-blue-600 hover:underline"
              >
                Edit
              </inertia-link>
              <button
                @click="destroy(evaluation.id)"
                class="text-red-600 hover:underline"
              >
                Delete
              </button>
            </td>
          </tr>

          <tr v-if="evaluations.length === 0">
            <td colspan="4" class="py-4 text-center text-gray-500">
              No evaluations found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </Main>
</template>

<script setup>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  evaluations: Array,
});

function destroy(id) {
  if (confirm('Are you sure you want to delete this evaluation?')) {
    Inertia.delete(`/evaluations/${id}`);
  }
}
</script>
