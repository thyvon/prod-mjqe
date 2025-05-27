<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define the props using defineProps()
const props = defineProps({
  cashRequests: {
    type: Array,
    required: true,
  },
  users: Array,
  currentUser: Object,
});

// Local state for cash requests
const isEdit = ref(false);
const cashRequestForm = reactive({
  id: null,
  request_type: null, // Changed to integer
  request_date: '',
  user_id: null,
  request_by: '',
  position: '',
  id_card: '',
  campus: '',
  division: '',
  department: '',
  description: '',
  currency: '', // Ensure currency is initialized
  exchange_rate: '',
  amount: '',
  via: '',
  reason: '',
  remark: '',
  checked_by: null,
  acknowledged_by: null,
  approved_by: null,
  received_by: null,
});

const validationErrors = ref({});
let dataTableInstance;

// Functions
const openCreateModal = () => {
  isEdit.value = false;
  const currentUser = props.currentUser || {};
  Object.assign(cashRequestForm, {
    id: null,
    request_type: null,
    request_date: new Date().toISOString().split('T')[0],
    user_id: currentUser.id || '',
    request_by: currentUser.name || '',
    position: currentUser.position || '',
    id_card: currentUser.card_id || '',
    campus: currentUser.campus || '',
    division: currentUser.division || '',
    department: currentUser.department || '',
    description: '',
    currency: '',
    exchange_rate: '',
    amount: '',
    via: '',
    reason: '',
    remark: '',
    checked_by: null, // Clear checked_by
    acknowledged_by: null, // Clear acknowledged_by
    approved_by: null, // Clear approved_by
    received_by: currentUser.id || '', // Set received_by to the same as the Requester
  });
  const modalElement = document.getElementById('cashRequestModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
  });
};

const openEditModal = async (cashRequest) => {
  isEdit.value = true;
  Object.assign(cashRequestForm, cashRequest);
  
  try {
    // Fetch approval data for the cash request
    const response = await axios.get(`/cash-request/${cashRequest.id}/approvals`);
    const approvals = response.data;

    // Map approvals to the corresponding fields
    cashRequestForm.checked_by = approvals.find(a => a.status_type === 1)?.user_id || null;
    cashRequestForm.acknowledged_by = approvals.find(a => a.status_type === 2)?.user_id || null;
    cashRequestForm.approved_by = approvals.find(a => a.status_type === 3)?.user_id || null;
    cashRequestForm.received_by = approvals.find(a => a.status_type === 4)?.user_id || null;
  } catch (error) {
    console.error('Failed to fetch approval data:', error);
  }

  const modalElement = document.getElementById('cashRequestModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
  });
};

const saveCashRequest = async () => {
  const requestTypeText = cashRequestForm.request_type == 1 ? 'Petty Cash' : 'Cash Advance';
  const confirmResult = await swal({
    title: 'Confirm Save',
    text: `Are you sure you want to save this ${requestTypeText} request?`,
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
        text: 'Yes, save it!',
        value: true,
        visible: true,
        className: 'btn btn-primary',
        closeModal: true,
      },
    },
    dangerMode: true,
  });

  if (!confirmResult) {
    return;
  }

  try {
    if (isEdit.value) {
      const response = await axios.put(`/cash-request/${cashRequestForm.id}`, cashRequestForm); // Ensure currency is sent
      const updatedRequest = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedRequest.id).index();
      dataTableInstance.row(rowIndex).data(updatedRequest).draw();
      swal('Success!', 'Cash request updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/cash-request', cashRequestForm); // Ensure currency is sent
      dataTableInstance.row.add(response.data).draw();
      swal('Success!', 'Cash request created successfully!', 'success', { timer: 2000 });
    }
    // Clear specific fields after save
    cashRequestForm.checked_by = null;
    cashRequestForm.acknowledged_by = null;
    cashRequestForm.approved_by = null;
    cashRequestForm.received_by = null;

    const modalElement = document.getElementById('cashRequestModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save cash request. Please try again.', 'error');
    }
  }
};

