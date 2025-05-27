<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import axios from 'axios';

toastr.options = {
  progressBar: true,
  closeButton: true,
  timeOut: 5000,
};

const props = defineProps({
  invoiceItems: Array,
  suppliers: Array,
});

const invoiceItemsTableInstance = ref(null);
const startDate = ref('');
const endDate = ref('');
const selectedSupplier = ref('');
const selectedTransactionType = ref('');

const initializeDataTable = (selector, options) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable(options);
};

const formatNumber = (value, decimalPlaces = 2) => {
  return isNaN(parseFloat(value)) ? '0.00' : parseFloat(value).toFixed(decimalPlaces);
};

const applyFilter = async () => {
  try {
    const response = await axios.post('/invoices/filter', {
      start_date: startDate.value,
      end_date: endDate.value,
      supplier_id: selectedSupplier.value,
      transaction_type: selectedTransactionType.value,
    });
    invoiceItemsTableInstance.value.clear().rows.add(response.data).draw();
  } catch (error) {
    console.error('Error applying filter:', error);
    toastr.error('Failed to apply filter.');
  }
};

const forceClose = async (id, isChecked) => {
  console.log('Force Close switch toggled for item ID:', id, 'Checked:', isChecked);
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to revert this!',
    icon: 'warning',
    buttons: true,
    dangerMode: true,
  }).then(async (willDelete) => {
    if (willDelete) {
      try {
        await axios.post(`/invoices/${id}/force-close`, { stop_purchase: isChecked ? 1 : 0 });
        toastr.success('Invoice item force closed successfully.');
        const item = props.invoiceItems.find(item => item.id === id);
        item.stop_purchase = isChecked ? 1 : 0;
        invoiceItemsTableInstance.value.row(item).invalidate().draw();
        swal('Force Closed!', 'Invoice item has been force closed.', 'success');
      } catch (error) {
        console.error('Error force closing invoice item:', error);
        toastr.error('Failed to force close the invoice item.');
        swal('Error!', 'Failed to force close the invoice item. Please try again.', 'error');
      }
    } else {
      // Revert the checkbox state if the user cancels the confirmation
      const item = props.invoiceItems.find(item => item.id === id);
      item.stop_purchase = !isChecked ? 1 : 0;
      invoiceItemsTableInstance.value.row(item).invalidate().draw();
    }
  });
};

onMounted(() => {
  invoiceItemsTableInstance.value = initializeDataTable('#invoice-items-table', {
    responsive: false,
    autoWidth: false,
    scrollX: false,
    select: true,
    data: props.invoiceItems,
    columns: [
      { data: null, render: (data, type, row, meta) => meta.row + 1 },
      { data: 'invoice_date', render: (data) => moment(data).format('MMM DD, YYYY') },
      { data: 'invoice_no' },
      { data: 'supplier.name' },
      { data: 'purchase_request.pr_number' },
      { data: 'purchase_order.po_number' },
      { data: 'invoice.pi_number' },
      { data: 'product.sku' },
      { data: null, render: (data) => `<div>${data.description}</div>` },
      { data: 'remark', render: (data) => `<div>${data}</div>` },
      { data: 'qty', render: (data) => formatNumber(data, 4) },
      { data: 'uom' },
      { data: 'unit_price', render: (data) => formatNumber(data, 4) },
      { data: null, render: (data) => formatNumber(data.qty * data.unit_price, 4) },
      { data: 'discount', render: (data) => formatNumber(data, 4) },
      { data: 'service_charge', render: (data) => formatNumber(data, 4) },
      { data: 'deposit', render: (data) => formatNumber(data, 4) },
      { data: 'vat', render: (data) => formatNumber(data, 4) },
      { data: 'return', render: (data) => formatNumber(data, 4) },
      { data: 'retention', render: (data) => formatNumber(data, 4) },
      // { data: 'due_amount', render: (data) => formatNumber(data, 4) },
      { data: 'paid_amount', render: (data) => formatNumber(data, 4) },
      { data: 'currency', render: (data) => data === 1 ? 'USD' : 'KHR' },
      { data: 'currency_rate', render: (data) => formatNumber(data, 4) },
      { data: 'total_usd', render: (data) => formatNumber(data, 4) },
      { data: 'campus' },
      { data: 'division' },
      { data: 'department' },
      { data: 'location' },
      { data: null, render: (data) => `<div>${data.purpose}</div>` },
      { data: null, render: (data) => `
        <div class="form-check form-switch">
          <input class="form-check-input btn-force-close" type="checkbox" data-id="${data.id}" ${data.stop_purchase ? 'checked disabled' : ''}>
        </div>
      ` },
    ],
  });

  $('#invoice-items-table').on('change', '.btn-force-close', function () {
    const itemId = $(this).data('id');
    const isChecked = $(this).is(':checked');
    forceClose(itemId, isChecked);
  });

  $("#default-daterange").daterangepicker({
    opens: "right",
    format: "MM/DD/YYYY",
    separator: " to ",
    startDate: moment().subtract(29, "days"),
    endDate: moment(),
    showDropdowns: true, // Enable month and year selection
  }, function (start, end) {
    startDate.value = start.format("YYYY-MM-DD");
    endDate.value = end.format("YYYY-MM-DD");
    $("#default-daterange input").val(start.format("MMM D, YYYY") + " - " + end.format("MMM D, YYYY"));
  });

  $('#supplier-select').select2({
    placeholder: 'Select Supplier',
    allowClear: true,
    width: '100%',
  }).on('change', function () {
    selectedSupplier.value = $(this).val();
  });

  $('#transaction-type-select').select2({
    placeholder: 'Select Transaction Type',
    allowClear: true,
    width: '100%',
  }).on('change', function () {
    selectedTransactionType.value = $(this).val();
  });
});
</script>

<template>
  <Main>
    <Head :title="'Invoice Items'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Supplier List</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="row mb-3">
          <div class="col-md-2">
            <div class="input-group" id="default-daterange">
              <input type="text" name="default-daterange" class="form-control" value="" placeholder="Date Range Filter" />
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
          <div class="col-md-2">
            <select id="supplier-select" class="form-control">
              <option value="">Select Supplier</option>
              <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select id="transaction-type-select" class="form-control">
              <option value="">Select Transaction Type</option>
              <option value="1">Petty Cash</option>
              <option value="2">Credit</option>
              <option value="3">Advance</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary" @click="applyFilter">Apply Filter</button>
          </div>
        </div>
        <div class="table-responsive">
          <table id="invoice-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Invoice Date</th>
                <th>Invoice No</th>
                <th>Supplier</th>
                <th>PR Number</th>
                <th>PO Number</th>
                <th>PI Number</th>
                <th>Item Code</th>
                <th style="min-width: 250px;">Description</th>
                <th>Remark</th>
                <th>Qty</th>
                <th>UOM</th>
                <th>Price</th>
                <th>Total Price</th>
                <th>Discount</th>
                <th>Service Charge</th>
                <th>Deposit</th>
                <th>VAT</th>
                <th>Return</th>
                <th>Retention</th>
                <th>Grand Total</th>
                <th>Currency</th>
                <th>Currency Rate</th>
                <th>Total USD</th>
                <th>Campus</th>
                <th>Division</th>
                <th>Department</th>
                <th>Location</th>
                <th style="width: 20%;">Purpose</th>
                <th>Force Close</th>
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
.description-column {
  min-width: 200px;
  white-space: normal;
  word-break: break-word;
  max-width: 300px;
}

.wrap-cell {
  white-space: normal;
  word-break: break-word;
}
</style>
