<template>
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
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  supplier: Object,
  isEdit: Boolean,
});

const supplierForm = reactive({
  id: null,
  name: '',
  kh_name: '',
  number: '',
  email: '',
  payment_term: '',
  vat: null,
  address: '',
  status: 1,
  currency: 1, // Add currency field with default value
});

const validationErrors = ref({});

watch(
  () => props.supplier,
  (newSupplier) => {
    if (newSupplier) {
      Object.assign(supplierForm, newSupplier, { currency: newSupplier.currency || 1 }); // Ensure currency is set
    }
  },
  { immediate: true }
);

const saveSupplier = async () => {
  try {
    if (props.isEdit) {
      const response = await axios.put(`/suppliers/${supplierForm.id}`, supplierForm);
      // Handle success
    } else {
      const response = await axios.post('/suppliers', supplierForm);
      // Handle success
    }
    const modalElement = document.getElementById('supplierFormModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      // Handle error
    }
  }
};
</script>
