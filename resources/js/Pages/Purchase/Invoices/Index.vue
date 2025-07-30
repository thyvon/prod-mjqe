<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3'; // Added Link import
import Main from '@/Layouts/Main.vue';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import { formatDate, openPdfViewer } from '@/Pages/Purchase/Invoices/helpers.js';

toastr.options = {
  progressBar: true,
  closeButton: true,
  timeOut: 5000,
};

const props = defineProps({
  purchaseInvoices: Array,
});

const invoiceListTableInstance = ref(null);

const initializeDataTable = (selector, options) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable(options);
};

const deleteInvoice = async (invoiceId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this invoice!',
    icon: 'warning',
    buttons: {
      cancel: { text: 'No, cancel!', visible: true, className: 'btn btn-secondary', closeModal: true },
      confirm: { text: 'Yes, delete it!', visible: true, className: 'btn btn-danger', closeModal: true },
    },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/invoices/${invoiceId}`);
        toastr.success('Invoice deleted successfully.');
        const rowIndex = invoiceListTableInstance.value.row((idx, data) => data.id === invoiceId).index();
        invoiceListTableInstance.value.row(rowIndex).remove().draw();
      } catch (error) {
        toastr.error('Failed to delete invoice.');
        console.error('Error:', error);
      }
    }
  });
};

onMounted(() => {
  try {
    console.log('purchaseInvoices:', props.purchaseInvoices);
    const invoices = (props.purchaseInvoices || []).map(invoice => ({
      id: invoice.id,
      pi_number: invoice.pi_number || '',
      invoice_date: invoice.invoice_date || '',
      supplier_name: invoice.supplier ? invoice.supplier.name : '',
      paid_amount: invoice.paid_amount || 0,
      transaction_type: invoice.transaction_type || 0,
      cash_ref: invoice.cash_request ? invoice.cash_request.ref_no : '',
      payment_type: invoice.payment_type || 0,
      paid_usd: invoice.paid_usd || 0,
      currency: invoice.currency || 0,
      currency_rate: invoice.currency_rate || 0,
      status: invoice.status || 0,
      pdf_url: invoice.pdf_url || '',
    }));

    invoiceListTableInstance.value = initializeDataTable('#invoice-list-table', {
      responsive: true,
      autoWidth: true,
      data: invoices,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'pi_number' },
        { data: 'invoice_date', render: (data) => formatDate(data, 'date') },
        { data: 'supplier_name' },
        { data: 'paid_amount', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'currency', render: (data) => (data === 1 ? 'USD' : data === 2 ? 'KHR' : 'Unknown') },
        { data: 'currency_rate', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'paid_usd', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        {
          data: 'transaction_type',
          render: (data) => {
            const transactionTypes = {
              1: 'Petty Cash',
              2: 'Credit',
              3: 'Advance',
            };
            const badgeClasses = {
              1: 'badge bg-primary',
              2: 'badge bg-warning',
              3: 'badge bg-success',
            };
            return `<span class="${badgeClasses[data] || 'badge bg-secondary'}">${transactionTypes[data] || 'Unknown'}</span>`;
          },
        },
        { data: 'cash_ref' },
        { data: 'payment_type', render: (data) => (data === 1 ? 'Final' : data === 2 ? 'Deposit' : 'Unknown') },
        {
          data: 'status',
          render: (data) => (data === 1 ? '<span class="badge bg-success">Cleared</span>' : '<span class="badge bg-danger">Pending</span>'),
        },
        {
          data: null,
          render: (data) => `
            <div class="btn-group">
              <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item btn-edit ${data.status === 1 ? 'disabled' : ''}"><i class="fas fa-edit"></i> Edit</a></li>
                <li><a class="dropdown-item btn-delete ${data.status === 1 ? 'disabled' : ''}"><i class="fas fa-trash-alt"></i> Delete</a></li>
                <li><a class="dropdown-item btn-view-pdf"><i class="fas fa-file-pdf"></i> View PDF</a></li>
                <li><a class="dropdown-item btn-show"><i class="fas fa-eye"></i> Show</a></li>
              </ul>
            </div>
          `,
          className: 'text-center',
        },
      ],
    });

    $('#invoice-list-table')
      .on('click', '.btn-edit', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.id) {
          window.location.href = `/invoices/${rowData.id}/edit`;
        }
      })
      .on('click', '.btn-delete', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData) deleteInvoice(rowData.id);
      })
      .on('click', '.btn-view-pdf', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.pdf_url) {
          openPdfViewer(rowData.pdf_url);
        }
      })
      .on('click', '.btn-show', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.id) {
          window.location.href = `/invoices/${rowData.id}`;
        }
      });

    $('#invoice-list-table').on('click', '.dtr-details .btn-edit', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.id) {
        window.location.href = `/invoices/${rowData.id}/edit`;
      }
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-delete', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData) deleteInvoice(rowData.id);
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-view-pdf', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.pdf_url) {
        openPdfViewer(rowData.pdf_url);
      }
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-show', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.id) {
        window.location.href = `/invoices/${rowData.id}`;
      }
    });
  } catch (error) {
    console.error('Error during onMounted:', error);
    toastr.error('Failed to initialize invoice list.');
  }
});
</script>

<template>
  <Main>
    <Head title="Invoices" />
    <div class="panel panel-inverse border">
      <div class="panel-heading">
        <h4 class="panel-title">Invoice List</h4>
        <div class="panel-heading-btn">
          <Link href="/invoices/create" class="btn btn-primary btn-sm me-2">
            <i class="fas fa-plus"></i> Create Invoice
          </Link>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="invoice-list-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Invoice Ref</th>
                <th>Invoice Date</th>
                <th>Supplier</th>
                <th>Grand Total</th>
                <th>Currency</th>
                <th>Exchange Rate</th>
                <th>Paid USD</th>
                <th>Transaction Type</th>
                <th>Cash Ref</th>
                <th>Payment Type</th>
                <th>Payment Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </Main>
</template>

<style scoped>
</style>