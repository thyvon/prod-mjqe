<script setup>
  import { onMounted, nextTick } from 'vue';
  import { Inertia } from '@inertiajs/inertia';
  import { Head, Link } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';

  // Props received from the controller
  const props = defineProps({
    purchaseRequests: {
      type: Array,
      required: true,
    },
    flash: {
      type: Object,
      required: false,
    },
  });

//   const notification = ref(null);

  // Initialize success message
  const successMessage = props.flash?.success || null;

  // Edit a purchase request (navigate to the edit page)
  const editPurchaseRequest = (rowData) => {
    if (rowData && rowData.id) {
      Inertia.visit(`/purchase-requests/${rowData.id}/edit`);
    } else {
      console.error('Invalid row data:', rowData);
    }
  };

  // View a purchase request
  const showPurchaseRequest = (rowData) => {
    if (rowData && rowData.id) {
      Inertia.visit(route('purchase-requests.show', rowData.id));
    } else {
      console.error('Invalid row data:', rowData);
    }
  };

  // Function to delete a purchase request
  const deletePurchaseRequest = async (purchaseRequestId) => {
    swal({
      title: 'Are you sure?',
      text: 'You will not be able to recover this purchase request!',
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
          await Inertia.delete(route('purchase-requests.destroy', purchaseRequestId));
          swal('Deleted!', 'Purchase request has been deleted.', 'success').then(() => {
            Inertia.visit(route('purchase-requests.index'));
          });
        } catch (error) {
          swal('Error!', 'Failed to delete purchase request. Please try again.', 'error');
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
      const table = $('#purchase-request');
      if (table.length) {
        console.log('Initializing DataTable');
        const dataTableInstance = table.DataTable({
          responsive: true,
          autoWidth: true,
          data: props.purchaseRequests,
          columns: [
            { data: null, render: (data, type, row, meta) => meta.row + 1 },
            { data: 'pr_number' },
            { data: 'request_date', render: (data) => formatDate(data) },
            { data: 'request_by.name' },
            { data: 'campus' },
            { data: 'division' },
            { data: 'department' },
            { data: 'purpose' },
            {
              data: 'is_urgent',
              render: (data) => {
                return data === 1 ? 'Yes' : ''; // If is_urgent is 1, show 'Yes', else show an empty string
              }
            },
            { data: 'total_amount' },
            { data: 'status' },
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
        $('#purchase-request')
          .on('click', '.btn-edit', function () {
            const rowData = dataTableInstance.row($(this).closest('tr')).data();
            if (rowData) editPurchaseRequest(rowData);
          })
          .on('click', '.btn-delete', function () {
            const rowData = dataTableInstance.row($(this).closest('tr')).data();
            if (rowData) deletePurchaseRequest(rowData.id);
          })
          .on('click', '.btn-show', function () {
            const rowData = dataTableInstance.row($(this).closest('tr')).data();
            if (rowData) showPurchaseRequest(rowData);
          });

        // Handle actions inside child rows (responsive details)
        $('#purchase-request').on('click', '.dtr-details .btn-edit', function () {
          const tr = $(this).closest('tr').prev();
          const rowData = dataTableInstance.row(tr).data();
          if (rowData) editPurchaseRequest(rowData);
        });

        $('#purchase-request').on('click', '.dtr-details .btn-delete', function () {
          const tr = $(this).closest('tr').prev();
          const rowData = dataTableInstance.row(tr).data();
          if (rowData) deletePurchaseRequest(rowData.id);
        });

        $('#purchase-request').on('click', '.dtr-details .btn-show', function () {
          const tr = $(this).closest('tr').prev();
          const rowData = dataTableInstance.row(tr).data();
          if (rowData) showPurchaseRequest(rowData);
        });
      }
    });
  });
  </script>


<template>
    <Main>
      <Head :title="'Purchase Request'" />

      <!-- Success Alert -->
      <!-- <div v-if="notification.value" class="alert" :class="notification.value.type" role="alert">
        {{ notification.value.message }}
      </div> -->

      <div class="panel panel-inverse">
        <div class="panel-heading">
          <h4 class="panel-title">Purchase Request
          </h4>
          <div class="panel-heading-btn">
            <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
            <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
          </div>
        </div>
        <div class="panel-body">
          <!-- Create New Purchase Request Button -->
          <Link href="/purchase-requests/create" class="btn btn-primary mb-4 btn-sm">Create New</Link>

          <!-- Purchase Request Table -->
          <table id="purchase-request" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>PR Number</th>
                <th>Request Date</th>
                <th>Request By</th>
                <th>Campus</th>
                <th>Division</th>
                <th>Department</th>
                <th>Purpose</th>
                <th>Urgent?</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </Main>
  </template>
