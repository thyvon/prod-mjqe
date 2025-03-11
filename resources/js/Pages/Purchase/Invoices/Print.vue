<template>
  <div class="container mt-5">
    <div class="row">
      <div class="col-12 text-center">
        <h1>Invoice Details</h1>
      </div>
    </div>
    <div class="row mt-4">
      <div class="col-md-6">
        <p><strong>Invoice No:</strong> {{ invoice.invoice_no }}</p>
        <p><strong>Invoice Date:</strong> {{ formatDate(invoice.invoice_date) }}</p>
        <p><strong>Supplier:</strong> {{ invoice.supplier.name }}</p>
        <p><strong>Transaction Type:</strong> {{ getTransactionType(invoice.transaction_type) }}</p>
        <p><strong>Payment Type:</strong> {{ getPaymentType(invoice.payment_type) }}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Currency:</strong> {{ invoice.currency === 1 ? 'USD' : 'KHR' }}</p>
        <p><strong>Currency Rate:</strong> {{ invoice.currency_rate }}</p>
        <p><strong>Total Amount:</strong> {{ formatCurrency(invoice.total_amount, invoice.currency) }}</p>
        <p><strong>Paid Amount:</strong> {{ formatCurrency(invoice.paid_amount, invoice.currency) }}</p>
      </div>
    </div>
    <div class="table-responsive mt-4">
      <table class="table table-bordered">
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
            <td>{{ item.vat }}%</td>
            <td>{{ formatCurrency(item.paid_amount, invoice.currency) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatCurrency, formatDate, getTransactionType, getPaymentType } from '@/Pages/Purchase/Invoices/helpers';

const { props } = usePage();
const invoice = ref(props.invoice);
</script>

<style scoped>
.container {
  font-family: Arial, sans-serif;
}

.table-responsive {
  margin-top: 20px;
}
</style>
