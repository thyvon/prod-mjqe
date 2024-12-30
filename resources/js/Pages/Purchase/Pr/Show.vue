<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  purchaseRequest: Object, // The Purchase Request data passed to the component
  products: Array, // List of products to select from
});
</script>

<template>
  <div>
    <h2>Purchase Request Details</h2>

    <!-- PR Details -->
    <div class="mb-4">
      <h5>PR Number: {{ props.purchaseRequest.pr_number }}</h5>
      <p><strong>Request Date:</strong> {{ props.purchaseRequest.request_date }}</p>
      <p><strong>Requested By:</strong> {{ props.purchaseRequest.request_by }}</p>
      <p><strong>Campus:</strong> {{ props.purchaseRequest.campus }}</p>
      <p><strong>Division:</strong> {{ props.purchaseRequest.division }}</p>
      <p><strong>Department:</strong> {{ props.purchaseRequest.department }}</p>
      <p><strong>Purpose:</strong> {{ props.purchaseRequest.purpose }}</p>
      <p><strong>Urgent:</strong> {{ props.purchaseRequest.is_urgent ? 'Yes' : 'No' }}</p>
    </div>

    <!-- PR Items Section -->
    <h4>PR Items</h4>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Product</th>
          <th>Qty</th>
          <th>UOM</th>
          <th>Price</th>
          <th>Total</th>
          <th>Remark</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in props.purchaseRequest.prItems" :key="index">
          <td>{{ products.find(p => p.id === item.product_id)?.product_description || 'N/A' }}</td>
          <td>{{ item.qty }}</td>
          <td>{{ item.uom }}</td>
          <td>{{ item.price }}</td>
          <td>{{ item.total_price }}</td>
          <td>{{ item.remark }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
