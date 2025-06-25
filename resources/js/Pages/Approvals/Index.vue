<template>
  <Main>
    <Head :title="'Approvals'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Approval Notifications</h4>
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
                <th>Reference</th>
                <th>Requested Date</th>
                <th>Request Type</th>
                <th>Status</th>
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
import { onMounted, nextTick } from 'vue';

const props = defineProps({
  approvals: {
    type: [Array, Object],
    required: true,
    default: () => [],
  }
});

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
                case 1: return '<span class="badge bg-info">To Check</span>';
                case 2: return '<span class="badge bg-primary">To Acknowledge</span>';
                case 3: return '<span class="badge bg-success">To Approve</span>';
                case 4: return '<span class="badge bg-warning">To Receive</span>';
                case 5: return '<span class="badge bg-secondary">To Authorize</span>';
                case 6: return '<span class="badge bg-danger">To Reject</span>';
                case 7: return '<span class="badge bg-dark">To Review</span>';
                default: return '<span class="badge bg-secondary">Unknown</span>';
              }
            }
          }, // Render status_type with badges
          {
            data: 'status',
            render: (data) => {
              if (data === 0) return '<span class="badge bg-warning">Pending</span>';
              if (data === 1) return '<span class="badge bg-success">Approved</span>';
              return '<span class="badge bg-danger">Rejected</span>';
            }
          }, // Status with badge
          {
            data: null,
            render: (data) => `
              <div class="btn-group">
                <button class="btn btn-primary btn-sm btn-view">Open</button>
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