const deleteCashRequest = async (cashRequestId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this cash request!',
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
        await axios.delete(`/cash-request/${cashRequestId}`);
        dataTableInstance.row((idx, data) => data.id === cashRequestId).remove().draw();
        swal('Deleted!', 'Cash request has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        if (error.response && error.response.status === 400) {
          swal('Error!', error.response.data.message, 'error');
        } else {
          swal('Error!', 'Failed to delete cash request. Please try again.', 'error');
        }
      }
    }
  });
};

const clearForm = () => {
  Object.assign(cashRequestForm, {
    id: null,
    request_type: null, // Changed to integer
    request_date: new Date().toISOString().split('T')[0], // Ensure date format is "yyyy-MM-dd"
    user_id: props.currentUser ? props.currentUser.id : '',
    request_by: props.currentUser ? props.currentUser.name : '',
    position: '',
    id_card: '',
    campus: '',
    division: '',
    department: '',
    description: '',
    currency: '',
    exchange_rate: '',
    amount: '',
    via: '',
    reason: '',
    remark: '',
  });
  validationErrors.value = {};
};

const initializeSelect2 = () => {
  $('#cashRequestModal .select2').select2({
    dropdownParent: $('#cashRequestModal'),
    placeholder: 'Select an option', // 👈 must set placeholder
    allowClear: true,
    width: 'resolve', // optional, but helps Select2 fit better
  }).on('change', function () {
    const field = $(this).attr('id');
    cashRequestForm[field] = $(this).val();
  });
};

const isAcknowledgedByDisabled = computed(() => cashRequestForm.request_type == 1);

watch(() => cashRequestForm.request_type, () => {
  // Disable/enable the "checked_by" field based on clear_type
  nextTick(() => {
    $('#acknowledged_by').prop('disabled', cashRequestForm.request_type == 1).trigger('change.select2');
  });
});

