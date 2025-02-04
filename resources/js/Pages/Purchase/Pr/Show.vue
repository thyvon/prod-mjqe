<script setup>
import { defineProps, ref } from 'vue';
import Main from '@/Layouts/Main.vue';
import { Head, Link } from '@inertiajs/vue3';

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

const printInvoice = () => {
  const printContents = document.querySelector('.invoice').innerHTML;
  const originalContents = document.body.innerHTML;

  document.body.innerHTML = printContents;
  window.print();
  document.body.innerHTML = originalContents;
  window.location.reload();
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
</script>

<template>
  <Main>
    <Head title="Purchase Request Details" />
    <div class="mb-3">
      <Link href="/purchase-requests" class="btn btn-primary"><i class="fa-solid fa-arrow-left-long"></i> Back</Link>
    </div>
    <div class="invoice" ref="invoiceContent">
      <!-- Invoice Company -->
      <div class="invoice-company">
        <span class="float-end hidden-print">
          <a href="javascript:;" class="btn btn-sm btn-white mb-10px" @click="printInvoice">
            <i class="fa fa-print t-plus-1 fa-fw fa-lg"></i> Print
          </a>
        </span>
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
          <table class="table table-invoice">
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
                <th>UOM</th>
                <th>Price</th>
                <th>Total Price</th>
                <th class="hidden-print">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in form.pr_items" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ products.find(p => p.id === item.product_id)?.sku || 'N/A' }}</td>
                <td>{{ products.find(p => p.id === item.product_id)?.product_description || 'N/A' }}</td>
                <td>{{ item.remark }}</td>
                <td class="text-center">{{ item.campus }}</td>
                <td class="text-center">{{ item.division }}</td>
                <td class="text-center">{{ item.department }}</td>
                <td class="text-center">{{ item.qty }}</td>
                <td class="text-center">{{ item.uom }}</td>
                <td class="text-center">{{ item.price }}</td>
                <td class="text-center">{{ item.total_price }}</td>
                <td class="hidden-print">
                  <span :class="getItemStatusBadgeClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
              <tr class="print-only">
                <td colspan="10" class="text-end"><strong>Total Amount:</strong></td>
                <td><strong>${{ calculateTotalAmount().toFixed(2) }}</strong></td>
                <td class="hidden-print"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Invoice Note -->
      <!-- <div class="invoice-note">
        * Urgent: <span :class="form.is_urgent ? 'badge bg-primary' : 'badge bg-danger'">{{ form.is_urgent ? 'Yes' : 'No' }}</span>
      </div> -->

      <!-- Invoice Footer -->
      <div class="invoice-footer">
        <p class="text-center mb-5px fw-bold">
          THANK YOU FOR YOUR BUSINESS
        </p>
        <p class="text-center">
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-globe"></i> mjqeducation.edu.kh</span>
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-phone-volume"></i> T:096-3612146</span>
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-envelope"></i> <a href="mailto:vun.thy@mjqeducation.edu.kh">vun.thy@mjqeducation.edu.kh</a></span>
        </p>
      </div>
    </div>
  </Main>
</template>

<style scoped>
@media print {
  .hidden-print {
    display: none !important;
  }
  .print-only {
    display: table-row !important;
  }
  .invoice {
    width: 100%;
    margin: 0;
    padding: 0;
  }
  .invoice-header, .invoice-content, .invoice-footer {
    page-break-inside: avoid;
  }
  .invoice-header .col-md-6, .invoice-date .col-md-6 {
    width: 50%;
    float: left;
  }
  .invoice-header .text-end {
    text-align: right;
  }
  .invoice-header .text-start {
    text-align: left;
  }
  .table-invoice {
    border-collapse: collapse;
    width: 100%;
    font-size: 10px; /* Smaller font size */
  }
  .table-invoice th, .table-invoice td {
    border: 1px solid #000;
    padding: 8px;
  }
  .table-invoice tfoot td {
    border-top: 1px solid #000;
  }
}
</style>
