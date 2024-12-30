<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { ref, reactive, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';

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
  uom: '',
  quantity: '',
  status: 1,
});

// Functions
const editProduct = (product) => {
  isEdit.value = true;
  Object.assign(productForm, product, { status: product.status.toString() });

  const modalElement = document.getElementById('productFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const createProduct = async () => {
  try {
    await Inertia.post('/products', productForm);
    clearForm();

    swal({
      title: 'Success!',
      text: 'Product created successfully!',
      icon: 'success',
      timer: 2000,
      buttons: false,
    });
  } catch (error) {
    console.error('Failed to create product:', error);
    swal({
      title: 'Error!',
      text: 'Failed to create product. Please try again.',
      icon: 'error',
      timer: 2000,
      buttons: false,
    });
  }
};

const updateProduct = async () => {
  try {
    await Inertia.put(`/products/${productForm.id}`, productForm);
    clearForm();

    swal({
      title: 'Success!',
      text: 'Product updated successfully!',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (error) {
    console.error('Failed to update product:', error);
    swal({
      title: 'Error!',
      text: 'Failed to update product. Please try again.',
      icon: 'error',
      timer: 2000,
      showConfirmButton: false,
    });
  }
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
        await Inertia.delete(`/products/${productId}`);
        swal('Deleted!', 'Product has been deleted.', 'success');
      } catch (error) {
        console.error('Failed to delete product:', error);
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
    uom: '',
    quantity: '',
    status: 1,
  });
};

// Initialize DataTable
onMounted(() => {
  const table = $('#product-table').DataTable({
    responsive: true,
    autoWidth: false,
    data: props.products,
    columns: [
      { data: null, render: (data, type, row, meta) => meta.row + 1 },
      { data: 'sku' },
      { data: 'product_description' },
      { data: 'brand' },
      { data: 'category.name' },
      { data: 'group.name' },
    //   { data: 'price' },
      { data: 'uom' },
    //   { data: 'quantity' },
      { data: 'status', render: (data) => {return `<span class="badge ${data === 1 ? 'bg-primary' : 'bg-danger'}">${data === 1 ? 'Active' : 'Inactive'}</span>`;},className: 'text-center'},
      {
        data: null,
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
  $('#product-table')
    .on('click', '.btn-edit', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) editProduct(rowData);
    })
    .on('click', '.btn-delete', function () {
      const rowData = table.row($(this).closest('tr')).data();
      if (rowData) deleteProduct(rowData.id);
    });

  // Handle actions inside child rows (responsive details)
  $('#product-table').on('click', '.dtr-details .btn-edit', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) editProduct(rowData);
  });

  $('#product-table').on('click', '.dtr-details .btn-delete', function () {
    const tr = $(this).closest('tr').prev(); // Get the parent row of the child
    const rowData = table.row(tr).data();
    if (rowData) deleteProduct(rowData.id);
  });
});
</script>

<template>
    <Main>
      <Head :title="'Product List'"></Head>
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
          <button type="button" class="btn btn-gray mb-2" @click="clearForm; isEdit = false;" data-bs-toggle="modal" data-bs-target="#productFormModal">
            Add New Product
          </button>

          <!-- Product Table -->
          <table id="product-table" width="100%" class="table table-bordered align-middle text-nowrap">
            <thead>
              <tr>
                <th width="1%">#</th>
                <th width="3">SKU</th>
                <th>Description</th>
                <th width="3">Brand</th>
                <th width="3">Category</th>
                <th width="3">Group</th>
                <th width="2">UoM</th>
                <th width="2%">Status</th>
                <th width="1%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(product, index) in products" :key="product.id">
                <td>{{ index + 1 }}</td>
                <td>{{ product.sku }}</td>
                <td>{{ product.product_description }}</td>
                <td>{{ product.brand }}</td>
                <td>{{ product.category ? product.category.name : 'N/A' }}</td>
                <td>{{ product.group ? product.group.name : 'N/A' }}</td>
                <td>{{ product.uom }}</td>
                <td><span class="badge bg-primary">{{ product.status === 1 ? 'Active' : 'Inactive' }}</span></td>
                <td>
                  <div class="btn-group">
                    <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
                      <i class="fas fa-cog fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <a class="dropdown-item btn-edit" href="javascript:;" @click="editProduct(product)">
                          <i class="fas fa-edit"></i> Edit
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item btn-delete text-danger" href="javascript:;" @click="deleteProduct(product.id)">
                          <i class="fas fa-trash-alt"></i> Delete
                        </a>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Product Form Modal -->
      <div class="modal fade" id="productFormModal" tabindex="-1" aria-labelledby="productFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="productFormModalLabel">{{ isEdit ? 'Edit Product' : 'Add New Product' }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="isEdit ? updateProduct() : createProduct()">
                <div class="row">
                  <div class="col-md-6">
                    <label for="product-description">Description</label>
                    <input id="product-description" name="product_description" type="text" v-model="productForm.product_description" class="form-control" required />
                  </div>
                  <div class="col-md-6">
                    <label for="brand">Brand</label>
                    <input id="brand" name="brand" type="text" v-model="productForm.brand" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label for="category-select">Category</label>
                    <select id="category-select" name="category_id" v-model="productForm.category_id" class="selectpicker form-control" data-live-search="true">
                      <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="group-select">Group</label>
                    <select id="group-select" name="group_id" v-model="productForm.group_id" class="form-control" required>
                      <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="price">Price</label>
                    <input id="price" name="price" type="number" v-model="productForm.price" class="form-control" required />
                  </div>
                  <div class="col-md-6">
                    <label for="uom">UoM</label>
                    <input id="uom" name="uom" type="text" v-model="productForm.uom" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" v-model="productForm.quantity" class="form-control" required />
                  </div>
                  <div class="col-md-6">
                    <label for="status">Status</label>
                    <select id="status" name="status" v-model="productForm.status" class="form-control" required>
                      <option value="1">Active</option>
                      <option value="0">Inactive</option>
                    </select>
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
    </Main>
</template>
