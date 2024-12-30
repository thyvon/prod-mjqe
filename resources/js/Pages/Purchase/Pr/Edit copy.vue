<template>
  <Main>
    <Head :title="'Edit Purchase Request'" />

    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Edit Purchase Request</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand">
            <i class="fa fa-expand"></i>
          </a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload">
            <i class="fa fa-redo"></i>
          </a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse">
            <i class="fa fa-minus"></i>
          </a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove">
            <i class="fa fa-times"></i>
          </a>
        </div>
      </div>

      <div class="panel-body">
        <form @submit.prevent="submitForm">
          <div class="row">
            <!-- Left Column: Requester Information -->
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

            <!-- Right Column: Additional Information -->
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

          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" class="btn btn-secondary mb-4 btn-sm mr-2">Update Request</button>
            <Link href="/purchase-requests" class="btn btn-danger mb-4 btn-sm">Return to Index</Link>
          </div>
        </form>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Props
const props = defineProps({
  purchaseRequest: Object,
  currentUser: Object,
});

// Form State
const form = ref({
  pr_number: '',
  request_date: new Date().toISOString().split('T')[0],
  request_by: props.currentUser ? props.currentUser.id : '',
  campus: '',
  division: '',
  department: '',
  purpose: '',
  is_urgent: false,
});

// Populate form with existing data for editing
onMounted(() => {
  if (props.purchaseRequest) {
    form.value = { ...props.purchaseRequest };
  }
});

// Handlers
const goBack = () => {
  window.history.back();
};

const submitForm = () => {
  Inertia.put(`/purchase-requests/${form.value.id}`, form.value, {
    onSuccess: () => alert('Purchase request updated successfully!'),
  });
};
</script>
