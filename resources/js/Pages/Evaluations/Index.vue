<template>
  <Main>
    <Head title="Evaluations List" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Evaluations</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <!-- Create New Evaluation Button -->
        <Link href="/evaluations/create" class="btn btn-primary mb-4 btn-sm">Create New</Link>

        <!-- Evaluations Table -->
        <div class="table-responsive">
          <table id="evaluations-table" class="table table-bordered table-sm align-middle" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Created At</th>
                <th>Approval Status</th>
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
import { ref, onMounted, nextTick } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  evaluations: Array,
});

let dataTableInstance;

const deleteEvaluation = async (evaluationId) => {
  const result = await swal({
    title: 'Are you sure?',
    text: 'You will not be able to recover this evaluation!',
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

  if (result) {
    try {
      await Inertia.delete(`/evaluations/${evaluationId}`);
      dataTableInstance.row((idx, data) => data.id === evaluationId).remove().draw();
      swal('Deleted!', 'Evaluation has been deleted.', 'success', { timer: 2000 });
    } catch (error) {
      swal('Error!', 'Failed to delete evaluation. Please try again.', 'error');
    }
  }
};

// Helper function to format dates
const formatDate = (value) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(value);
  return date.toLocaleDateString('en-US', options);
};

// Derive description from evaluation data
const getDescription = (evaluation) => {
  if (evaluation.products && evaluation.products.length > 0) {
    const product = props.products?.find(p => p.id === evaluation.products[0]);
    return product ? `${product.sku} - ${product.product_description}` : 'No products';
  }
  return evaluation.recommendation ? evaluation.recommendation.substring(0, 50) + '...' : 'No description';
};

// Initialize DataTable
onMounted(() => {
  nextTick(() => {
    const table = $('#evaluations-table');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: props.evaluations,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'reference' },
          { data: null, render: (data) => getDescription(data) },
          { data: 'created_at', render: (data) => formatDate(data) },
          {
            data: null,
            render: (data) => {
              const hasReviewed = data.reviewed_by;
              const hasAcknowledged = data.acknowledged_by;
              const hasApproved = data.approved_by;
              if (hasApproved) return '<span class="badge bg-success">Approved</span>';
              if (hasAcknowledged) return '<span class="badge bg-warning">Acknowledged</span>';
              if (hasReviewed) return '<span class="badge bg-primary">Reviewed</span>';
              return '<span class="badge bg-secondary">Pending</span>';
            },
            defaultContent: '<span class="badge bg-secondary">Pending</span>',
          },
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

      // Event listeners for main table
      $('#evaluations-table')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/evaluations/${rowData.id}/edit`;
          }
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deleteEvaluation(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/evaluations/${rowData.id}`;
          }
        });

      // Event listeners for responsive child rows
      $('#evaluations-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          window.location.href = `/evaluations/${rowData.id}/edit`;
        }
      });

      $('#evaluations-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deleteEvaluation(rowData.id);
      });

      $('#evaluations-table').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          window.location.href = `/evaluations/${rowData.id}`;
        }
      });
    }
  });
});
</script>