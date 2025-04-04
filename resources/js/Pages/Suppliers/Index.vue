<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  suppliers: Array,
});

const isEdit = ref(false);
const supplierForm = reactive({
  id: null,
  name: '',
  kh_name: '',
  number: '',
  email: '',
  payment_term: '',
  vat: null, // Add vat field
  address: '', // Move address field to the bottom
  status: 1,
  currency: 1, // Add currency field with default value
});

const validationErrors = ref({});
let dataTableInstance;

// Functions
const openCreateModal = () => {
  isEdit.value = false;
  clearForm();
  const modalElement = document.getElementById('supplierFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const openEditModal = (supplier) => {
  isEdit.value = true;
  Object.assign(supplierForm, supplier, { status: supplier.status.toString() });
  const modalElement = document.getElementById('supplierFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const saveSupplier = async () => {
  try {
    if (isEdit.value) {
      const response = await axios.put(`/suppliers/${supplierForm.id}`, supplierForm);
      const updatedSupplier = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedSupplier.id).index();
      dataTableInstance.row(rowIndex).data(updatedSupplier).draw();
      swal('Success!', 'Supplier updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/suppliers', supplierForm);
      dataTableInstance.row.add(response.data).draw();
      swal('Success!', 'Supplier created successfully!', 'success', { timer: 2000 });
    }
    const modalElement = document.getElementById('supplierFormModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save supplier. Please try again.', 'error');
    }
  }
};

const deleteSupplier = async (supplierId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this supplier!',
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
        await axios.delete(`/suppliers/${supplierId}`);
        dataTableInstance.row((idx, data) => data.id === supplierId).remove().draw();
        swal('Deleted!', 'Supplier has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete supplier. Please try again.', 'error');
      }
    }
  });
};

const clearForm = () => {
  Object.assign(supplierForm, {
    id: null,
    name: '',
    kh_name: '',
    number: '',
    email: '',
    payment_term: '',
    vat: null, // Add vat field
    address: '', // Move address field to the bottom
    status: 1,
    currency: 1, // Reset currency field
  });
  validationErrors.value = {};
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#supplier-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: false,
        data: props.suppliers,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'name' },
          { data: 'kh_name' },
          { data: 'number' },
          { data: 'email' },
          { data: 'payment_term' },
          { data: 'vat' }, // Add vat column
          { data: 'address', className: 'text-wrap' }, // Enable text wrapping for address column
          { data: 'status', render: (data) => {return `<span class="badge ${data === 1 ? 'bg-primary' : 'bg-danger'}">${data === 1 ? 'Active' : 'Inactive'}</span>`;}, className: 'text-center' },
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
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners to the main table
      $('#supplier-table')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditModal(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteSupplier(rowData.id);
        });

      // Handle actions inside child rows (responsive details)
      $('#supplier-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditModal(rowData);
      });

      $('#supplier-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteSupplier(rowData.id);
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Supplier List'" />
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
        <button @click="openCreateModal" class="btn btn-primary btn-sm mb-4">Add New Supplier</button>

        <!-- Table Responsive Wrapper -->
        <div class="table-responsive">
          <!-- Supplier Table -->
          <table id="supplier-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Khmer Name</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Payment Term</th>
                <th>VAT(%)</th> <!-- Add VAT column -->
                <th class="text-wrap" style="min-width: 100px;">Address</th> <!-- Enable text wrapping -->
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
        <!-- End Table Responsive Wrapper -->

        <!-- Supplier Form Modal -->
        <div class="modal fade" id="supplierFormModal" tabindex="-1" aria-labelledby="supplierFormModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="supplierFormModalLabel">{{ isEdit ? 'Edit Supplier' : 'Add New Supplier' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveSupplier">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="row mb-3 align-items-center">
                        <label for="name" class="col-sm-4 col-form-label">Name</label>
                        <div class="col-sm-8">
                          <input v-model="supplierForm.name" type="text" class="form-control" id="name" required />
                          <div v-if="validationErrors.name" class="text-danger">{{ validationErrors.name[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="kh_name" class="col-sm-4 col-form-label">Khmer Name</label>
                        <div class="col-sm-8">
                          <input v-model="supplierForm.kh_name" type="text" class="form-control" id="kh_name" required />
                          <div v-if="validationErrors.kh_name" class="text-danger">{{ validationErrors.kh_name[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="email" class="col-sm-4 col-form-label">Email Address</label>
                        <div class="col-sm-8">
                          <input v-model="supplierForm.email" type="email" class="form-control" id="email" required />
                          <div v-if="validationErrors.email" class="text-danger">{{ validationErrors.email[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="number" class="col-sm-4 col-form-label">Phone</label>
                        <div class="col-sm-8">
                          <input v-model="supplierForm.number" type="text" class="form-control" id="number" required />
                          <div v-if="validationErrors.number" class="text-danger">{{ validationErrors.number[0] }}</div>
                        </div>
                      </div>

                    </div>
                    <div class="col-md-6">
                      <div class="row mb-3 align-items-center">
                        <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                        <div class="col-sm-8">
                          <select v-model="supplierForm.payment_term" class="form-select" id="payment_term" required>
                            <option value="Credit 1 Week">Credit 1 Week</option>
                            <option value="Credit 2 Weeks">Credit 2 Weeks</option>
                            <option value="Credit 1 Month">Credit 1 Month</option>
                            <option value="None Credit">None Credit</option>
                          </select>
                          <div v-if="validationErrors.payment_term" class="text-danger">{{ validationErrors.payment_term[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="vat" class="col-sm-4 col-form-label">VAT(%)</label>
                        <div class="col-sm-8">
                          <input v-model="supplierForm.vat" type="number" class="form-control" id="vat" />
                          <div v-if="validationErrors.vat" class="text-danger">{{ validationErrors.vat[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                        <div class="col-sm-8">
                          <select v-model="supplierForm.currency" class="form-select" id="currency" required>
                            <option value="1">USD</option>
                            <option value="2">KHR</option>
                          </select>
                          <div v-if="validationErrors.currency" class="text-danger">{{ validationErrors.currency[0] }}</div>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="status" class="col-sm-4 col-form-label">Status</label>
                        <div class="col-sm-8">
                          <select v-model="supplierForm.status" class="form-select" id="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                          </select>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-center">
                        <label for="address" class="col-sm-4 col-form-label">Address</label>
                        <div class="col-sm-8">
                          <textarea v-model="supplierForm.address" class="form-control" rows="3" id="address" required></textarea>
                          <div v-if="validationErrors.address" class="text-danger">{{ validationErrors.address[0] }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update Supplier' : 'Add Supplier' }}</button>
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
