<script setup>
import { ref, reactive, onMounted, nextTick, watch, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define the props using defineProps()
const props = defineProps({
  clearInvoices: {
    type: Array,
    required: true,
  },
  cashRequests: {
    type: Array,
    required: true,
  },
  users: Array,
  currentUser: {
    type: Object,
    required: true,
  },
});

const purchaseInvoices = ref([]); // Ensure this is reactive
// Currency mapping
const currencyMap = {
  1: 'USD',
  2: 'KHR',
  // Add more currencies as needed
};

const statusMap = {
  1: 'Cleared',
  0: 'Pending',
};

const fetchPurchaseInvoices = async (cashRef) => {
  try {
    const response = await axios.get('/clear-invoices-invoice', {
      params: { cash_ref: cashRef },
    });
    purchaseInvoices.value = response.data; // Update the reactive array
  } catch (error) {
    console.error('Error fetching purchase invoices:', error);
    purchaseInvoices.value = []; // Clear the array on error
  }
};

const fetchPurchaseInvoicesEdit = async (cashRef) => {
  try {
    const response = await axios.get('/clear-invoices-invoice-edit', {
      params: { cash_ref: cashRef },
    });
    purchaseInvoices.value = response.data; // Update the reactive array
  } catch (error) {
    console.error('Error fetching purchase invoices:', error);
    purchaseInvoices.value = []; // Clear the array on error
  }
};

// Local state for clear invoices
const isEdit = ref(false);
const clearInvoiceForm = reactive({
  id: null,
  clear_type: '',
  clear_date: '',
  cash_id: null,
  clear_by: '',
  description: '',
  status: '',
  remark: '', // Added remark field
});

const validationErrors = ref({});
let dataTableInstance;

const filteredCashRequests = ref([]);

const filterCashRequests = () => {
  if (clearInvoiceForm.clear_type === '1') {
    filteredCashRequests.value = props.cashRequests.filter(request => request.request_type === 1);
  } else if (clearInvoiceForm.clear_type === '2') {
    filteredCashRequests.value = props.cashRequests.filter(request => request.request_type === 2);
  } else {
    filteredCashRequests.value = props.cashRequests;
  }
};

watch(() => clearInvoiceForm.clear_type, () => {
  // Call filterCashRequests as before
  filterCashRequests();

  // Disable/enable the "checked_by" field based on clear_type
  nextTick(() => {
    $('#checked_by').prop('disabled', clearInvoiceForm.clear_type == 1).trigger('change.select2');
    
    // Update the "cash_id" field after filtering
    $('#cash_id').val(clearInvoiceForm.cash_id).trigger('change');
  });
});


// Watch for changes in cash_id and fetch purchaseInvoice data
watch(() => clearInvoiceForm.cash_id, (newCashId) => {
  if (newCashId) {
    fetchPurchaseInvoices(newCashId);
    // fetchPurchaseInvoicesEdit(newCashId);
  }
});

// Functions
const openCreateModal = () => {
  isEdit.value = false;
  Object.assign(clearInvoiceForm, {
    id: null,
    clear_type: '',
    clear_date: new Date().toISOString().split('T')[0],
    cash_id: '',
    clear_by: props.currentUser.id, // Set clear_by to current user
    description: '',
    status: 'pending',
  });
  purchaseInvoices.value = [];
  filterCashRequests(); // Filter cash requests based on clear type
  const modalElement = document.getElementById('clearInvoiceModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
  });
};

const openEditModal = async (clearInvoice) => {
  isEdit.value = true;
  Object.assign(clearInvoiceForm, clearInvoice);
  try {
    // Fetch approval data for the clear invoice
    const response = await axios.get(`/clear-invoice/${clearInvoice.id}/approvals`);
    const approvals = response.data;

    // Map approvals to the corresponding fields
    clearInvoiceForm.checked_by = approvals.find(a => a.status_type === 1)?.user_id || null;
    clearInvoiceForm.approved_by = approvals.find(a => a.status_type === 3)?.user_id || null;

    // Fetch purchaseInvoice data based on cash_id
    await fetchPurchaseInvoicesEdit(clearInvoice.cash_id);
      watch(() => clearInvoiceForm.cash_id, (newCashId) => {
        if (newCashId) {
          fetchPurchaseInvoicesEdit(newCashId);
          // fetchPurchaseInvoicesEdit(newCashId);
        }
      });

  } catch (error) {
    console.error('Failed to fetch approval data:', error);
  }

  filterCashRequests(); // Filter cash requests based on clear type
  nextTick(() => {
    const modalElement = document.getElementById('clearInvoiceModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    initializeSelect2();

    // Set the values of Select2 components
    $('#cash_id').val(clearInvoiceForm.cash_id).trigger('change');
    $('#checked_by').val(clearInvoiceForm.checked_by).trigger('change');
    $('#approved_by').val(clearInvoiceForm.approved_by).trigger('change');
  });
};

const saveClearInvoice = async () => {
  try {
    if (isEdit.value) {
      const response = await axios.put(`/clear-invoice/${clearInvoiceForm.id}`, clearInvoiceForm);
      const updatedInvoice = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedInvoice.id).index();
      dataTableInstance.row(rowIndex).data(updatedInvoice).draw(false); // Redraw the table without resetting the paging
      swal('Success!', 'Clear invoice updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/clear-invoice', clearInvoiceForm);
      dataTableInstance.row.add(response.data).draw(false); // Redraw the table without resetting the paging
      swal('Success!', 'Clear invoice created successfully!', 'success', { timer: 2000 });
    }
    const modalElement = document.getElementById('clearInvoiceModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
    dataTableInstance.ajax.reload(null, false); // Reload the DataTable without resetting the paging
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
      if (validationErrors.value.cash_id) {
        swal('Error!', validationErrors.value.cash_id[0], 'error'); // Show alert for duplicate cash_id
      }
    } else {
      swal('Error!', 'Failed to save clear invoice. Please try again.', 'error');
    }
  }
};

