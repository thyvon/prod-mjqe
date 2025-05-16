<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head, Link} from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  products: Array,
  categories: Array,
  groups: Array,
});

const isEdit = ref(false);
const productForm = reactive({
  id: null,
  sku: '',
  product_description: '',
  brand: '',
  category_id: null,
  group_id: null,
  price: '',
  avg_price: 0,
  uom: '',
  quantity: 0,
  status: 1,
  image: null, // Add image field
});

const validationErrors = ref({});
let dataTableInstance;

// Functions
const openCreateModal = () => {
  isEdit.value = false;
  clearForm();
  const modalElement = document.getElementById('productFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
  });
};

const openShowPage = (productId) => {
  window.location.href = `/products/${productId}`;
};

const openEditModal = (product) => {
  isEdit.value = true;
  Object.assign(productForm, product, { status: product.status.toString() });
  const modalElement = document.getElementById('productFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    initializeSelect2();
  });
};

const saveProduct = async () => {
  const formData = new FormData();
  for (const key in productForm) {
    if (productForm[key] !== null) {
      formData.append(key, productForm[key]);
    }
  }

  try {
    if (isEdit.value) {
      const response = await axios.post(`/products/${productForm.id}?_method=PUT`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      const updatedProduct = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedProduct.id).index();
      dataTableInstance.row(rowIndex).data(updatedProduct).draw();
      swal('Success!', 'Product updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/products', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      dataTableInstance.row.add(response.data).draw();
      swal('Success!', 'Product created successfully!', 'success', { timer: 2000 });
    }

    const modalElement = document.getElementById('productFormModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save product. Please try again.', 'error');
    }
  }
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  productForm.image = file;
};


const deleteProduct = async (productId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this product!',
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
        await axios.delete(`/products/${productId}`);
        dataTableInstance.row((idx, data) => data.id === productId).remove().draw();
        swal('Deleted!', 'Product has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete product. Please try again.', 'error');
      }
    }
  });
};

const clearForm = () => {
  Object.assign(productForm, {
    id: null,
    sku: '',
    product_description: '',
    brand: '',
    category_id: null,
    group_id: null,
    price: '',
    avg_price: '',
    uom: '',
    quantity: '',
    status: 1,
  });
  validationErrors.value = {};
};

