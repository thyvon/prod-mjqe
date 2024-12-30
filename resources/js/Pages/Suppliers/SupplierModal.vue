<template>
    <div class="modal show d-block" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ action === 'create' ? 'Add Supplier' : 'Edit Supplier' }}</h5>
            <button type="button" class="btn-close" @click="$emit('close')"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" v-model="form.name" class="form-control" id="name" required />
              </div>
              <div class="mb-3">
                <label for="kh_name" class="form-label">Khmer Name</label>
                <input type="text" v-model="form.kh_name" class="form-control" id="kh_name" />
              </div>
              <div class="mb-3">
                <label for="number" class="form-label">Number</label>
                <input type="text" v-model="form.number" class="form-control" id="number" />
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" v-model="form.email" class="form-control" id="email" />
              </div>
              <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea v-model="form.address" class="form-control" id="address"></textarea>
              </div>
              <div class="mb-3">
                <label for="payment_term" class="form-label">Payment Term</label>
                <input type="text" v-model="form.payment_term" class="form-control" id="payment_term" />
              </div>
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select v-model="form.status" class="form-select" id="status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">{{ action === 'create' ? 'Create' : 'Update' }}</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  export default {
    props: {
      action: { type: String, required: true },
      supplier: { type: Object, default: () => ({}) },
    },
    emits: ['close', 'submit'],
    data() {
      return {
        form: { ...this.supplier },
      };
    },
    methods: {
      submitForm() {
        this.$emit('submit', this.form);
      },
    },
  };
  </script>
