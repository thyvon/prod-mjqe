<script setup>
import { onMounted, nextTick } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define the props using defineProps()
const props = defineProps({
  cashRequests: {
    type: Array,
    required: true,
  },
});

// Edit a cash request (navigate to the edit page)
const editCashRequest = (rowData) => {
  if (rowData && rowData.id) {
    Inertia.visit(`/cash-request/${rowData.id}/edit`);
  } else {
    console.error('Invalid row data:', rowData);
  }
};

// View a cash request
const showCashRequest = (rowData) => {
  if (rowData && rowData.id) {
    Inertia.visit(route('cash-request.show', rowData.id));
  } else {
    console.error('Invalid row data:', rowData);
  }
};

// Function to delete a cash request
const deleteCashRequest = async (cashRequestId) => {
  swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this cash request!',
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
        await Inertia.delete(route('cash-request.destroy', cashRequestId));
        swal('Deleted!', 'Cash request has been deleted.', 'success').then(() => {
          Inertia.visit(route('cash-request.index'));
        });
      } catch (error) {
        swal('Error!', 'Failed to delete cash request. Please try again.', 'error');
      }
    }
  });
};

// Helper function to format dates
const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};

// Initialize DataTable after DOM is updated
onMounted(() => {
  nextTick(() => {
    const table = $('#cash-request');
    if (table.length) {
      console.log('Initializing DataTable');
      const dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: props.cashRequests,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'ref_no' },
          { data: 'description' },
          { data: 'request_type' },
          { data: 'request_by' },
          { data: 'campus' },
          { data: 'currency' },
          { data: 'amount' },
          { data: 'remark' },
          { data: 'request_date', render: (data) => formatDate(data) },
          {
            data: null,
            render: () => `
              <div class="btn-group">
                <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> View</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners
      $('#cash-request')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) editCashRequest(rowData);
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteCashRequest(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) showCashRequest(rowData);
        });

      // Handle actions inside child rows (responsive details)
      $('#cash-request').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) editCashRequest(rowData);
      });

      $('#cash-request').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteCashRequest(rowData.id);
      });

      $('#cash-request').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) showCashRequest(rowData);
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Cash Request'" />

    <div class="panel panel-inverse">
        <div class="panel-heading">
          <h4 class="panel-title">Cash Request</h4>
          <div class="panel-heading-btn">
            <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
          </div>
        </div>
      <div class="panel-body">
        <!-- Create New Cash Request Button -->
        <Link href="/cash-request/create" class="btn btn-primary mb-4">Create New</Link>

        <!-- Cash Request Table -->
        <table id="cash-request" class="table table-bordered align-middle text-nowrap" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Ref No.</th>
              <th>Description</th>
              <th>Type</th>
              <th>Request By</th>
              <th>Campus</th>
              <th>Currency</th>
              <th>Amount</th>
              <th>Remark</th>
              <th>Request Date</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </Main>
</template>
