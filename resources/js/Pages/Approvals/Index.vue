<template>
  <Main>
    <Head :title="'Approvals'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Approvals</h4>
        <div class="panel-heading-btn">
          <!-- ...existing code... -->
        </div>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="approvals-table" class="table table-bordered table-sm align-middle text-wrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Approval Name</th>
                <th>Requested Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(approval, index) in approvals" :key="approval.id">
                <td>{{ index + 1 }}</td>
                <td>{{ approval.approval_name }}</td>
                <td>{{ formatDate(approval.created_at) }}</td>
                <td>
                  <span v-if="approval.status === 0" class="badge bg-warning">Pending</span>
                  <span v-else-if="approval.status === 1" class="badge bg-success">Approved</span>
                  <span v-else class="badge bg-danger">Rejected</span>
                </td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-primary btn-sm">View</button>
                  </div>
                </td>
              </tr>
              <tr v-if="approvals.length === 0">
                <td colspan="8" class="text-center">No approvals found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { onMounted } from 'vue';

defineProps({
  approvals: {
    type: Array,
    required: true,
    default: () => [] // Ensure approvals is always an array
  }
});

function formatDate(date) {
  if (!date) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true };
  return new Intl.DateTimeFormat('en-US', options).format(new Date(date));
}

onMounted(() => {
  const table = document.querySelector('#approvals-table');
  if (table) {
    $(table).DataTable(); // Initialize DataTable
  }
});
</script>