const deleteClearInvoice = async (clearInvoiceId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this clear invoice!',
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
        await axios.delete(`/clear-invoice/${clearInvoiceId}`);
        dataTableInstance.row((idx, data) => data.id === clearInvoiceId).remove().draw();
        swal('Deleted!', 'Clear invoice has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete clear invoice. Please try again.', 'error');
      }
    }
  });
};

const approveClearInvoice = async (clearInvoiceId) => {
  swal({
    title: 'Are you sure?',
    text: 'Do you want to approve this clear invoice?',
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
        text: 'Yes, approve it!',
        value: true,
        visible: true,
        className: 'btn btn-success',
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        const response = await axios.put(`/clear-invoice/${clearInvoiceId}/approve`);
        const updatedInvoice = response.data;
        const rowIndex = dataTableInstance.row((idx, data) => data.id === clearInvoiceId).index();
        dataTableInstance.row(rowIndex).data(updatedInvoice).draw(false); // Redraw the table without resetting the paging
        swal('Success!', 'Clear invoice approved successfully!', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to approve clear invoice. Please try again.', 'error');
      }
    }
  });
};

const viewClearInvoice = (clearInvoiceId) => {
  window.location.href = `/clear-invoice/${clearInvoiceId}`;
};

const initializeSelect2 = () => {
  $('#clearInvoiceModal .select2').select2({
    dropdownParent: $('#clearInvoiceModal'),
    placeholder: 'Select an option', // 👈 required for allowClear to work
    allowClear: true,
    width: 'resolve', // 👈 optional but recommended inside modal
  }).on('change', function () {
    const field = $(this).attr('id');
    clearInvoiceForm[field] = $(this).val();
  });
};

const isCheckedByDisabled = computed(() => clearInvoiceForm.clear_type == 1);


