<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  categories: Array,
});

const isEdit = ref(false);
const categoryForm = reactive({
  id: null,
  name: '',
  remark: '',
});

const validationErrors = ref({});
let dataTableInstance;

// Functions
const openCreateModal = () => {
  isEdit.value = false;
  clearForm();
  const modalElement = document.getElementById('categoryFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const openEditModal = (category) => {
  isEdit.value = true;
  Object.assign(categoryForm, category);
  const modalElement = document.getElementById('categoryFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const saveCategory = async () => {
  try {
    let response;
    if (isEdit.value) {
      response = await axios.put(`/categories/${categoryForm.id}`, categoryForm);
      const updatedCategory = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedCategory.id).index();
      dataTableInstance.row(rowIndex).data(updatedCategory).draw();
      swal('Success!', 'Category updated successfully!', 'success', { timer: 2000 });
    } else {
      response = await axios.post('/categories', categoryForm);
      dataTableInstance.row.add(response.data).draw();
      swal('Success!', 'Category created successfully!', 'success', { timer: 2000 });
    }
    const modalElement = document.getElementById('categoryFormModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
    clearForm();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save category. Please try again.', 'error');
    }
  }
};

const deleteCategory = async (categoryId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this category!',
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
        await axios.delete(`/categories/${categoryId}`);
        dataTableInstance.row((idx, data) => data.id === categoryId).remove().draw();
        swal('Deleted!', 'Category has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        if (error.response && error.response.status === 400) {
          swal('Error!', error.response.data.message, 'error');
        } else {
          swal('Error!', 'Failed to delete category. Please try again.', 'error');
        }
      }
    }
  });
};

const clearForm = () => {
  Object.assign(categoryForm, {
    id: null,
    name: '',
    remark: '',
  });
  validationErrors.value = {};
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#category-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: false,
        data: props.categories,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'name' },
          { data: 'remark' },
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
      $('#category-table')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditModal(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteCategory(rowData.id);
        });

      // Handle actions inside child rows (responsive details)
      $('#category-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditModal(rowData);
      });

      $('#category-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteCategory(rowData.id);
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Category List'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Product Category</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Add New Category</button>

        <!-- Category Table -->
        <table id="category-table" class="table table-bordered align-middle text-nowrap" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Remark</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>

        <!-- Category Form Modal -->
        <div class="modal fade" id="categoryFormModal" tabindex="-1" aria-labelledby="categoryFormModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="categoryFormModalLabel">{{ isEdit ? 'Edit Category' : 'Add New Category' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveCategory">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input v-model="categoryForm.name" type="text" class="form-control" id="name" required />
                        <div v-if="validationErrors.name" class="text-danger">{{ validationErrors.name[0] }}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="remark" class="form-label">Remark</label>
                        <input v-model="categoryForm.remark" type="text" class="form-control" id="remark" required />
                        <div v-if="validationErrors.remark" class="text-danger">{{ validationErrors.remark[0] }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update Category' : 'Add Category' }}</button>
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
