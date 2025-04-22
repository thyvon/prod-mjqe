<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  product: Object,
  purchasedItems: Array,
});

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
          { 
            data: null, 
            render: (data, type, row) => props.product.sku // Use the SKU from the product object
          },
          { data: 'description' },
          { data: 'qty' },
          { 
            data: 'currency', 
            render: function (data) {
              return data === 1 ? 'USD' : 'KHR';
            }
          },
          {             
            data: null, 
            render: (data, type, row) => props.product.uom
          },
          { data: 'unit_price' },
          { data: 'total_price', render: (data, type, row) => (row.qty * row.unit_price).toFixed(2) },
          { data: 'purchased_by', render: (data) => data.name },
          { 
            data: 'invoice_date', 
            render: (data) => {
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return new Date(data).toLocaleDateString('en-US', options); // Formats to "April 21, 2025"
            },
          },
          
          { data: 'supplier', render: (data) => data.name },
          { 
            data: 'invoice', 
            render: (data) => `<a inertia href="/invoices/${data.id}">${data.pi_number}</a>` 
          },
        ],
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="`Purchased Items for ${props.product.product_description}`" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Purchased history for product: {{ props.product.product_description }}</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <Link href="/products" class="btn btn-secondary btn-sm mb-3">
          <i class="fa fa-arrow-left"></i> Back to Products
        </Link>
        <!-- Purchased Items Table -->
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
              <th>Invoice Date</th> <!-- Added Invoice Date column -->
              <th>Supplier</th> <!-- Added Supplier column -->
              <th>Invoice Ref</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </Main>
</template>