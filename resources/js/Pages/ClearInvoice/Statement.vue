<script setup>
import { ref, reactive,computed, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import swal from 'sweetalert';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define props to receive data from the backend
const props = defineProps({
  statements: {
    type: Array,
    required: true,
  },
  suppliers: {
    type: Array,
    required: true,
  },
  users: Array,
  currentUser: {
    type: Object,
    required: true,
  },
});

let dataTableInstance;

const statementForm = reactive({
  id: null,
  supplier_id: '',
  clear_date: '',
  description: '',
  remark: '',
  status: 0,
  invoices: [], // Add invoices to the statementForm
});

const totalPaidAmount = computed(() => {
  return statementForm.invoices.reduce((sum, invoice) => sum + parseFloat(invoice.paid_amount || 0), 0).toFixed(4);
});

const totalInvoices = computed(() => {
  return statementForm.invoices.length;
});

const isEdit = ref(false);

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  return new Date(dateString).toLocaleDateString('en-US', options);
};

const formatStatementDate = (dateString) => {
  const options = { year: 'numeric', month: 'short' };
  return new Date(dateString).toLocaleDateString('en-US', options).replace(' ', '-'); // Format as "Apr-2025"
};

const formatAmountWithCurrency = (amount, supplier) => {
  const currency = supplier?.currency === 1 ? 'USD' : supplier?.currency === 2 ? 'KHR' : 'Unknown';
  return `${parseFloat(amount).toFixed(4)} ${currency}`; // Format amount with 4 decimal places and append currency
};