// Helper function to format dates
const format = (value, type) => {
  if (type === 'date') {
    const options = { year: 'numeric', month: 'short', day: '2-digit' };
    const date = new Date(value);
    return date.toLocaleDateString('en-US', options);
  }
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#clear-invoice');
    if (table.length) {
      // console.log('Initializing DataTable');
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        ajax: {
          url: '/clear-invoices', // Ensure this URL returns the correct JSON structure
          dataSrc: ''
        },
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'ref_no' }, // Ensure 'ref_no' exists in clearInvoices data
          { data: 'cash_id', render: (data) => props.cashRequests.find(request => request.id === data)?.ref_no || 'Unknown' }, // Render cash request ref_no
          { data: 'description' },
          { data: 'clear_type', render: (data) => data === 1 ? 'Petty Cash' : 'Advance' }, // Render clear type
          { data: 'clear_by', render: (data) => props.users.find(user => user.id === data)?.name || 'Unknown' }, // Render user's name
          { 
            data: 'status',
            render: (data) => {
              if (data === 1) return '<span class="badge bg-primary">Checked</span>';
              if (data === 3) return '<span class="badge bg-success">Approved</span>';
              return '<span class="badge bg-warning">Pending</span>';
            },
          }, // Render status with badge
          { data: 'clear_date', render: (data) => format(data, 'date') },
          {
            data: null,
            render: (data) => `
              <div class="btn-group">
                <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> View</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners to the main table
      $('#clear-invoice')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditModal(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteClearInvoice(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            viewClearInvoice(rowData.id); // Navigate to the Show page
          }
        })
        .on('click', '.btn-approve', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) approveClearInvoice(rowData.id);
        });

      // Handle actions inside child rows (responsive details)
      $('#clear-invoice').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditModal(rowData);
      });

      $('#clear-invoice').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteClearInvoice(rowData.id);
      });

      $('#clear-invoice').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          viewClearInvoice(rowData.id);
        }
      });

      $('#clear-invoice').on('click', '.dtr-details .btn-approve', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) approveClearInvoice(rowData.id);
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Clear Invoice'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Clear Invoice</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <!-- Create New Clear Invoice Button -->
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Create New</button>

        <!-- Clear Invoice Table -->
        <div class="table-responsive">
          <table id="clear-invoice" class="table table-bordered table-sm align-middle text-wrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Ref No.</th>
                <th>Cash No.</th>
                <th>Description</th>
                <th>Type</th>
                <th>Clear By</th>
                <th>Status</th>
                <th>Clear Date</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>

        <!-- Modal for Create/Edit Clear Invoice -->
        <div class="modal fade" id="clearInvoiceModal" tabindex="-1" aria-labelledby="clearInvoiceModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="clearInvoiceModalLabel">{{ isEdit ? 'Edit Clear Invoice' : 'Create Clear Invoice' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveClearInvoice">
                  <div class="row">
                    <!-- Left side for clear invoice information -->
                    <div class="col-md-6">
                      <div class="mb-3 row">
                        <label for="clear_type" class="col-sm-4 col-form-label">Clear Type</label>
                        <div class="col-sm-8">
                          <select v-model="clearInvoiceForm.clear_type" class="form-select select2" id="clear_type" required>
                            <option value="1">Petty Cash</option>
                            <option value="2">Advance</option>
                          </select>
                          <div v-if="validationErrors.clear_type" class="text-danger">{{ validationErrors.clear_type[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="clear_date" class="col-sm-4 col-form-label">Clear Date</label>
                        <div class="col-sm-8">
                          <input v-model="clearInvoiceForm.clear_date" type="date" class="form-control" id="clear_date" required />
                          <div v-if="validationErrors.clear_date" class="text-danger">{{ validationErrors.clear_date[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="cash_id" class="col-sm-4 col-form-label">Cash Request</label>
                        <div class="col-sm-8">
                          <select v-model="clearInvoiceForm.cash_id" class="form-select select2" id="cash_id" required>
                            <option v-for="cashRequest in filteredCashRequests" :key="cashRequest.id" :value="cashRequest.id">{{ cashRequest.ref_no }}</option>
                          </select>
                          <div v-if="validationErrors.cash_id" class="text-danger">{{ validationErrors.cash_id[0] }}</div>
                        </div>
                      </div>
                      <!-- <div class="mb-3 row">
                        <label for="clear_by" class="col-sm-4 col-form-label">Clear By</label>
                        <div class="col-sm-8">
                          <input v-model="props.currentUser.name" type="text" class="form-control" id="clear_by" readonly />
                          <div v-if="validationErrors.clear_by" class="text-danger">{{ validationErrors.clear_by[0] }}</div>
                        </div>
                      </div> -->
                    </div>
                    <!-- Right side for other information -->
                    <div class="col-md-6">
                      <div class="mb-3 row">
                        <label for="description" class="col-sm-4 col-form-label">Description</label>
                        <div class="col-sm-8">
                          <textarea v-model="clearInvoiceForm.description" class="form-control" id="description"></textarea>
                          <div v-if="validationErrors.description" class="text-danger">{{ validationErrors.description[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="remark" class="col-sm-4 col-form-label">Remark</label>
                        <div class="col-sm-8">
                          <textarea v-model="clearInvoiceForm.remark" class="form-control" id="remark"></textarea>
                          <div v-if="validationErrors.remark" class="text-danger">{{ validationErrors.remark[0] }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- New Table for Purchase Invoices -->
                  <div class="row mt-4">
                    <div class="col-12">
                      <h5>Purchase Invoices</h5>
                      <table class="table table-bordered table-sm table-responsive">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>PI Number</th>
                            <th>Currency</th>
                            <th>Paid Amount</th>
                            <th>Purchaser</th>
                            <th>Payment Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- Loop through purchaseInvoices and display each invoice -->
                          <tr v-for="(invoice, index) in purchaseInvoices" :key="invoice.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ invoice.pi_number || 'N/A' }}</td> <!-- Handle missing data with fallback -->
                            <td>{{ currencyMap[invoice.currency] || 'Unknown' }}</td> <!-- Map currency ID to name -->
                            <td>{{ invoice.paid_amount ? parseFloat(invoice.paid_amount).toFixed(4) : '0.0000' }}</td> <!-- Format numbers to 4 decimal places -->
                            <td>{{ invoice.purchased_by?.name || 'N/A' }}</td> <!-- Handle missing data with fallback -->
                            <td>{{ statusMap[invoice.status] || 'Unknown' }}</td> 
                          </tr>
                          <!-- Show a message if no purchase invoices are found -->
                          <tr v-if="purchaseInvoices.length === 0">
                            <td colspan="4" class="text-center">No purchase invoices found.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <div class="col-6 border">
                      <div class="row">
                        <span class="text-center">Checked By</span>
                      </div>
                      <div class="col-sm-12">
                        <select v-model="clearInvoiceForm.checked_by" class="form-select select2" id="checked_by" :disabled="isCheckedByDisabled">
                          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <div v-if="validationErrors.checked_by" class="text-danger">{{ validationErrors.checked_by[0] }}</div>
                      </div>
                    </div>
                    <div class="col-6 border">
                      <div class="row">
                        <span class="text-center">Approved By</span>
                      </div>
                      <div class="col-sm-12">
                        <select v-model="clearInvoiceForm.approved_by" class="form-select select2" id="approved_by">
                          <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <div v-if="validationErrors.approved_by" class="text-danger">{{ validationErrors.approved_by[0] }}</div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update' : 'Create' }}</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Main>
</template>
