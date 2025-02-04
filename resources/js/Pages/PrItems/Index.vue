<script setup>
import { ref, computed } from 'vue'; // Import computed from vue
import { Head, Link } from '@inertiajs/vue3'; // Import Link from Inertia.js
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  prItems: Array, // Add the PR items data
});

const search = ref('');

const filteredPrItems = computed(() => {
  if (!search.value) {
    return props.prItems;
  }
  return props.prItems.filter(item =>
    item.product.product_description.toLowerCase().includes(search.value.toLowerCase()) ||
    item.purchaseRequest.pr_number.toLowerCase().includes(search.value.toLowerCase())
  );
});

</script>

<template>
  <Main>
    <Head title="PR Items List" />
    <div>
      <h1 class="mb-4">PR Items List</h1>
      <div class="mb-4">
        <input v-model="search" type="text" class="form-control" placeholder="Search PR Items..." />
      </div>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>PR Number</th>
            <th>Product</th>
            <th>Qty</th>
            <th>UOM</th>
            <th>Price</th>
            <th>Total Price</th>
            <th>Campus</th>
            <th>Division</th>
            <th>Department</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredPrItems" :key="item.id">
            <td>{{ item.purchaseRequest?.pr_number || 'N/A' }}</td>
            <td>{{ item.product?.product_description || 'N/A' }}</td>
            <td>{{ item.qty }}</td>
            <td>{{ item.uom }}</td>
            <td>{{ item.price }}</td>
            <td>{{ item.total_price }}</td>
            <td>{{ item.campus }}</td>
            <td>{{ item.division }}</td>
            <td>{{ item.department }}</td>
            <td>
              <Link :href="`/pr-items/${item.id}/edit`" class="btn btn-warning btn-sm">Edit</Link>
              <Link :href="`/pr-items/${item.id}`" class="btn btn-info btn-sm">View</Link>
              <form :action="`/pr-items/${item.id}`" method="POST" style="display:inline;">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </Main>
</template>