const deleteStatement = async (statementId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this statement!',
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'No, cancel!',
        value: null,
        visible: true,
        className: 'btn btn-secondary',
        closeModal: true,
      },
      confirm: {
        text: 'Yes, delete it!',
        value: true,
        visible: true,
        className: 'btn btn-danger',
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/statements/${statementId}`);
        dataTableInstance.row((idx, data) => data.id === statementId).remove().draw();
        swal('Deleted!', 'Statement has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete statement. Please try again.', 'error');
      }
    }
  });
};

const initializeDataTable = () => {
  const table = $('#statements-table');
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy(); // Clear and destroy the existing DataTable instance
  }

  dataTableInstance = table.DataTable({
    responsive: true,
    autoWidth: true,
    data: props.statements, // Use props.statements for the table data
    columns: [
      { 
        data: null, 
        render: (data, type, row, meta) => meta.row + 1 // Dynamically calculate the sequence number
      },
      { data: 'clear_date', render: (data) => formatDate(data) }, // Keep the Clear Date column
      { data: 'statement_number' },
      { data: 'supplier.name', defaultContent: 'Unknown' }, // Ensure supplier.name is used
      { 
        data: 'clear_date', 
        render: (data) => formatStatementDate(data) // Add the Statement Date column
      },
      { 
        data: 'cleared_by.name', defaultContent: 'Unknown' // Display the user name for clear_by_id
      },
      { 
        data: null, 
        render: (data) => formatAmountWithCurrency(data.total_amount, data.supplier), // Format total_amount with currency
      },
      { data: 'total_invoices' },
      {
        data: 'status',
        render: (data) => {
          if (data === 1) return '<span class="badge bg-primary">Checked</span>';
          if (data === 3) return '<span class="badge bg-success">Approved</span>';
          return '<span class="badge bg-warning">Pending</span>';
        },
      },
      {
        data: null,
        render: (data) => `
          <div class="btn-group">
            <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
              <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item btn-view text-primary btn-sm"><i class="fas fa-eye"></i> View</a></li>
              <li><a class="dropdown-item btn-edit text-warning btn-sm"><i class="fas fa-edit"></i> Edit</a></li>
              <li><a class="dropdown-item btn-delete text-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</a></li>
            </ul>
          </div>
        `,
      },
    ],
    drawCallback: function () {
      // Recalculate the sequence number on redraw
      const api = this.api();
      api.rows().every(function (rowIdx) {
        const row = this.node();
        $(row).find('td:first').html(rowIdx + 1);
      });
    },
  });

  $('#statements-table').on('click', '.btn-view', function () {
    const rowData = dataTableInstance.row($(this).closest('tr')).data();
    if (rowData) {
      viewStatement(rowData.id);
    }
  });

  $('#statements-table').on('click', '.btn-edit', function () {
    const rowData = dataTableInstance.row($(this).closest('tr')).data();
    if (rowData) {
      openEditModal(rowData);
    }
  });

  $('#statements-table').on('click', '.btn-delete', function () {
    const rowData = dataTableInstance.row($(this).closest('tr')).data();
    if (rowData) {
      deleteStatement(rowData.id);
    }
  });
};

const initializeSelect2 = () => {
  // Initialize Select2 for supplier_id
  $('#supplier_id').select2({
    dropdownParent: $('#statementModal'),
    placeholder: 'Select a supplier',
    allowClear: true,
  }).on('change', function () {
    statementForm.supplier_id = $(this).val();
  });

  // Initialize Select2 for checked_by
  $('#checked_by').select2({
    dropdownParent: $('#statementModal'),
    placeholder: 'Select a user',
    allowClear: true,
  }).on('change', function () {
    statementForm.checked_by = $(this).val();
  });

  // Initialize Select2 for approved_by
  $('#approved_by').select2({
    dropdownParent: $('#statementModal'),
    placeholder: 'Select a user',
    allowClear: true,
  }).on('change', function () {
    statementForm.approved_by = $(this).val();
  });
};

const viewStatement = (statementId) => {
  window.location.href = `/statements/${statementId}`;
};

const openCreateModal = () => {
  isEdit.value = false;
  const today = new Date().toISOString().split('T')[0]; // Format as 'YYYY-MM-DD'
  Object.assign(statementForm, {
    id: null,
    supplier_id: '',
    clear_date: today,
    description: '',
    remark: '',
    status: 0,
    invoices: [], // Reset invoices
  });
  const modalElement = document.getElementById('statementModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
    initializeStatementInvoiceTable(); // Initialize the Statement Invoices table
  });
};

const openEditModal = async (statement) => {
  try {
    // Fetch both statement data and approvals data
    const [statementResponse, approvalsResponse] = await Promise.all([
      axios.get(`/statements/${statement.id}/edit`),
      axios.get(`/statements/${statement.id}/approvals`)
    ]);
    
    const data = statementResponse.data;
    const approvals = approvalsResponse.data;

    isEdit.value = true;
    Object.assign(statementForm, {
      id: data.id,
      supplier_id: data.supplier_id,
      clear_date: data.clear_date,
      description: data.description,
      remark: data.remark,
      status: data.status,
      // Map approvals to corresponding fields
      checked_by: approvals.find(a => a.status_type === 1)?.user_id || '',
      approved_by: approvals.find(a => a.status_type === 3)?.user_id || '',
      invoices: data.invoices.map((invoice) => ({
        id: invoice.invoice_id,
        invoice_no: invoice.purchase_invoice?.invoice_no || 'N/A',
        pi_number: invoice.purchase_invoice?.pi_number || 'N/A',
        invoice_date: invoice.purchase_invoice?.invoice_date || 'N/A',
        total_amount: invoice.purchase_invoice?.total_amount || 0,
        paid_amount: invoice.purchase_invoice?.paid_amount || 0,
        hasError: false,
      })),
    });

    const modalElement = document.getElementById('statementModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    nextTick(() => {
      initializeSelect2();
      // Update all Select2 fields with their values
      $('#supplier_id').val(statementForm.supplier_id).trigger('change');
      $('#checked_by').val(statementForm.checked_by).trigger('change');
      $('#approved_by').val(statementForm.approved_by).trigger('change');
      initializeStatementInvoiceTable();
    });
  } catch (error) {
    console.error('Failed to fetch statement data for editing:', error);
    swal('Error!', 'Failed to fetch statement data. Please try again.', 'error');
  }
};

const resetStatementForm = () => {
  Object.assign(statementForm, {
    id: null,
    supplier_id: '',
    clear_date: '',
    description: '',
    remark: '',
    status: 0,
    invoices: [], // Reset invoices
  });
};

const saveStatement = async () => {
  try {
    console.log('Saving statement:', statementForm); // Log the data being sent
    let response;
    if (isEdit.value) {
      response = await axios.put(`/statements/${statementForm.id}`, statementForm);
      const updatedStatement = response.data;

      // Update the row in the DataTable
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedStatement.id).index();
      dataTableInstance.row(rowIndex).data(updatedStatement).draw(false);

      swal('Success!', 'Statement updated successfully!', 'success', { timer: 2000 });
    } else {
      response = await axios.post('/statements', statementForm);
      const newStatement = response.data;

      // Add the new row to the DataTable
      dataTableInstance.row.add(newStatement).draw(false);

      swal('Success!', 'Statement created successfully!', 'success', { timer: 2000 });
    }

    const modalElement = document.getElementById('statementModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();

    resetStatementForm(); // Reset the form after saving

    // Reinitialize the statement-invoice-table
    initializeStatementInvoiceTable();
  } catch (error) {
    console.error('Failed to save statement:', error);

    if (error.response && error.response.data) {
      const errorMessage = error.response.data.error || 'Validation failed. Please check your input.';
      const existingInvoices = error.response.data.existing_invoices || [];
      let detailedMessage = errorMessage;

      if (existingInvoices.length > 0) {
        detailedMessage += `\nDuplicate Invoice Numbers: ${existingInvoices.join(', ')}`;
      }

      swal('Error!', detailedMessage, 'error');
    } else {
      swal('Error!', 'Failed to save statement. Please try again.', 'error');
    }
  }
};

const purchaseInvoices = ref([]);
const selectedSupplierId = ref(null);
const selectedClearDate = ref(null);

const fetchPurchaseInvoices = async () => {
  try {
    const response = await axios.get('/statements-purchase-invoices', {
      params: {
        supplier_id: selectedSupplierId.value,
        clear_date: selectedClearDate.value,
      },
    });
    console.log('Fetched purchase invoices:', response.data); // Debug log
    purchaseInvoices.value = response.data;
    initializePurchaseInvoiceTable();

    const modalElement = document.getElementById('purchaseInvoiceModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  } catch (error) {
    console.error('Failed to fetch purchase invoices:', error);
    if (error.response) {
      console.error('Error Response Data:', error.response.data);
      console.error('Error Response Status:', error.response.status);
      console.error('Error Response Headers:', error.response.headers);
    }
    swal('Error!', 'Failed to fetch purchase invoices. Please try again.', 'error');
  }
};

const initializePurchaseInvoiceTable = () => {
  console.log('Purchase Invoices:', purchaseInvoices.value); // Log the data
  const table = $('#purchase-invoice-table');
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy(); // Clear and destroy the existing DataTable instance
  }

  table.DataTable({
    responsive: true,
    autoWidth: true,
    data: purchaseInvoices.value,
    columns: [
      { 
        data: null, 
        title: '<input type="checkbox" id="select-all-invoices" />', 
        render: (data) => `<input type="checkbox" class="select-invoice" data-id="${data.id}" />` 
      },
      {
        data: null,
        title: '#',
        render: (data, type, row, meta) => meta.row + 1
      },
      { data: 'pi_number', title: 'PI Ref' },
      { data: 'invoice_no', title: 'Invoice No' },
      { data: 'invoice_date', title: 'Invoice Date' },
      { data: 'total_amount', title: 'Total Amount' },
      { data: 'paid_amount', title: 'Paid Amount' },
    ],
  });

  // Add event listener for the select-all checkbox
  $('#select-all-invoices').on('change', function () {
    const isChecked = $(this).is(':checked');
    $('.select-invoice').prop('checked', isChecked);
  });

  // Add event listener for bulk add button
  $('#bulk-add-invoices').on('click', function () {
    const selectedInvoices = [];
    $('.select-invoice:checked').each(function () {
      const invoiceId = $(this).data('id');
      const invoice = purchaseInvoices.value.find((inv) => inv.id === invoiceId);
      if (invoice) {
        selectedInvoices.push(invoice);
      }
    });

    const duplicates = [];
    selectedInvoices.forEach((invoice) => {
      const isDuplicate = statementForm.invoices.some((inv) => inv.id === invoice.id);
      if (!isDuplicate) {
        statementForm.invoices.push({
          id: invoice.id,
          invoice_no: invoice.invoice_no || 'N/A',
          pi_number: invoice.pi_number || 'N/A',
          invoice_date: invoice.invoice_date,
          total_amount: invoice.total_amount,
          paid_amount: invoice.paid_amount,
        });
      } else {
        duplicates.push(invoice.invoice_no || 'Unknown Invoice');
      }
    });

    if (duplicates.length > 0) {
      swal('Warning!', `The following invoices are already added: ${duplicates.join(', ')}`, 'warning');
    } else {
      swal('Success!', 'Selected invoices added to the statement.', 'success');
    }

    initializeStatementInvoiceTable(); // Refresh the statement invoice table
  });
};

const openPurchaseInvoiceModal = (supplierId, clearDate) => {
  if (!supplierId || !clearDate) {
    swal('Error!', 'Supplier or Clear Date is missing. Please check your input.', 'error');
    return;
  }

  // Log the supplierId and clearDate for debugging
  console.log('Opening Purchase Invoice Modal with:', { supplierId, clearDate });

  selectedSupplierId.value = supplierId;
  selectedClearDate.value = clearDate;

  fetchPurchaseInvoices(); // Call the function to fetch invoices
};

const initializeStatementInvoiceTable = () => {
  console.log('Statement Invoices:', statementForm.invoices); // Log the data
  const table = $('#statement-invoice-table');
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy(); // Clear and destroy the existing DataTable instance
  }

  table.DataTable({
    responsive: true,
    autoWidth: true,
    data: statementForm.invoices || [], // Use statementForm.invoices for the table data
    columns: [
      { 
        data: null, 
        title: '#', 
        render: (data, type, row, meta) => meta.row + 1 
      },
      { data: 'pi_number', title: 'PI Ref' },
      { data: 'invoice_no', title: 'Invoice No' },
      { data: 'invoice_date', title: 'Invoice Date' },
      { 
        data: 'total_amount', 
        title: 'Total Amount',
        render: (data) => parseFloat(data).toFixed(4) 
      },
      { 
        data: 'paid_amount', 
        title: 'Paid Amount',
        render: (data) => parseFloat(data).toFixed(4) 
      },
      {
        data: null,
        title: 'Actions',
        render: (data) => `
          <button class="btn btn-sm btn-danger btn-remove-invoice" data-id="${data.id}">
            <i class="fas fa-trash-alt"></i>
          </button>
        `,
      },
    ],
  });

  // Add event listener for the remove button
  $('#statement-invoice-table').on('click', '.btn-remove-invoice', function () {
    const invoiceId = $(this).data('id');
    const index = statementForm.invoices.findIndex((inv) => inv.id === invoiceId);
    if (index !== -1) {
      statementForm.invoices.splice(index, 1); // Remove the invoice from the array
      swal('Success!', 'Invoice removed from the statement.', 'success');
      initializeStatementInvoiceTable(); // Refresh the table
    }
  });
};

// Call initializeStatementInvoiceTable when the modal is shown
watch(() => statementForm.invoices, () => {
  nextTick(() => {
    initializeStatementInvoiceTable();
  });
});

watch(
  () => [statementForm.supplier_id, statementForm.clear_date],
  async ([supplierId, clearDate]) => {
    if (isEdit.value) return; // Prevent execution when in Edit mode
    if (supplierId && clearDate) {
      try {
        const response = await axios.get('/statements-purchase-invoices', {
          params: { supplier_id: supplierId, clear_date: clearDate },
        });
        statementForm.invoices = response.data.map((invoice) => ({
          id: invoice.id,
          invoice_no: invoice.invoice_no,
          pi_number: invoice.pi_number,
          invoice_date: invoice.invoice_date,
          total_amount: invoice.total_amount,
          paid_amount: invoice.paid_amount,
        }));
        initializeStatementInvoiceTable(); // Refresh the table with the fetched invoices
      } catch (error) {
        console.error('Failed to fetch invoices:', error);
        swal('Error!', 'Failed to fetch invoices. Please try again.', 'error');
      }
    }
  }
);

onMounted(() => {
  initializeDataTable(); // Initialize the DataTable on mount

  // Initialize the date picker for clear_date
  $("#datepicker-autoClose").datepicker({
    todayHighlight: true,
    autoclose: true,
  }).on("changeDate", function (e) {
    statementForm.clear_date = e.format("yyyy-mm-dd"); // Update the reactive property
  });
});
</script>

<template>
  <Main>
    <Head :title="'Statements'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Statements</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      
      <div class="panel-body">
        <!-- Create New Button -->
        <div class="mb-3">
          <button @click="openCreateModal" class="btn btn-primary btn-sm">Create New</button>
        </div>

        <!-- Responsive Table Wrapper -->
        <div class="table-responsive">
          <table id="statements-table" class="table table-bordered table-sm align-middle text-wrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Clear Date</th> <!-- Keep the Clear Date column -->
                <th>Statement Number</th>
                <th>Supplier</th>
                <th>Statement Date</th> <!-- Add the new column header -->
                <th>Cleared By</th> <!-- Add the new column header for clear_by_id -->
                <th>Total Amount</th>
                <th>Total Invoices</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <!-- Removed the <tbody> section -->
          </table>
        </div>
      </div>
    </div>

    <!-- Modal for Create/Edit Statement -->
    <div class="modal fade" id="statementModal" tabindex="-1" aria-labelledby="statementModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="statementModalLabel">{{ isEdit ? 'Edit Statement' : 'Create Statement' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveStatement">
              <div class="col-12">
                <div class="row">
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="supplier_id" class="form-label">Supplier</label>
                      <select v-model="statementForm.supplier_id" class="form-select select2" id="supplier_id" style="width: 100%;" required>
                        <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                          {{ supplier.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="clear_date" class="form-label">Clear Date</label>
                      <input
                        v-model="statementForm.clear_date"
                        type="text"
                        class="form-control"
                        id="datepicker-autoClose"
                        placeholder="Select a date"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea v-model="statementForm.description" class="form-control" id="description"></textarea>
              </div>
              <div class="mb-3">
                <label for="remark" class="form-label">Remarks</label>
                <textarea v-model="statementForm.remark" class="form-control" id="remark"></textarea>
              </div>
              <!-- Add DataTable for statement_invoice -->
              <div class="mt-4">
                <h5>Statement Invoices</h5>
                <table id="statement-invoice-table" class="table table-bordered border-secondary table-sm align-middle text-wrap" width="100%">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>PI Ref</th>
                      <th>Invoice No</th>
                      <th>Invoice Date</th>
                      <th>Total Amount</th>
                      <th>Paid Amount</th>
                      <th>Actions</th> <!-- Removed the "Status" column -->
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Data will be dynamically populated -->
                  </tbody>
                </table>
                <!-- filepath: c:\xampp\htdocs\prod-mjqe\resources\js\Pages\ClearInvoice\Statement.vue -->
                <div class="mt-2 text-end">
                  <strong>Total Invoices: </strong> {{ totalInvoices }}
                </div>
                <div class="mt-2 text-end">
                  <strong>Total Paid Amount: </strong> {{ totalPaidAmount }}
                </div>
              </div>

              <div class="row">
                <div class="col-6">
                  <div class="mb-3">
                    <label for="checked_by" class="form-label">Checked By</label>
                    <select v-model="statementForm.checked_by" class="form-select select2" id="checked_by" style="width: 100%;">
                      <option value="">Select User</option>
                      <option v-for="user in props.users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="col-6">
                  <div class="mb-3">
                    <label for="approved_by" class="form-label">Approved By</label>
                    <select v-model="statementForm.approved_by" class="form-select select2" id="approved_by" style="width: 100%;">
                      <option value="">Select User</option>
                      <option v-for="user in props.users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update' : 'Save' }}</button>
                <button
                  type="button"
                  class="btn btn-info btn-sm"
                  @click="openPurchaseInvoiceModal(statementForm.supplier_id, statementForm.clear_date)"
                >
                  Add More Invoices
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Purchase Invoices -->
    <div class="modal fade bg-secondary" id="purchaseInvoiceModal" tabindex="-1" aria-labelledby="purchaseInvoiceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="purchaseInvoiceModalLabel">Purchase Invoices</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table id="purchase-invoice-table" class="table table-bordered border-secondary table-sm align-middle text-wrap" width="100%"></table>
            <button id="bulk-add-invoices" class="btn btn-primary btn-sm mt-3">Add Selected Invoices</button>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </Main>
</template>
