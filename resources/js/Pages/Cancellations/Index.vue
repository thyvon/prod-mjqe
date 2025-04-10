<script setup>
import { onMounted, nextTick, ref } from 'vue';
import Main from '@/Layouts/Main.vue';
import CancellationModal from './CancellationModal.vue';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';

// Props
const props = defineProps({
  cancellations: Array,
});

// Local state
let dataTableInstance;
const isEdit = ref(false);
const selectedCancellation = ref(null);

// Methods
const openCreateModal = () => {
  isEdit.value = false;
  selectedCancellation.value = null;
  window.dispatchEvent(new CustomEvent('open-create-modal', { detail: { prId: 0, docs: 1} }));
  // Dispatch event to open the modal
};

const openEditModal = (cancellation) => {
  axios.get(`/cancellations/${cancellation.id}/edit`)
    .then(response => window.dispatchEvent(new CustomEvent('open-edit-modal', { detail: response.data.cancellation })))
    .catch(() => toastr.error('Failed to fetch cancellation details.'));
};

const deleteCancellation = async (id) => {
  const confirmResult = await swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this cancellation!',
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
  });

  if (!confirmResult) {
    return;
  }

  try {
    await Inertia.delete(`/cancellations/${id}`, {
      preserveState: true,
      onSuccess: () => {
        const rowIndex = dataTableInstance.row((idx, data) => data.id === id).index();
        if (rowIndex !== undefined) {
          dataTableInstance.row(rowIndex).remove().draw();
        }
        swal('Deleted!', 'Cancellation has been deleted.', 'success', { timer: 2000 });
      },
      onError: () => {
        swal('Error!', 'Failed to delete cancellation. Please try again.', 'error');
      },
    });
  } catch (error) {
    console.error('Error deleting cancellation:', error);
  }
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#cancellations-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: props.cancellations,
        columns: [
          { 
            data: null, 
            render: (data, type, row, meta) => meta.row + 1 // Render sequence number
          },
          { data: 'cancellation_no' },
          { data: 'cancellation_date', render: (data) => moment(data).format('MMM DD, YYYY') },
          { data: 'cancellation_reason' },
          { data: 'user.name' },
          {
            data: null,
            render: (data, type, row, meta) => `
              <div class="btn-group">
                <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-view"><i class="fas fa-eye"></i> View</a></li>
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                </ul>
              </div>
            `,
            className: 'text-center',
          },
        ],
        drawCallback: function(settings) {
          // Recalculate sequence numbers
          const api = this.api();
          api.rows().every(function(rowIdx, tableLoop, rowLoop) {
            const row = this.node();
            $(row).find('td:first').html(rowIdx + 1);
          });
        },
      });

      // Attach event listeners
      $('#cancellations-table')
        .on('click', '.btn-view', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/cancellations/${rowData.id}`;
          }
        })
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            openEditModal(rowData); // Call the function to open the edit modal
          }
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            deleteCancellation(rowData.id);
          }
        });
    }
  });
});

// Handle updates from the modal
window.addEventListener('cancellation-saved', (event) => {
  const cancellation = event.detail;
  const existingRowIndex = dataTableInstance.row((idx, data) => data.id === cancellation.id).index();

  if (existingRowIndex !== undefined) {
    // Update the existing row
    dataTableInstance.row(existingRowIndex).data(cancellation).draw();
    swal('Success!', 'Cancellation updated successfully.', 'success');
  } else {
    // Add a new row
    dataTableInstance.row.add(cancellation).draw();
    swal('Success!', 'Cancellation created successfully.', 'success');
  }
});
</script>

<template>
  <Main>
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Cancellations</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <!-- Create New Button -->
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Create New</button>
        <div class="table-responsive">
          <table id="cancellations-table" class="table table-bordered align-middle" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Cancellation No</th>
                <th>Date</th>
                <th style="min-width: 500px;">Reason</th>
                <th>Cancelled by</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Include the CancellationModal component -->
    <CancellationModal />
  </Main>
</template>

<style scoped>
</style>