const initializeSelect2 = () => {
  $('#productFormModal .select2').select2({
    dropdownParent: $('#productFormModal')
  }).on('change', function () {
    const field = $(this).attr('name');
    productForm[field] = $(this).val();
  });

  // Set initial values for select2 fields
  $('#category-select').val(productForm.category_id).trigger('change');
  $('#group-select').val(productForm.group_id).trigger('change');
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#product-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: false,
        data: props.products,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          {
            data: 'image_path',
            render: (data) => {
              const imgSrc = data ? `/storage/${data}` : 'default.jpg';
              return `
                <div class="image-popup-container" style="position: relative; display: inline-block;">
                  <img src="${imgSrc}" alt="Product Image" style="width: 50px; height: 50px; object-fit: contain; cursor: pointer;" />
                  <div class="image-popup" style="
                    display: none;
                    position: absolute;
                    top: 0;
                    left: 60px;
                    background: white;
                    padding: 5px;
                    z-index: 1000;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                  ">
                    <img src="${imgSrc}" alt="Product Image" style="width: 400px; height: 400px; object-fit: contain;" />
                  </div>
                </div>
              `;
            },
            orderable: false,
            searchable: false,
            className: 'text-center',
          },
          { data: 'sku' },
          { data: 'product_description' },
          { data: 'brand' },
          { data: 'category.name', defaultContent: 'N/A' },
          { data: 'group.name', defaultContent: 'N/A' },
          { data: 'uom' },
          { data: 'price' },
          { data: 'avg_price' },
          { data: 'status', render: (data) => {return `<span class="badge ${data === 1 ? 'bg-primary' : 'bg-danger'}">${data === 1 ? 'Active' : 'Inactive'}</span>`;},className: 'text-center'},
          {
            data: null,
            render: () => `
              <div class="btn-group">
                <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-view"><i class="fas fa-eye"></i> View Details</a></li>
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners to the main table
      $('#product-table')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditModal(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteProduct(rowData.id);
        })

        .on('click', '.btn-view', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openShowPage(rowData.id);
        });
        

      // Handle actions inside child rows (responsive details)
      $('#product-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditModal(rowData);
      });

      $('#product-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteProduct(rowData.id);
      });
      $('#product-table').on('click', '.btn-view', function () {
        const rowData = dataTableInstance.row($(this).closest('tr')).data();
        if (rowData) openShowPage(rowData.id);
      });
    }
  });

  $('#product-table').on('mouseenter', '.image-popup-container', function () {
    $(this).find('.image-popup').show();
  });

  $('#product-table').on('mouseleave', '.image-popup-container', function () {
    $(this).find('.image-popup').hide();
  });

});
</script>

<template>
  <Main>
    <Head :title="'Product List'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Product List</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Add New Product</button>

        <!-- Product Table -->
        <table id="product-table" class="table table-bordered align-middle" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Image</th>
              <th>SKU</th>
              <th>Description</th>
              <th>Brand</th>
              <th>Category</th>
              <th>Group</th>
              <th>UoM</th>
              <th>Latest Price</th>
              <th>Avg Price (3m)</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>

        <!-- Product Form Modal -->
        <div class="modal fade" id="productFormModal" tabindex="-1" aria-labelledby="productFormModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="productFormModalLabel">{{ isEdit ? 'Edit Product' : 'Add New Product' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="saveProduct">
                  <div class="row">
                    <div class="col-md-6">
                      <!-- <div class="mb-3">
                        <label for="sku" class="form-label">SKU</label>
                        <input v-model="productForm.sku" type="text" class="form-control" id="sku" required />
                        <div v-if="validationErrors.sku" class="text-danger">{{ validationErrors.sku[0] }}</div>
                      </div> -->
                      <div class="mb-3">
                        <label for="product-description" class="form-label">Description</label>
                        <input v-model="productForm.product_description" type="text" class="form-control" id="product-description" required />
                        <div v-if="validationErrors.product_description" class="text-danger">{{ validationErrors.product_description[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="brand" class="form-label">Brand</label>
                        <input v-model="productForm.brand" type="text" class="form-control" id="brand" />
                        <div v-if="validationErrors.brand" class="text-danger">{{ validationErrors.brand[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="category-select" class="form-label">Category</label>
                        <select v-model="productForm.category_id" class="form-select select2" id="category-select" name="category_id" required>
                          <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <div v-if="validationErrors.category_id" class="text-danger">{{ validationErrors.category_id[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="group-select" class="form-label">Group</label>
                        <select v-model="productForm.group_id" class="form-select select2" id="group-select" name="group_id" required>
                          <option v-for="group in props.groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>
                        <div v-if="validationErrors.group_id" class="text-danger">{{ validationErrors.group_id[0] }}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input v-model="productForm.price" type="number" class="form-control" id="price" required />
                        <div v-if="validationErrors.price" class="text-danger">{{ validationErrors.price[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="uom" class="form-label">UoM</label>
                        <input v-model="productForm.uom" type="text" class="form-control" id="uom" />
                        <div v-if="validationErrors.uom" class="text-danger">{{ validationErrors.uom[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input v-model="productForm.quantity" type="number" class="form-control" id="quantity" required />
                        <div v-if="validationErrors.quantity" class="text-danger">{{ validationErrors.quantity[0] }}</div>
                      </div>
                      <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select v-model="productForm.status" class="form-select" id="status" required>
                          <option value="1">Active</option>
                          <option value="0">Inactive</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input @change="handleFileChange" type="file" class="form-control" id="image" />
                        <div v-if="validationErrors.image" class="text-danger">{{ validationErrors.image[0] }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update Product' : 'Add Product' }}</button>
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
