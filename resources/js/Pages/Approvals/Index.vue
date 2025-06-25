<template>
  <Main>
    <Head :title="'Approvals'" />
    <!-- Dashboard Summary Start -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i class="fa fa-hourglass-half fa-2x me-3"></i>
              <div>
                <h5 class="card-title mb-0">Pending</h5>
                <h2 class="mb-0">{{ pendingCount }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i class="fa fa-times-circle fa-2x me-3"></i>
              <div>
                <h5 class="card-title mb-0">Rejected</h5>
                <h2 class="mb-0">{{ rejectedCount }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i class="fa fa-check-circle fa-2x me-3"></i>
              <div>
                <h5 class="card-title mb-0">Completed</h5>
                <h2 class="mb-0">{{ completedCount }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i class="fa fa-list-ol fa-2x me-3"></i>
              <div>
                <h5 class="card-title mb-0">Total</h5>
                <h2 class="mb-0">{{ totalApprovals }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Dashboard Summary End -->
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">My Approval List</h4>
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
                <th>Document Type</th>
                <th>Reference No</th>
                <th>Requested Date</th>
                <th>Request Type</th>
                <th>Status</th>
                <th>Responded Date</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { onMounted, nextTick, computed } from 'vue';

const props = defineProps({
  approvals: {
    type: [Array, Object],
    required: true,
    default: () => [],
  }
});

// Dashboard summary computed properties
const totalApprovals = computed(() => {
  return Array.isArray(props.approvals) ? props.approvals.length : 0;
});

const pendingCount = computed(() =>
  Array.isArray(props.approvals)
    ? props.approvals.filter(a => a.status === 0).length
    : 0
);
const rejectedCount = computed(() =>
  Array.isArray(props.approvals)
    ? props.approvals.filter(a => a.status !== 0 && a.status !== 1).length
    : 0
);
const completedCount = computed(() =>
  Array.isArray(props.approvals)
    ? props.approvals.filter(a => a.status === 1).length
    : 0
);

let dataTableInstance;

onMounted(() => {
  nextTick(() => {
    const table = $('#approvals-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: props.approvals, // Use the approvals prop directly
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 }, // Row index
          { data: 'approval_name' }, // Approval name
          { data: 'reference' }, // Reference column
          { data: 'created_at', render: (data) => formatDate(data) }, // Requested date
          {
            data: 'status_type',
            render: (data) => {
              switch (data) {
                case 1: return '<span class="badge bg-info">Need Check</span>';
                case 2: return '<span class="badge bg-primary">Need Acknowledge</span>';
                case 3: return '<span class="badge bg-success">Need Approve</span>';
                case 4: return '<span class="badge bg-warning">Need Receive</span>';
                case 5: return '<span class="badge bg-secondary">Need Authorize</span>';
                case 6: return '<span class="badge bg-danger">Need Reject</span>';
                case 7: return '<span class="badge bg-dark">Need Review</span>';
                default: return '<span class="badge bg-secondary">Unknown</span>';
              }
            }
          }, // Render status_type with badges
          {
            data: 'status',
            render: (data) => {
              if (data === 0) return '<span class="badge bg-warning">Pending</span>';
              if (data === 1) return '<span class="badge bg-success">Completed</span>';
              return '<span class="badge bg-danger">Rejected</span>';
            }
          }, // Status with badge
          { data: 'click_date', render: (data) => formatDate(data) }, // Responded date
          {
            data: null,
            render: (data) => `
              <div class="btn-group">
                <button class="btn btn-primary btn-sm btn-view">View</button>
              </div>
            `,
          }, // Actions
        ],
      });

      // Attach event listeners to the table
      $('#approvals-table').on('click', '.btn-view', function () {
        const rowData = dataTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          viewApproval(rowData.docs_type, rowData.approval_id); // Pass docs_type and approval_id
        }
      });
    }
  });
});

// Helper function to format dates
function formatDate(date) {
  if (!date) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true };
  return new Intl.DateTimeFormat('en-US', options).format(new Date(date));
}

// Function to view approval details based on docs_type
function viewApproval(docsType, approvalId) {
  let url = '';
  switch (docsType) {
    case 1:
    case 2:
      url = `/cash-request/${approvalId}`;
      break;
    case 3:
    case 4:
      url = `/clear-invoice/${approvalId}`;
      break;
    case 5:
      url = `/statements/${approvalId}`;
      break;
    case 6:
      url = `/cancellations/${approvalId}`;
      break;
    case 7:
      url = `/cancellations/${approvalId}`;
      break;
    case 8:
      url = `/evaluations/${approvalId}`;
      break;
    default:
      console.error('Unknown docs_type:', docsType);
      return;
  }
  window.location.href = url; // Navigate to the URL in the same tab
}
</script>