<template>
    <MainLayout> <!-- Use Main layout -->
        <Head title="User List" />

        <div class="container mt-5">
            <h1 class="mb-4">User List</h1>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Card ID</th>
                        <th>Position</th>
                        <th>Campus</th>
                        <th>Division</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Extension</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.card_id }}</td>
                        <td>{{ user.position }}</td>
                        <td>{{ user.campus }}</td>
                        <td>{{ user.division }}</td>
                        <td>{{ user.department }}</td>
                        <td>{{ user.phone }}</td>
                        <td>{{ user.extension }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import MainLayout from '@/Layouts/Main.vue'; // Import Main layout

const props = defineProps({
    users: Array,
});
</script>

<style scoped>
/* Optional styling specific to this component */
</style>
