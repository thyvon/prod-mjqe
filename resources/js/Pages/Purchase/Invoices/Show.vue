<template>
  <Main>
    <Head :title="'Invoice Details'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Invoice Details</h4>
      </div>
      <div class="panel-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Invoice No:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ invoice.invoice_no }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Invoice Date:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ formatDate(invoice.invoice_date) }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Supplier:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ invoice.supplier.name }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Transaction Type:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ getTransactionType(invoice.transaction_type) }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Payment Type:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ getPaymentType(invoice.payment_type) }}</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Currency:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ invoice.currency === 1 ? 'USD' : 'KHR' }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Currency Rate:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ invoice.currency_rate }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Total Amount:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ formatCurrency(invoice.total_amount, invoice.currency) }}</p>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-4 col-form-label">Paid Amount:</label>
              <div class="col-sm-8">
                <p class="form-control-plaintext">{{ formatCurrency(invoice.paid_amount, invoice.currency) }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="invoice-items-table" class="table table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>PR Number</th>
                <th>PO Number</th>
                <th>Item Code</th>
                <th>Description</th>
                <th>Qty</th>
                <th>UOM</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Discount</th>
                <th>Service Charge</th>
                <th>VAT</th>
                <th>Grand Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in invoice.items" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ item.purchase_request?.pr_number || '' }}</td>
                <td>{{ item.purchase_order?.po_number || '' }}</td>
                <td>{{ item.product?.sku || '' }}</td>
                <td>{{ item.description }}</td>
                <td>{{ item.qty }}</td>
                <td>{{ item.uom }}</td>
                <td>{{ formatCurrency(item.unit_price, invoice.currency) }}</td>
                <td>{{ formatCurrency(item.total_price, invoice.currency) }}</td>
                <td>{{ formatCurrency(item.discount, invoice.currency) }}</td>
                <td>{{ formatCurrency(item.service_charge, invoice.currency) }}</td>
                <td>{{ formatCurrency(item.vat, invoice.currency) }}</td>
                <td>{{ formatCurrency(item.paid_amount, invoice.currency) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <h5>Attachments</h5>
            <div class="d-flex flex-wrap">
              <div v-for="attachment in invoice.attachments" :key="attachment.id" class="attachment-thumbnail position-relative me-3 mb-3">
                <img :src="getFileThumbnail(attachment.file_url)" @click="openPdfViewer(attachment.file_url)" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;" />
              </div>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <h5>Grand Total: {{ formatCurrency(grandTotal, invoice.currency) }}</h5>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn btn-secondary" @click="goBack">Back</button>
          <button class="btn btn-primary" @click="navigateToPrint">Print</button> <!-- Add Print button -->
        </div>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, Head, router } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { formatCurrency, formatDate, getTransactionType, getPaymentType, getFileThumbnail, openPdfViewer } from '@/Pages/Purchase/Invoices/helpers';

const { props } = usePage();
const invoice = ref(props.invoice);

console.log('Fetched invoice:', invoice.value); // Add logging to see the fetched invoice data

const goBack = () => {
  window.history.back();
};

const navigateToPrint = () => {
  router.visit(`/purchase/invoices/${invoice.value.id}/print`); // Update route to navigate to Print page
};

const initializeDataTable = (selector) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable({
    responsive: true,
    autoWidth: false,
    scrollX: false,
  });
};

const grandTotal = computed(() => {
  return invoice.value.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0).toFixed(2);
});

onMounted(() => {
  try {
    initializeDataTable('#invoice-items-table');
  } catch (error) {
    console.error('Error initializing DataTable:', error);
  }
});
</script>

<style scoped>
.attachment-thumbnail {
  position: relative;
}
</style>
