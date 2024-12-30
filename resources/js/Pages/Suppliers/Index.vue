<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { ref, reactive, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
  suppliers: Array,
});

// Modal state and form data
const isEdit = ref(false);
const supplierForm = reactive({
  id: null,
  name: '',
  kh_name: '',
  number: '',
  email: '',
  address: '',
  payment_term: '',
  status: 1,
});

// Functions
const editSupplier = (supplier) => {
  isEdit.value = true;
  Object.assign(supplierForm, supplier, { status: supplier.status.toString() });

  const modalElement = document.getElementById('supplierFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const createSupplier = async () => {
  try {
    await Inertia.post('/suppliers', supplierForm);
    clearForm();

    // Show success alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Success!',
      text: 'Supplier created successfully!',
      icon: 'success',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  } catch (error) {
    console.error('Failed to create supplier:', error);

    // Show error alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Error!',
      text: 'Failed to create supplier. Please try again.',
      icon: 'error',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  }
};


const updateSupplier = async () => {
  try {
    await Inertia.put(`/suppliers/${supplierForm.id}`, supplierForm);
    clearForm();

    // Show success alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Success!',
      text: 'Supplier updated successfully!',
      icon: 'success',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  } catch (error) {
    console.error('Failed to update supplier:', error);

    // Show error alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Error!',
      text: 'Failed to update supplier. Please try again.',
      icon: 'error',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  }
};


const deleteSupplier = async (supplierId) => {
  // Show confirmation alert using SweetAlert (Bootstrap version)
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
        closeModal: true
      },
      confirm: {
        text: 'Yes, delete it!',
        value: true,
        visible: true,
        className: 'btn btn-danger',
        closeModal: true
      }
    },
    dangerMode: true,  // Enables the danger mode for the confirm button
  }).then(async (result) => {
    // If the user clicks "Yes, delete it!"
    if (result) {
      try {
        // Send the request to delete the supplier
        await Inertia.delete(`/suppliers/${supplierId}`);
        swal('Deleted!', 'Supplier has been deleted.', 'success');  // Success alert
      } catch (error) {
        console.error('Failed to delete supplier:', error);
        swal('Error!', 'Failed to delete supplier. Please try again.', 'error');  // Error alert
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
    address: '',
    payment_term: '',
    status: '',
  });
};

// Initialize DataTable and attach event listeners
onMounted(() => {
  const table = $('#supplier-table').DataTable({
    responsive: true,
    autoWidth: true,
    data: props.suppliers,
    columns: [
      { data: null, render: (data, type, row, meta) => meta.row + 1 },
      { data: 'name' },
      { data: 'kh_name' },
      { data: 'number' },
      { data: 'email' },
      { data: 'address' },
      { data: 'payment_term' },
      { data: 'status', render: (data) => (data === 1 ? 'Active' : 'Inactive') },
      {
        data: null,
        // render: () => `
        //   <button class="btn btn-default btn-edit"><i class="fas fa-edit"></i></button>
        //   <button class="btn btn-danger btn-delete"><i class="fas fa-trash-alt"></i></button>
        // `,

        render: () => `
        <div class="btn-group">
            <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item btn-edit">
                <i class="fas fa-edit"></i> Edit
                </a>
            </li>
            <li>
                <a class="dropdown-item btn-delete text-danger">
                <i class="fas fa-trash-alt"></i> Delete
                </a>
            </li>
            </ul>
        </div>
        `,
      },
    ],
  });

  // Attach event listeners to the main table
  $('#supplier-table')
    .on('click', '.btn-edit', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) editSupplier(rowData);
    })
    .on('click', '.btn-delete', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) deleteSupplier(rowData.id);
    });

  // Handle actions inside child rows (responsive details)
  $('#supplier-table').on('click', '.dtr-details .btn-edit', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) editSupplier(rowData);
  });

  $('#supplier-table').on('click', '.dtr-details .btn-delete', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) deleteSupplier(rowData.id);
  });
});


</script>

<template>
    <Main>
      <Head :title="'Supplier List'"></Head>
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
          <button type="button" class="btn btn-sm btn-primary mb-2" @click="clearForm; isEdit = false;" data-bs-toggle="modal" data-bs-target="#supplierFormModal">
            Add New Supplier
          </button>

          <!-- Supplier Table -->
          <table id="supplier-table" width="100%" class="table table-bordered align-middle text-nowrap">
            <thead>
              <tr class="odd gradeX">
                <th width="1%">#</th>
                <th>Name</th>
                <th>Khmer Name</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Address</th>
                <th>Payment Term</th>
                <th>Active?</th>
                <th width="1%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(supplier, index) in suppliers" :key="supplier.id">
                <td>{{ index + 1 }}</td>
                <td>{{ supplier.name }}</td>
                <td>{{ supplier.kh_name }}</td>
                <td>{{ supplier.number }}</td>
                <td>{{ supplier.email }}</td>
                <td>{{ supplier.address }}</td>
                <td>{{ supplier.payment_term }}</td>
                <td>{{ supplier.status }}</td>
                <td>
                  <button class="btn btn-default" @click="editSupplier(supplier)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Delete Button -->
                  <button class="btn btn-danger" @click="deleteSupplier(supplier.id)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Supplier Form Modal -->
      <div class="modal fade" id="supplierFormModal" tabindex="-1" aria-labelledby="supplierFormModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="supplierFormModalLabel">{{ isEdit ? 'Edit Supplier' : 'Add New Supplier' }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="isEdit ? updateSupplier() : createSupplier()">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" v-model="supplierForm.name" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="kh_name" class="form-label">Khmer Name</label>
                    <input type="text" class="form-control" id="kh_name" v-model="supplierForm.kh_name" required>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" v-model="supplierForm.email" required>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="number" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="number" v-model="supplierForm.number" required>
                  </div>
                </div>

                <div class="row mb-2">
                  <label for="address" class="form-label">Address</label>
                  <div class="col-md-12">
                    <textarea class="form-control" rows="3" id="address" v-model="supplierForm.address" required></textarea>
                  </div>
                </div>

                <div class="row m-2">
                  <label for="payment_term" class="form-label col-form-label col-md-3">Payment Term</label>
                  <div class="col-md-7">
                    <select class="form-select" id="payment_term" v-model="supplierForm.payment_term" required>
                      <option value="Credit 1 Week">Credit 1 Week</option>
                      <option value="Credit 2 Weeks">Credit 2 Weeks</option>
                      <option value="Credit 1 Month">Credit 1 Month</option>
                      <option value="None Credit">None Credit</option>
                    </select>
                  </div>
                </div>

                <div class="row m-2">
                    <label for="status" class="form-label col-form-label col-md-3">Status</label>
                    <div class="col-md-7">
                        <input
                        type="checkbox"
                        id="status"
                        :true-value="'1'"
                        :false-value="'0'"
                        v-model="supplierForm.status"
                        class="form-check-input"
                        />
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-6">
                    <button type="submit" class="btn btn-success btn-block">
                      <span>{{ isEdit ? 'Update Supplier' : 'Save' }}</span>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Main>
  </template>