// Function to clear select2 fields
const clearSelect2 = () => {
  $('#cashRequestModal .select2').val(null).trigger('change');
  cashRequestForm.reason = '';
  cashRequestForm.checked_by = null;
  cashRequestForm.acknowledged_by = null;
  cashRequestForm.approved_by = null;
  cashRequestForm.received_by = null;
};

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
    const table = $('#cash-request');
    if (table.length) {
      console.log('Initializing DataTable');
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: props.cashRequests,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'ref_no' },
          { data: 'description' },
          {
            data: 'request_type',
            render: (data) => {
              if (data == 1) return '<span class="badge bg-primary">Petty Cash</span>';
              if (data == 2) return '<span class="badge bg-success">Cash Advance</span>';
              return ''; // Default case to handle unexpected values
            }
          },
          { data: 'request_by' },
          { data: 'campus' },
          {
            data: 'currency',
            render: (data) => {
              if (data == 1) return '<span class="badge bg-primary">USD</span>';
              if (data == 2) return '<span class="badge bg-success">KHR</span>';
              return ''; // Default case to handle unexpected values
            }
          },
          { data: 'amount' },
          { data: 'remark' },
          { data: 'request_date', render: (data) => format(data, 'date') },
          {
            data: 'approval_status',
            render: (data) => {
              if (data == 1) return '<span class="badge bg-primary">Checked</span>';
              if (data == 2) return '<span class="badge bg-warning">Acknowledged</span>';
              if (data == 3) return '<span class="badge bg-primary">Approved</span>';
              if (data == 4) return '<span class="badge bg-success">Received</span>';
              if (data == -1) return '<span class="badge bg-danger">Rejected</span>';
              return '<span class="badge bg-secondary">Requested</span>'; // Default case
            },
            defaultContent: '<span class="badge bg-secondary">Requested</span>', // Handle missing approval_status
          },
          {
            data: 'status',
            render: (data) => {
              if (data == 0) return '<span class="badge bg-warning">Pending</span>';
              if (data == 1) return '<span class="badge bg-success">Done</span>';
              return '<span class="badge bg-warning">Pending</span>'; // Default case
            },
            defaultContent: '<span class="badge bg-warning">Pending</span>', // Handle missing approval_status
          },
          {
            data: null,
            render: () => `
              <div class="btn-group">
                <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
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
      $('#cash-request')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditModal(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteCashRequest(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/cash-request/${rowData.id}`;
          }
        });

      // Handle actions inside child rows (responsive details)
      $('#cash-request').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditModal(rowData);
      });

      $('#cash-request').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteCashRequest(rowData.id);
      });

      $('#cash-request').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          window.location.href = `/cash-request/${rowData.id}`;
        }
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Cash Request'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Cash Request</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <!-- Create New Cash Request Button -->
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Create New</button>

        <!-- Cash Request Table -->
        <div class="table-responsive">
          <table id="cash-request" class="table table-bordered table-sm align-middle" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Ref No.</th>
              <th>Description</th>
              <th>Type</th>
              <th>Request By</th>
              <th>Campus</th>
              <th>Currency</th>
              <th>Amount</th>
              <th>Remark</th>
              <th>Request Date</th>
              <th>Approval Status</th>
              <th>Clear Payment</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
        </div>

        <!-- Modal for Create/Edit Cash Request -->
        <div class="modal fade" id="cashRequestModal" tabindex="-1" aria-labelledby="cashRequestModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="cashRequestModalLabel">{{ isEdit ? 'Edit Cash Request' : 'Create Cash Request' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveCashRequest">
                  <div class="row">
                    <!-- Left side for requester information -->
                    <div class="col-md-6">
                      <div class="mb-3 row">
                        <label for="user_id" class="col-sm-4 col-form-label">Requester</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.request_by" type="text" class="form-control" id="user_id" readonly />
                          <div v-if="validationErrors.user_id" class="text-danger">{{ validationErrors.user_id[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="position" class="col-sm-4 col-form-label">Position</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.position" type="text" class="form-control" id="position" required readonly />
                          <div v-if="validationErrors.position" class="text-danger">{{ validationErrors.position[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="id_card" class="col-sm-4 col-form-label">ID Card</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.id_card" type="text" class="form-control" id="id_card" required readonly />
                          <div v-if="validationErrors.id_card" class="text-danger">{{ validationErrors.id_card[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="campus" class="col-sm-4 col-form-label">Campus</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.campus" type="text" class="form-control" id="campus" required readonly />
                          <div v-if="validationErrors.campus" class="text-danger">{{ validationErrors.campus[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="division" class="col-sm-4 col-form-label">Division</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.division" type="text" class="form-control" id="division" required readonly />
                          <div v-if="validationErrors.division" class="text-danger">{{ validationErrors.division[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="department" class="col-sm-4 col-form-label">Department</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.department" type="text" class="form-control" id="department" required readonly />
                          <div v-if="validationErrors.department" class="text-danger">{{ validationErrors.department[0] }}</div>
                        </div>
                      </div>
                    </div>
                    <!-- Right side for other information -->
                    <div class="col-md-6">
                      <div class="mb-3 row">
                        <label for="request_type" class="col-sm-4 col-form-label">Request Type</label>
                        <div class="col-sm-8">
                          <select v-model="cashRequestForm.request_type" class="form-select select2" id="request_type" :disabled="isEdit" required>
                            <option value="1">Petty Cash</option>
                            <option value="2">Cash Advance</option>
                          </select>
                          <div v-if="validationErrors.request_type" class="text-danger">{{ validationErrors.request_type[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="request_date" class="col-sm-4 col-form-label">Request Date</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.request_date" type="date" class="form-control" id="request_date" required />
                          <div v-if="validationErrors.request_date" class="text-danger">{{ validationErrors.request_date[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                        <div class="col-sm-8">
                          <select v-model="cashRequestForm.currency" class="form-select select2" id="currency" required>
                            <option value="1">USD</option>
                            <option value="2">KHR</option>
                          </select>
                          <div v-if="validationErrors.currency" class="text-danger">{{ validationErrors.currency[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="exchange_rate" class="col-sm-4 col-form-label">Exchange Rate</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.exchange_rate" type="number" step="0.01" class="form-control" id="exchange_rate" required />
                          <div v-if="validationErrors.exchange_rate" class="text-danger">{{ validationErrors.exchange_rate[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="amount" class="col-sm-4 col-form-label">Amount</label>
                        <div class="col-sm-8">
                          <input v-model="cashRequestForm.amount" type="number" step="0.01" class="form-control" id="amount" required />
                          <div v-if="validationErrors.amount" class="text-danger">{{ validationErrors.amount[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="via" class="col-sm-4 col-form-label">Payment Method</label>
                        <div class="col-sm-8">
                          <select v-model="cashRequestForm.via" class="form-select select2" id="via" required>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                          </select>
                          <div v-if="validationErrors.via" class="text-danger">{{ validationErrors.via[0] }}</div>
                        </div>
                      </div>
                    </div>
                    <!-- Middle section for description, reason, and remark -->
                  </div>
                  <div class="row mb-2">
                    <div class="col-12">
                      <div class="mb-3 row">
                        <label for="description" class="col-sm-2 col-form-label">Description/Reason</label>
                        <div class="col-sm-10">
                          <textarea v-model="cashRequestForm.description" class="form-control" id="description"></textarea>
                          <div v-if="validationErrors.description" class="text-danger">{{ validationErrors.description[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="reason" class="col-sm-2 col-form-label">Request For</label>
                        <div class="col-sm-10">
                          <select v-model="cashRequestForm.reason" class="form-select select2" id="reason">
                            <option value="ប្រាក់បៀវត្យគ្រូ/Teacher's Salary">ប្រាក់បៀវត្យគ្រូ/Teacher's Salary</option>
                            <option value="ប្រាក់បៀវត្យបុគ្គលិក/Staff's Salary">ប្រាក់បៀវត្យបុគ្គលិក/Staff's Salary</option>
                            <option value="ទិញសម្ភារៈ/Purchase Request">ទិញសម្ភារៈ/Purchase Request</option>
                            <option value="Other">ផ្សេងៗ/Others</option>
                          </select>
                          <div v-if="validationErrors.reason" class="text-danger">{{ validationErrors.reason[0] }}</div>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="remark" class="col-sm-2 col-form-label">Remark</label>
                        <div class="col-sm-10">
                          <textarea v-model="cashRequestForm.remark" class="form-control" id="remark"></textarea>
                          <div v-if="validationErrors.remark" class="text-danger">{{ validationErrors.remark[0] }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-3 border">
                      <div class="row">
                        <span class="text-center">Checked By</span>
                      </div>
                      <div class="col-sm-12">
                        <select v-model="cashRequestForm.checked_by" class="form-select select2" id="checked_by">
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                          </select>
                          <div v-if="validationErrors.checked_by" class="text-danger">{{ validationErrors.checked_by[0] }}</div>
                      </div>
                    </div>

                      <div class="col-3 border">
                        <div class="row">
                          <span class="text-center">Acknowledged By</span>
                        </div>
                        <div class="col-sm-12">
                          <select v-model="cashRequestForm.acknowledged_by" class="form-select select2" id="acknowledged_by" :disabled="isAcknowledgedByDisabled">
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                          </select>
                          <div v-if="validationErrors.acknowledged_by" class="text-danger">{{ validationErrors.acknowledged_by[0] }}</div>
                        </div>
                      </div>
                      <div class="col-3 border">
                        <div class="row">
                          <span class="text-center">Approved By</span>
                        </div>
                        <div class="col-sm-12">
                          <select v-model="cashRequestForm.approved_by" class="form-select select2" id="approved_by">
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                          </select>
                          <div v-if="validationErrors.approved_by" class="text-danger">{{ validationErrors.approved_by[0] }}</div>
                        </div>
                      </div>
                      <div class="col-3 border">
                        <div class="row">
                          <span class="text-center">Received By</span>
                        </div>
                        <div class="col-sm-12">
                          <select v-model="cashRequestForm.received_by" class="form-select select2" id="received_by">
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                          </select>
                          <div v-if="validationErrors.received_by" class="text-danger">{{ validationErrors.received_by[0] }}</div>
                        </div>
                      </div>
                    </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm text-start" @click="clearSelect2">Clear Fields</button>
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
