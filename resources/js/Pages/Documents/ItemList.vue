<template>
    <Head title="Documents" />
    <Main>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">All Document Items</h1>
        
            <table class="w-full table-auto border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-left">#</th>
                    <th class="border px-4 py-2 text-left">Article Name</th>
                    <th class="border px-4 py-2 text-left">Section</th>
                    <th class="border px-4 py-2 text-left">Status</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, index) in items" :key="item.id">
                    <td class="border px-4 py-2">{{ index + 1 }}</td>
                    <td class="border px-4 py-2">{{ item.article_name }}</td>
                    <td class="border px-4 py-2">{{ item.documentation?.section_name }}</td>
                    <td class="border px-4 py-2">{{ item.status }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </Main>
  </template>
  
  <script setup>
import Main from '@/Layouts/Main.vue';
  defineProps({
    items: Array,
  });
  </script>
  