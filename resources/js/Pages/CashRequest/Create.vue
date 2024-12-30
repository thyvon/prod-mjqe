<template>
    <Main>
      <Head :title="'Create Cash Request'" />

      <div class="panel panel-inverse">
        <div class="card shadow">
          <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create Cash Request</h4>
            <button @click="goBack" class="btn btn-danger btn-sm">Go Back</button>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm">
                <div class="row">
                    <!-- Left Column: Requester Information -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Requester Information</h5>

                        <div class="row mb-3 align-items-center">
                        <label for="request_type" class="col-sm-4 col-form-label">Request Type</label>
                        <div class="col-sm-8">
                            <select v-model="form.request_type" class="form-select" id="request_type" required>
                            <option value="" disabled>Select request type</option>
                            <option v-for="(type, index) in requestTypes" :key="index" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="request_date" class="col-sm-4 col-form-label">Request Date</label>
                        <div class="col-sm-8">
                            <input v-model="form.request_date" type="date" class="form-control" id="request_date" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="user_id" class="col-sm-4 col-form-label">Request By</label>
                        <div class="col-sm-8">
                            <select v-model="form.user_id" class="form-select" id="user_id" required>
                            <option value="" disabled>Select user</option>
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="position" class="col-sm-4 col-form-label">Position</label>
                        <div class="col-sm-8">
                            <input v-model="form.position" type="text" class="form-control" id="position" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="id_card" class="col-sm-4 col-form-label">ID Card</label>
                        <div class="col-sm-8">
                            <input v-model="form.id_card" type="text" class="form-control" id="id_card" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="campus" class="col-sm-4 col-form-label">Campus</label>
                        <div class="col-sm-8">
                            <select v-model="form.campus" class="form-select" id="campus" required>
                            <option value="" disabled>Select campus</option>
                            <option v-for="(campus, index) in campuses" :key="index" :value="campus">{{ campus }}</option>
                            </select>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="division" class="col-sm-4 col-form-label">Division</label>
                        <div class="col-sm-8">
                            <input v-model="form.division" type="text" class="form-control" id="division" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="department" class="col-sm-4 col-form-label">Department</label>
                        <div class="col-sm-8">
                            <input v-model="form.department" type="text" class="form-control" id="department" required />
                        </div>
                        </div>
                    </div>

                    <!-- Right Column: Financial Information -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Financial Information</h5>

                        <div class="row mb-3 align-items-center">
                        <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                        <div class="col-sm-8">
                            <select v-model="form.currency" class="form-select" id="currency" required>
                            <option value="" disabled>Select currency</option>
                            <option v-for="(currency, index) in currencies" :key="index" :value="currency">{{ currency }}</option>
                            </select>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="exchange_rate" class="col-sm-4 col-form-label">Exchange Rate</label>
                        <div class="col-sm-8">
                            <input v-model="form.exchange_rate" type="number" step="0.01" class="form-control" id="exchange_rate" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="amount" class="col-sm-4 col-form-label">Amount</label>
                        <div class="col-sm-8">
                            <input v-model="form.amount" type="number" step="0.01" class="form-control" id="amount" required />
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="via" class="col-sm-4 col-form-label">Payment Method</label>
                        <div class="col-sm-8">
                            <select v-model="form.via" class="form-select" id="via" required>
                            <option value="" disabled>Select payment method</option>
                            <option v-for="(via, index) in vias" :key="index" :value="via">{{ via }}</option>
                            </select>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="description" class="col-sm-4 col-form-label">Description</label>
                        <div class="col-sm-8">
                            <textarea v-model="form.description" class="form-control" id="description"></textarea>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="reason" class="col-sm-4 col-form-label">Reason</label>
                        <div class="col-sm-8">
                            <textarea v-model="form.reason" class="form-control" id="reason"></textarea>
                        </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                        <label for="remark" class="col-sm-4 col-form-label">Remark</label>
                        <div class="col-sm-8">
                            <textarea v-model="form.remark" class="form-control" id="remark"></textarea>
                        </div>
                        </div>
                    </div>
                </div>


              <!-- Submit Button -->
              <div class="text-end">
                <button type="submit" class="btn btn-primary">Submit Request</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Main>
  </template>

  <script setup>
  import { ref } from 'vue';
  import { Inertia } from '@inertiajs/inertia';
  import { Head } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';

  // Props
  const props = defineProps({
    users: Array,
    currentUser: Object,
  });

  // Form State
  const form = ref({
    request_type: '',
    request_date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser ? props.currentUser.id : '',
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

  // Options
  const requestTypes = ['Petty Cash', 'Cash Advance'];
  const campuses = ['CEN', 'TK'];
  const currencies = ['USD', 'KHR'];
  const vias = ['Bank Transfer', 'Cash', 'Cheque'];

  // Handlers
  const goBack = () => {
    window.history.back();
  };

  const submitForm = () => {
    Inertia.post('/cash-request', form.value, {
      onSuccess: () => alert('Cash request submitted successfully!'),
    });
  };
  </script>
