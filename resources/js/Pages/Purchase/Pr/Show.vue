<script setup>
import { defineProps, ref } from 'vue';
import Main from '@/Layouts/Main.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  purchaseRequest: Object, // The Purchase Request data passed to the component
  products: Array, // List of products to select from
  users: Array,
});

const getUserDetails = (userId) => {
  const user = props.users.find(user => user.id === userId);
  return user ? user : { name: 'Unknown User', card_id: '', position: '', phone: '', extension: '' };
};

const userDetails = getUserDetails(props.purchaseRequest.request_by);

const form = ref({
  id: props.purchaseRequest.id || '',
  pr_number: props.purchaseRequest.pr_number || '',
  request_date: props.purchaseRequest.request_date ? props.purchaseRequest.request_date.split('T')[0] : '',
  request_by: userDetails.name || '',
  campus: props.purchaseRequest.campus || '',
  division: props.purchaseRequest.division || '',
  department: props.purchaseRequest.department || '',
  purpose: props.purchaseRequest.purpose || '',
  is_urgent: props.purchaseRequest.is_urgent || false,
  status: props.purchaseRequest.status || '',
  pr_items: props.purchaseRequest.pr_items || [],
});

const validationErrors = ref({});

const format = (value, type) => {
  if (type === 'date') {
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
  }
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Pending': return 'badge bg-warning';
    case 'Approved': return 'badge bg-success';
    case 'Rejected': return 'badge bg-danger';
    case 'Cancelled': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const calculateTotalAmount = () => {
  return form.value.pr_items.reduce((total, item) => total + parseFloat(item.total_price || 0), 0);
};

const getItemStatusBadgeClass = (status) => {
  switch (status) {
    case 'Pending': return 'badge bg-warning';
    case 'Approved': return 'badge bg-success';
    case 'Rejected': return 'badge bg-danger';
    case 'Cancelled': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const invoiceItems = ref([]);
const prItemsTableInstance = ref(null);
const invoiceItemsTableInstance = ref(null);

const initializeDataTable = (selector, options) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable(options);
};

onMounted(async () => {
  try {
    const response = await axios.get(`/purchase-invoice-itemspr/${props.purchaseRequest.id}`);
    invoiceItems.value = response.data;

    prItemsTableInstance.value = initializeDataTable('#pr-items-table', {
      responsive: false,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: form.value.pr_items,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'product_id', render: (data) => props.products.find(p => p.id === data)?.sku || 'N/A' },
        { data: 'product_id', render: (data) => props.products.find(p => p.id === data)?.product_description || 'N/A' },
        { data: 'remark' },
        { data: 'campus' },
        { data: 'division' },
        { data: 'department' },
        { data: 'qty' },
        { data: 'qty_cancel'},
        { data: 'qty_purchase'},
        { data: 'qty_pending'},
        { data: 'uom' },
        { data: 'unit_price' },
        { data: 'total_price' },
        { data: 'status', render: (data) => `<span class="${getItemStatusBadgeClass(data)}">${data}</span>` },
        { data: 'force_close', render: (data) => data === 1 ? '<span class="badge bg-danger"><i class="fa fa-check-circle"></i> Yes</span>' : '<span class="badge bg-secondary"><i class="fa fa-times-circle"></i> No</span>' },
      ],
    });

    invoiceItemsTableInstance.value = initializeDataTable('#invoice-items-table', {
      responsive: false,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: invoiceItems.value,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'invoice_date', render: (data) => moment(data).format('MMM DD, YYYY') },
        { 
          data: 'invoice.pi_number', 
          render: (data, type, row) => `<a href="/invoices/${row.invoice.id}" class="text-primary">${data}</a>` 
        },
        { data: 'invoice_no' },
        { data: 'supplier.name' },
        { data: 'product.sku' },
        { data: 'description' },
        { data: 'qty' },
        { data: 'uom' },
        { 
          data: 'unit_price',
            render: function (data, type, row) {
                return parseFloat(data) ? parseFloat(data).toFixed(4) : '0.00';
            }
        },
        { 
          data: 'total_price',
            render: function (data, type, row) {
                return parseFloat(data) ? parseFloat(data).toFixed(4) : '0.00';
            }
        },
        { data: 'currency', render: (data) => data === 1 ? 'USD' : 'KHR' },
        { data: 'purchased_by.name' },
        { data: 'stop_purchase', render: (data) => data === 1 ? '<span class="badge bg-danger"><i class="fa fa-check-circle"></i> Yes</span>' : '<span class="badge bg-secondary"><i class="fa fa-times-circle"></i> No</span>' },
      ],
    });
  } catch (error) {
    console.error('Error fetching invoice items:', error);
  }
});
</script>

<template>
  <Main>
    <Head title="Purchase Request Details" />
    <div class="mb-3">
      <Link href="/purchase-requests" class="btn btn-primary"><i class="fa-solid fa-arrow-left-long"></i> Back</Link>
    </div>
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Purchase Request Details</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="invoice">
          <!-- Invoice Company -->
          <div class="invoice-company">
            {{ form.pr_number }}
          </div>

          <!-- Invoice Header -->
          <div class="invoice-header row">
            <div class="invoice-from col-md-6">
              <address class="mt-5px mb-5px">
                <strong class="text-dark">Requester: {{ form.request_by }}</strong><br />
                Card ID: {{ userDetails.card_id }}<br />
                Position: {{ userDetails.position }}<br />
                Phone: {{ userDetails.phone }}<br />
                Extension: {{ userDetails.extension }}<br />
                Purpose: {{ form.purpose }}<br />
              </address>
            </div>

            <div class="invoice-date col-md-6 text-end">
              <div class="invoice-detail text-start" style="float: right;">
                <div class="date text-dark mt-5px">{{ format(form.request_date, 'date') }}</div>
                Status: <span :class="getStatusBadgeClass(form.status)">{{ form.status }}</span><br />
                Campus: {{ form.campus }}<br />
                Division: {{ form.division }}<br />
                Department: {{ form.department }}<br />
                Urgent: <span :class="form.is_urgent ? 'badge bg-primary' : 'badge bg-danger'">{{ form.is_urgent ? 'Yes' : 'No' }}</span>
              </div>
            </div>
          </div>

          <!-- Invoice Content -->
          <div class="invoice-content">
            <!-- PR Items Section -->
            <div class="table-responsive">
              <table id="pr-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
                <thead class="text-center">
                  <tr>
                    <th>#</th>
                    <th>Product Code</th>
                    <th>Product Description</th>
                    <th>Remark</th>
                    <th>Campus</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Qty</th>
                    <th>Qty Cancel</th>
                    <th>Qty Purchase</th>
                    <th>Qty Pending</th>
                    <th>UOM</th>
                    <th>Price</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Force Close?</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel panel-inverse mt-2">
      <div class="panel-heading">
        <h4 class="panel-title">Purchased Item</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="invoice-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>PI Number</th>
                <th>Invoice Number</th>
                <th>Supplier</th>
                <th>Product Code</th>
                <th>Product Description</th>
                <th>Qty</th>
                <th>UOM</th>
                <th>Price</th>
                <th>Total Price</th>
                <th>Currency</th>
                <th>Purchaser</th>
                <th>Force Close?</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Main>
</template>

<style scoped>
/* Removed print-related styles */
</style>
