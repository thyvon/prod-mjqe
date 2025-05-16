<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  product: Object,
  purchasedItems: Array,
});

function formatDateTime(dateString) {
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  };
  return new Date(dateString).toLocaleString('en-US', options);
}

function convertStatus(status) {
  if (status === 1) {
    return 'Active';
  } else {
    return 'Inactive';
  }
}

const goBack = () => {
  window.history.back();
};

let dataTableInstance;

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#purchased-items-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: false,
        data: props.purchasedItems,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: null, render: () => props.product.sku },
          { data: 'description' },
          { data: 'qty' },
          { data: 'currency', render: (data) => (data === 1 ? 'USD' : 'KHR') },
          { data: null, render: () => props.product.uom },
          { data: 'unit_price' },
          { data: 'total_price', render: (data, type, row) => (row.qty * row.unit_price).toFixed(2) },
          { data: 'purchased_by', render: (data) => data.name },
          { data: 'invoice_date', render: (data) => new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) },
          { data: 'supplier', render: (data) => data.name },
          { data: 'invoice', render: (data) => `<a inertia href="/invoices/${data.id}">${data.pi_number}</a>` },
        ],
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="`Product Detail - ${props.product.product_description}`" />

    <!-- Product Detail Section -->
    <div class="bg-white rounded p-6 mb-6 shadow">
    <button class="btn btn-secondary" @click="goBack"><i class="fa-solid fa-arrow-left-long"></i> Back</button>
      <h3 class="text-xl font-bold mb-4">Product Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Product Image -->
      <div class="flex justify-center items-center">
        <div class="aspect-square w-full max-w-md overflow-hidden rounded bg-white-100 shadow">
          <div class="zoom-container">
            <img 
              :src="product.image_path ? `/storage/${product.image_path}` : 'default.jpg'"
              alt="Product Image" 
              class="object-contain"
            />
          </div>
        </div>
      </div>

        <!-- Product Info -->
      <div class="rounded p-6 w-full h-full shadow bg-white-100 space-y-6 text-base">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 ">

          <div class="field-box">
            <div class="field-label">Item Code</div>
            <div class="text-gray-800">{{ props.product.sku }}</div>
          </div>

          <div class="field-box">
            <div class="field-label">UOM</div>
            <div class="text-gray-800">{{ props.product.uom }}</div>
          </div>

          <div class="field-box">
            <div class="field-label">Category</div>
            <div class="text-gray-800">{{ props.product.category?.name || 'N/A' }}</div>
          </div>

          <div class="field-box">
            <div class="field-label">Status</div>
            <div class="text-gray-800">{{ convertStatus(props.product.status) || props.product.status || 'N/A' }}</div>
          </div>

          <div class="field-box">
            <div class="field-label">Latest Price</div>
            <div class="text-gray-800">{{ props.product.price }} USD</div>
          </div>

          <div class="field-box">
            <div class="field-label">Average Price Last 30 Months</div>
            <div class="text-gray-800">{{ props.product.avg_price }} USD</div>
          </div>

          <div class="field-box">
            <div class="field-label">Created At</div>
            <div class="text-gray-800">{{ formatDateTime(props.product.created_at) }}</div>
          </div>

          <div class="field-box">
            <div class="field-label">Updated At</div>
            <div class="text-gray-800">{{ formatDateTime(props.product.updated_at) }}</div>
          </div>

          <!-- Description spanning two columns -->
          <div class="field-box sm:col-span-2">
            <div class="field-label">Description</div>
            <div class="text-gray-800">{{ props.product.product_description }}</div>
          </div>

        </div>
      </div>
      </div>
    </div>

    <!-- Purchased History Section -->
    <div class="panel panel-inverse shadow">
      <div class="panel-heading">
        <h4 class="panel-title">Purchased History</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <table id="purchased-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Item Code</th>
              <th>Description</th>
              <th>Quantity</th>
              <th>Currency</th>
              <th>UOM</th>
              <th>Price</th>
              <th>Total</th>
              <th>Purchased By</th>
              <th>Invoice Date</th>
              <th>Supplier</th>
              <th>Invoice Ref</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </Main>
</template>

<style scoped>
  .field-box {
    position: relative;
    border: 1px solid #d1d5db; /* gray-300 */
    padding: 0.9rem 0.9rem 0.9rem 0.9rem;
    background: white;
  
  }

.field-label {
  position: absolute;
  top: -0.75rem;
  left: 1rem;
  background: rgb(255, 255, 255);
  padding: 0 0.75rem;
  font-weight: 100;
  color: #374151; /* gray-700 */
  font-size: 0.875rem; /* text-sm */
  user-select: none;
  border-radius: 9999px; /* fully rounded */
}

.zoom-container {
  overflow: hidden;
  cursor: zoom-in;
  transition: transform 0.3s ease;
  display: inline-block;
  width: 100%;
  height: 100%;
}

.zoom-container img {
  transition: transform 0.3s ease;
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.zoom-container:hover img {
  transform: scale(1.5);
  cursor: zoom-out;
  z-index: 10;
  position: relative;
}
</style>
