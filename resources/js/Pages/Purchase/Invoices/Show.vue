<template>
  <Main>
    <Head :title="'Invoice Details'" />
    <div class="mt-3">
      <button class="btn btn-secondary" @click="goBack">Back</button>
      <!-- <button class="btn btn-primary" @click="navigateToPrint">Print</button> -->
    </div>
    <div class="panel panel-inverse">
      <div class="panel-body">
        <div class="row mb-1">
            <div class="col-3">
              <a class="d-block text-start" href="#!">
                <img src="/images/Supplier-logo.png" class="img-fluid" alt="BootstrapBrain Logo" width="70" height="30">
              </a>
              <div class="row mt-2">
                <div class="col-12">{{ invoice.supplier?.address }}</div>
              </div>
            </div>
            <div class="col-6 pt-3">
              <div class="row font-monospace">
                <h3 class="text-uppercase text-center fw-bold" style="font-family: 'TW Cen MT';">INVOICE</h3>
                <h5 class="text-uppercase text-center fw-bold"style="font-family: 'TW Cen MT';">{{ invoice.supplier.name }}</h5>
                <p class="text-center"style="font-family: 'TW Cen MT';">{{ invoice.supplier?.number }}</p>
              </div>
            </div>
            <div class="col-3">
              <div class="row">
                <div class="col-6 text-end">Ref: </div>
                <div class="col-6">
                  {{ invoice.pi_number }}
                  <span class="badge bg-primary">
                    {{ invoice.payment_type == 1 ? 'Final' : invoice.payment_type == 2 ? 'Deposit' : invoice.payment_type }}
                  </span>
                </div>
              </div>
              <div class="row">
                <div class="col-6 text-end">No: </div>
                <div class="col-6">{{ invoice.invoice_no }}</div>
              </div>
              <div class="row">
                <div class="col-6 text-end">Date: </div>
                <div class="col-6">{{ formatDate(invoice.invoice_date) }}</div>
              </div>
              <div class="row">
                <div class="col-6 text-end">Purchaser: </div>
                <div class="col-6">{{ invoice.purchased_by?.name }}</div>
              </div>
              <div class="row">
                <div class="col-6 text-end">Payment Term: </div>
                <div class="col-6">{{ getPaymentTerm(invoice.payment_term) }}</div>
              </div>
            </div>
          </div>
        <div class="row">
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table id="invoice-items-table" class="table table-bordered mb-0">
              <thead class="table-light sticky-top bg-white">
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
                  <th>Delivery</th>
                  <th>Return</th>
                  <th>Retention</th>
                  <th>VAT</th>
                  <th>Deposit</th>
                  <th>Grand Total</th>
                  <th v-if="showRoundingColumn">Rounding</th>
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
                  <td>{{ formatCurrency(item.return, invoice.currency) }}</td>
                  <td>{{ formatCurrency(item.retention, invoice.currency) }}</td>
                  <td>{{ formatCurrency(item.vat, invoice.currency) }}</td>
                  <td>{{ formatCurrency(item.deposit, invoice.currency) }}</td>
                  <td>{{ formatCurrency(item.paid_amount, invoice.currency) }}</td>
                  <td v-if="showRoundingColumn">
                    <span v-if="item.rounding_method || (item.rounding_digits && item.rounding_digits > 0)">
                      <span v-if="item.rounding_method">{{ item.rounding_method.toUpperCase() }}</span>
                      <span v-if="item.rounding_digits && item.rounding_digits > 0">
                        <template v-if="item.rounding_method">, </template>{{ item.rounding_digits }}
                      </span>
                    </span>
                  </td>
                </tr>
                <tr class="no-datatable fw-bold">
                  <td colspan="8" class="text-end"><strong>TOTAL</strong></td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.total_price) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.service_charge) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.return) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.retention) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.vat) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.deposit) || 0), 0), invoice.currency) }}</td>
                  <td>{{ formatCurrency(invoice.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0), invoice.currency) }}</td>
                   <th v-if="showRoundingColumn"></th>
                </tr>
              </tbody>
            </table>
          </div>
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
        </div>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, Head, router } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { formatCurrency, formatDate, getTransactionType, getPaymentType, getFileThumbnail, openPdfViewer,getPaymentTerm } from '@/Pages/Purchase/Invoices/helpers';

const { props } = usePage();
const invoice = ref(props.invoice);

const showRoundingColumn = computed(() =>
  invoice.value.items.some(
    item =>
      (item.rounding_method && item.rounding_method !== '') ||
      (item.rounding_digits && item.rounding_digits > 0)
  )
);

console.log('Fetched invoice:', invoice.value); // Add logging to see the fetched invoice data

const goBack = () => {
  window.history.back();
};

// const navigateToPrint = () => {
//   router.visit(`/purchase/invoices/${invoice.value.id}/print`); // Update route to navigate to Print page
// };

// const initializeDataTable = (selector) => {
//   const table = $(selector);
//   if ($.fn.DataTable.isDataTable(table)) {
//     table.DataTable().clear().destroy();
//   }
//   return table.DataTable({
//     responsive: true,
//     autoWidth: false,
//     scrollX: false,
//   });
// };

const grandTotal = computed(() => {
  return invoice.value.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0).toFixed(2);
});

// onMounted(() => {
//   try {
//     initializeDataTable('#invoice-items-table');
//   } catch (error) {
//     console.error('Error initializing DataTable:', error);
//   }
// });
</script>

<style scoped>
.attachment-thumbnail {
  position: relative;
}
</style>
