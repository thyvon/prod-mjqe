<script setup>
import { ref, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  users: Array,
  currentUser: Object,
  products: Array,
});

const form = ref({
  pr_number: '',
  request_date: new Date().toISOString().split('T')[0],
  request_by: props.currentUser?.id || '',
  campus: '',
  division: '',
  department: '',
  purpose: '',
  is_urgent: false,
  items: [],
});

const newItem = ref({
  product_id: '',
  remark: '',
  qty: 0,
  uom: '',
  price: 0,
  total_price: 0,
  campus: '',
  division: '',
  department: ''
});

const calculateTotal = () => {
  newItem.value.total_price = newItem.value.qty * newItem.value.price;
};

const addItem = () => {
  form.value.items.push({ ...newItem.value });

  // Reset newItem object after adding
  newItem.value = {
    product_id: '',
    remark: '',
    qty: 0,
    uom: '',
    price: 0,
    total_price: 0,
    campus: form.value.campus,
    division: form.value.division,
    department: form.value.department
  };
  closeModal();
};

const updateUom = () => {
  const selectedProduct = props.products.find(product => product.id === newItem.value.product_id);
  newItem.value.uom = selectedProduct ? selectedProduct.uom : '';
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const openModal = () => {
  newItem.value = {
    product_id: '',
    remark: '',
    qty: 0,
    uom: '',
    price: 0,
    total_price: 0,
    campus: form.value.campus,
    division: form.value.division,
    department: form.value.department,
  };
  $('#itemModal').modal('show');
};

const closeModal = () => {
  $('#itemModal').modal('hide');
};

const submitForm = () => {
  Inertia.post('/purchase-requests', form.value);
};

onMounted(() => {
  console.log('Create Purchase Request Page');
});
</script>

<template>
  <Main>
    <Head title="Create Purchase Request" />
    <div>
      <form @submit.prevent="submitForm">
        <!-- Purchase Request Form Fields -->
        <div class="row">
          <!-- Left Column -->
          <div class="col-md-6">
            <h5 class="mb-3">Requester Information</h5>
            <div class="row mb-3">
              <label for="pr_number" class="col-sm-4 col-form-label">PR Number</label>
              <div class="col-sm-8">
                <input v-model="form.pr_number" type="text" class="form-control" id="pr_number" required />
              </div>
            </div>
            <div class="row mb-3">
              <label for="request_date" class="col-sm-4 col-form-label">Request Date</label>
              <div class="col-sm-8">
                <input v-model="form.request_date" type="date" class="form-control" id="request_date" required />
              </div>
            </div>
            <div class="row mb-3">
              <label for="request_by" class="col-sm-4 col-form-label">Requested By</label>
              <div class="col-sm-8">
                <input :value="currentUser.name" type="text" class="form-control" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="is_urgent" class="col-sm-4 col-form-label">Is Urgent</label>
              <div class="col-sm-8">
                <input v-model="form.is_urgent" type="checkbox" class="form-check-input" id="is_urgent" />
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="col-md-6">
            <h5 class="mb-3">Additional Information</h5>
            <div class="row mb-3">
              <label for="campus" class="col-sm-4 col-form-label">Campus</label>
              <div class="col-sm-8">
                <input v-model="form.campus" type="text" class="form-control" id="campus" required />
              </div>
            </div>
            <div class="row mb-3">
              <label for="division" class="col-sm-4 col-form-label">Division</label>
              <div class="col-sm-8">
                <input v-model="form.division" type="text" class="form-control" id="division" required />
              </div>
            </div>
            <div class="row mb-3">
              <label for="department" class="col-sm-4 col-form-label">Department</label>
              <div class="col-sm-8">
                <input v-model="form.department" type="text" class="form-control" id="department" required />
              </div>
            </div>
            <div class="row mb-3">
              <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
              <div class="col-sm-8">
                <input v-model="form.purpose" type="text" class="form-control" id="purpose" required />
              </div>
            </div>
          </div>
        </div>

        <!-- PR Items Section -->
        <div class="panel panel-inverse mt-3">
          <div class="panel-heading">
            <h4 class="panel-title">Purchase Request Items</h4>
          </div>
          <div class="panel-body">
            <button type="button" class="btn btn-primary mb-3" @click="openModal()">Add Item</button>

            <!-- Items Table -->
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>UOM</th>
                  <th>Price</th>
                  <th>Total</th>
                  <th>Campus</th>
                  <th>Division</th>
                  <th>Department</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in form.items" :key="index">
                  <td>{{ products.find(p => p.id === item.product_id)?.product_description || 'N/A' }}</td>
                  <td>{{ item.qty }}</td>
                  <td>{{ item.uom }}</td>
                  <td>{{ item.price }}</td>
                  <td>{{ item.total_price }}</td>
                  <td>{{ item.campus }}</td>
                  <td>{{ item.division }}</td>
                  <td>{{ item.department }}</td>
                  <td>
                    <button type="button" class="btn btn-warning" @click="openModal(index)">Edit</button>
                    <button type="button" class="btn btn-danger" @click="removeItem(index)">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="row mt-3">
          <div class="col-sm-12">
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
      </form>

      <!-- Item Modal -->
      <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="itemModalLabel">Add/Edit Item</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-sm-12 mb-3">
                  <label for="product_id">Product</label>
                  <select v-model="newItem.product_id" @change="updateUom" class="form-control" required>
                    <option value="">Select Product</option>
                    <option v-for="product in props.products" :key="product.id" :value="product.id">{{ product.product_description }}</option>
                  </select>
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="qty">Quantity</label>
                  <input v-model="newItem.qty" type="number" class="form-control" id="qty" @input="calculateTotal" required />
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="uom">UOM</label>
                  <input v-model="newItem.uom" type="text" class="form-control" id="uom" readonly />
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="price">Price</label>
                  <input v-model="newItem.price" type="number" class="form-control" id="price" @input="calculateTotal" required />
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="total_price">Total Price</label>
                  <input v-model="newItem.total_price" type="number" class="form-control" id="total_price" readonly />
                </div>
                <!-- Campus, Division, Department fields are now read-only and default from PR -->
                <div class="col-sm-12 mb-3">
                  <label for="campus">Campus</label>
                  <input v-model="newItem.campus" type="text" class="form-control" id="campus" readonly />
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="division">Division</label>
                  <input v-model="newItem.division" type="text" class="form-control" id="division" readonly />
                </div>
                <div class="col-sm-12 mb-3">
                  <label for="department">Department</label>
                  <input v-model="newItem.department" type="text" class="form-control" id="department" readonly />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" @click="addItem">Add Item</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Main>
</template>
