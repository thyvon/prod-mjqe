<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { ref, reactive, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
  categories: Array,
});

// Modal state and form data
const isEdit = ref(false);
const categoryForm = reactive({
  id: null,
  name: '',
  remark: '',
});

// Functions
const editCategory = (category) => {
  isEdit.value = true;
  Object.assign(categoryForm, category);
  const modalElement = document.getElementById('categoryFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const createCategory = async () => {
  try {
    await Inertia.post('/categories', categoryForm);
    clearForm();

    // Show success alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Success!',
      text: 'Category created successfully!',
      icon: 'success',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  } catch (error) {
    console.error('Failed to create category:', error);

    // Show error alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Error!',
      text: 'Failed to create category. Please try again.',
      icon: 'error',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  }
};


const updateCategory = async () => {
  try {
    await Inertia.put(`/categories/${categoryForm.id}`, categoryForm);
    clearForm();

    // Show success alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Success!',
      text: 'Category updated successfully!',
      icon: 'success',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  } catch (error) {
    console.error('Failed to update Category:', error);

    // Show error alert with auto-dismiss after 2 seconds and no OK button
    swal({
      title: 'Error!',
      text: 'Failed to update Category. Please try again.',
      icon: 'error',
      timer: 2000,  // Auto-close after 2 seconds
      showConfirmButton: false,  // Remove OK button
    });
  }
};


const deleteCategory = async (categoryId) => {
  // Show confirmation alert using SweetAlert (Bootstrap version)
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
        await Inertia.delete(`/categories/${categoryId}`);
        swal('Deleted!', 'Category has been deleted.', 'success');  // Success alert
      } catch (error) {
        console.error('Failed to delete category:', error);
        swal('Error!', 'Failed to delete category. Please try again.', 'error');  // Error alert
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
};

// Initialize DataTable and attach event listeners
onMounted(() => {
  const table = $('#category-table').DataTable({
    responsive: true,
    autoWidth: true,
    data: props.categories,
    columns: [
      { data: null, render: (data, type, row, meta) => meta.row + 1 },
      { data: 'name' },
      { data: 'remark' },
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
  $('#category-table')
    .on('click', '.btn-edit', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) editCategory(rowData);
    })
    .on('click', '.btn-delete', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) deleteCategory(rowData.id);
    });

  // Handle actions inside child rows (responsive details)
  $('#category-table').on('click', '.dtr-details .btn-edit', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) editCategory(rowData);
  });

  $('#category-table').on('click', '.dtr-details .btn-delete', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) deleteCategory(rowData.id);
  });
});


</script>

<template>
    <Main>
      <Head :title="'Category List'"></Head>
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
          <button type="button" class="btn btn-sm btn-primary mb-2" @click="clearForm; isEdit = false;" data-bs-toggle="modal" data-bs-target="#categoryFormModal">
            Add New Category
          </button>

          <!-- Supplier Table -->
          <table id="category-table" width="100%" class="table table-bordered align-middle text-nowrap">
            <thead>
              <tr class="odd gradeX">
                <th width="1%">#</th>
                <th>Name</th>
                <th>Remark</th>
                <th width="1%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(category, index) in categories" :key="category.id">
                <td>{{ index + 1 }}</td>
                <td>{{ category.name }}</td>
                <td>{{ category.remark }}</td>
                <td>
                  <button class="btn btn-default" @click="editCategory(category)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Delete Button -->
                  <button class="btn btn-danger" @click="deleteCategory(category.id)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Supplier Form Modal -->
      <div class="modal fade" id="categoryFormModal" tabindex="-1" aria-labelledby="categoryFormModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="categoryFormModalLabel">{{ isEdit ? 'Edit Category' : 'Add New Category' }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="isEdit ? updateCategory() : createCategory()">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" v-model="categoryForm.name" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="kh_name" class="form-label">Remark</label>
                    <input type="text" class="form-control" id="remark" v-model="categoryForm.remark" required>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <button type="submit" class="btn btn-success btn-block">
                      <span>{{ isEdit ? 'Update Category' : 'Save' }}</span>
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
