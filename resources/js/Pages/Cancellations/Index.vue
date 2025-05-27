<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Define the props using defineProps()
const props = defineProps({
  cancellations: {
    type: Array,
    required: true,
  },
  users: Array,
  currentUser: Object,

});
const goBack = () => {
  window.history.back();
};


// Cancellation Form Start
let modalInstance = null;
const isEdit = ref(false);
const cancellationForm = reactive({
  id: null,
  cancellation_date: '',
  cancellation_reason: '',
  cancellation_docs: '',
  cancellation_by: '',
  pr_po_id: null,
  approved_by: null,
  authorized_by: null,
  items: [],
});
const validationErrors = ref({});

const openEditModal = async (rowData) => {
  try {
    // Make an API request to fetch the cancellation data from the backend
    const response = await axios.get(`/cancellations/${rowData.id}/edit`);
    const cancellation = response.data.cancellation;
    const approvals = response.data.approvals;

    // Populate the form with the fetched data
    isEdit.value = true;
    Object.assign(cancellationForm, {
      id: cancellation.id,
      cancellation_date: cancellation.cancellation_date,
      cancellation_reason: cancellation.cancellation_reason,  // Make sure this is correctly populated
      cancellation_docs: cancellation.cancellation_docs,
      cancellation_by: cancellation.cancellation_by,
      approved_by: approvals.find(a => a.status_type === 3)?.user_id || null, // Set approved_by dynamically
      authorized_by: approvals.find(a => a.status_type === 5)?.user_id || null, // Set authorized_by dynamically
      items: cancellation.items.map((item) => ({
        id: item.id,
        name: `${item.purchase_request_item?.product?.product_description || ''} - ${item.purchase_request_item?.remark || ''}` || item.purchase_order_item?.product?.product_description || null,
        pr_number: item.purchase_request_item?.purchase_request?.pr_number || null,
        po_number: item.purchase_order_item?.purchase_order?.po_number || null,
        sku: item.purchase_request_item?.product?.sku || item.purchase_order_item?.product?.sku || null,
        qty: item.qty,
        purchase_request_id: item.purchase_request_id,
        purchase_request_item_id: item.purchase_request_item_id,
        purchase_order_id: item.purchase_order_id,
        purchase_order_item_id: item.purchase_order_item_id,
        cancellation_reason: item.cancellation_reason,
      })),
    });

    // Refresh the cancellation-items-table
    if (cancellationItemsTableInstance) {
      cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
    }

      if (modalInstance) {
      modalInstance.show(); // Show the modal
      nextTick(() => {
        initializeSelect2(); // Reinitialize select2 for the modal
        initializeSummernote(); // Initialize Summernote for the cancellation reason
      });
    }
  } catch (error) {
    console.error('Failed to fetch cancellation data:', error);
    toastr.error('Failed to load cancellation data. Please try again.', 'Error');
  }
};


let addItemModalInstance = null;
const prItems = ref([]);
const poItems = ref([]);
let prItemsTableInstance;
let poItemsTableInstance;
let cancellationItemsTableInstance;

const filterPrNumber = ref(''); // Initialize as an empty string
const fetchPrItems = async (purchaseRequestId = null) => {
  try {
    const response = await axios.get(route('pr-items-cancellation')); // Use the named route
    let items = response.data;

    // Filter items by purchase_request_id if provided
    if (purchaseRequestId) {
      items = items.filter(item => item.purchase_request.id === purchaseRequestId);
    }

    prItems.value = items; // Assign the filtered data to `prItems`
    console.log('Fetched PR Items:', prItems.value);
  } catch (error) {
    console.error('Failed to fetch PR items:', error); // Log any errors
  }
};

// PO
const filterPoNumber = ref(''); // Initialize as an empty string
const fetchPoItems = async (purchaseOrderId = null) => {
  try {
    const response = await axios.get(route('po-items-cancellation')); // Use the named route
    let items = response.data;

    // Filter items by purchase_request_id if provided
    if (purchaseOrderId) {
      items = items.filter(item => item.purchase_order.id === purchaseOrderId);
    }

    poItems.value = items; // Assign the filtered data to `prItems`
    console.log('Fetched PO Items:', prItems.value);
  } catch (error) {
    console.error('Failed to fetch PR items:', error); // Log any errors
  }
};

// Watch for changes in filterPrNumber and reinitialize the PR items table
watch(filterPrNumber, (newVal) => {
  const filteredPrItems = prItems.value.filter((item) =>
    newVal ? item.purchase_request.id === newVal : true
  );
  if (prItemsTableInstance) {
    prItemsTableInstance.clear().rows.add(filteredPrItems).draw();
  }
});

watch(filterPoNumber, (newVal) => {
  const filteredPoItems = poItems.value.filter((item) =>
    newVal ? item.purchase_order.id === newVal : true
  );
  if (poItemsTableInstance) {
    poItemsTableInstance.clear().rows.add(filteredPoItems).draw();
  }
});

const initializePrItemsTable = () => {
  nextTick(() => {
    const table = $('#pr-items-table');
    if (table.length) {
      // Check if the DataTable is already initialized
      if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy(); // Destroy the existing DataTable instance
        table.empty(); // Clear the table content to avoid duplication
      }
      prItemsTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: prItems.value,
        columns: [
          { data: 'purchase_request.pr_number', title: 'PR Number' },
          { data: 'product.sku', title: 'SKU' },
          { data: 'product.product_description', title: 'Product Description' },
          { data: 'qty_pending', title: 'Pending Qty' }, // Ensure this matches the backend field
          { data: 'unit_price', title: 'Unit Price', render: (data) => `$${parseFloat(data).toFixed(2)}` },
          { data: 'total_price', title: 'Total Price', render: (data) => `$${parseFloat(data).toFixed(2)}` },
          {
            data: null,
            title: 'Actions',
            render: (data) => `
              <button class="btn btn-primary btn-sm btn-select">Select</button>
            `,
            className: 'text-center',
          },
        ],
      });

      // Attach event listener for the "Select" button
      $('#pr-items-table').on('click', '.btn-select', function () {
        const rowData = prItemsTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          selectPrItem(rowData);
        }
      });
    }
  });
};

const initializePoItemsTable = () => {
  nextTick(() => {
    const table = $('#po-items-table');
    if (table.length) {
      // Check if the DataTable is already initialized
      if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy(); // Destroy the existing DataTable instance
        table.empty(); // Clear the table content to avoid duplication
      }
      poItemsTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: poItems.value,
        columns: [
          { data: 'purchase_order.po_number', title: 'PO Number' },
          { data: 'product.sku', title: 'SKU' },
          { data: 'product.product_description', title: 'Product Description' },
          { data: 'pending', title: 'Pending Qty' }, // Ensure this matches the backend field
          { data: 'unit_price', title: 'Unit Price', render: (data) => `$${parseFloat(data).toFixed(2)}` },
          { data: 'discount', title: 'Discount', render: (data) => `$${parseFloat(data).toFixed(2)}` },
          { data: 'vat', title: 'VAT', render: (data) => `${parseFloat(data).toFixed(2)}%` },
          { data: 'grand_total', title: 'Total Price', render: (data) => `$${parseFloat(data).toFixed(2)}` },
          {
            data: null,
            title: 'Actions',
            render: (data) => `
              <button class="btn btn-primary btn-sm btn-select">Select</button>
            `,
            className: 'text-center',
          },
        ],
      });

      // Attach event listener for the "Select" button
      $('#po-items-table').on('click', '.btn-select', function () {
        const rowData = poItemsTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          selectPoItem(rowData);
        }
      });
    }
  });
};

const initializeCancellationItemsTable = () => {
  nextTick(() => {
    const table = $('#cancellation-items-table');
    if (table.length) {
      cancellationItemsTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: cancellationForm.items,
        columns: [
          { data: 'sku', title: 'Item Code' },
          { data: 'name', title: 'Description' },
          {
            data: 'qty',
            title: 'Cancel Qty',
            render: (data, type, row, meta) => {
              return `
                <input type="number" class="form-control qty-input" 
                       data-index="${meta.row}" 
                       value="${data}" 
                       min="1" />
              `;
            },
          },
          {
            data: 'cancellation_reason',
            title: 'Remark',
            render: (data, type, row, meta) => {
              return `
                <textarea class="form-control cancellation-reason-input" 
                          data-index="${meta.row}" 
                          rows="1">${data || ''}</textarea>
              `;
            },
          },
          {
            data: null,
            title: 'Actions',
            render: (data, type, row, meta) => `
              <button type="button" class="btn btn-danger btn-sm btn-remove">Remove</button>
            `,
            className: 'text-center',
          },
        ],
      });

      // Attach event listener for changes in the cancellation_reason field
      $('#cancellation-items-table').on('input', '.cancellation-reason-input', function () {
        const index = $(this).data('index');
        const value = $(this).val();
        if (cancellationForm.items[index]) {
          cancellationForm.items[index].cancellation_reason = value; // Update the reactive data
        }
      });

      // Attach event listener for changes in the qty field
      $('#cancellation-items-table').on('input', '.qty-input', function () {
        const index = $(this).data('index');
        const value = parseFloat($(this).val());
        if (cancellationForm.items[index]) {
          cancellationForm.items[index].qty = value; // Update the reactive data
        }
      });

      // Attach event listener for the "Remove" button
      $('#cancellation-items-table').on('click', '.btn-remove', function () {
        const rowData = cancellationItemsTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          removeItemCancel(rowData);
        }
      });

    }
  });
};

const selectPrItem = (item) => {
  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_request_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  cancellationForm.items.push({
    name: item.product.product_description,
    qty: item.qty_pending,
    purchase_request_id: item.purchase_request.id,
    pr_number: item.purchase_request.pr_number,
    po_number: null,
    sku: item.product.sku,
    purchase_request_item_id: item.id,
    cancellation_reason: cancellationForm.cancellation_reason || '', // Default from main form
  });

  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  toastr.success('PR item added successfully.', 'Success');
};

const selectPoItem = (item) => {
  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_order_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  const newItem = {
    name: item.product.product_description,
    qty: item.pending,
    purchase_request_id: item.pr_id > 0 ? item.pr_id : null,
    purchase_order_id: item.purchase_order.id,
    pr_number: item.purchase_request?.pr_number || '',
    po_number: item.purchase_order?.po_number || '',
    sku: item.product.sku,
    purchase_order_item_id: item.id,
    purchase_request_item_id: item.pr_item_id > 0 ? item.pr_item_id : null,
    cancellation_reason: cancellationForm.cancellation_reason || '', // Default from main form
  };

  cancellationForm.items.push(newItem);

  console.log('✅ New cancellation item added:', newItem);
  console.log('📦 Updated cancellationForm.items:', cancellationForm.items);

  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  toastr.success('PO item added successfully.', 'Success');
};

const selectAllPrItems = () => {

  let addedCount = 0;

  prItems.value.forEach((item) => {
    const isDuplicate = cancellationForm.items.some(
      (existingItem) => existingItem.purchase_request_item_id === item.id
    );

    if (!isDuplicate) {
      cancellationForm.items.push({
        name: item.product.product_description,
        qty: item.qty_pending,
        purchase_request_id: item.purchase_request.id,
        pr_number: item.purchase_request.pr_number,
        sku: item.product.sku,
        purchase_request_item_id: item.id,
        cancellation_reason: cancellationForm.cancellation_reason || '', // Default from main form
      });
      addedCount++;
    }
  });

  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  if (addedCount > 0) {
    toastr.success(`${addedCount} PR items added successfully.`, 'Success');
  } else {
    toastr.warning('No new PR items were added.', 'Warning');
  }
};

const selectAllPoItems = () => {

let addedCount = 0;

poItems.value.forEach((item) => {
  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_order_item_id === item.id
  );

  if (!isDuplicate) {
    cancellationForm.items.push({
      name: item.product.product_description,
      qty: item.pending,
      purchase_request_id: item.pr_id,
      purchase_order_id: item.purchase_order.id,
      pr_number: item.purchase_request.pr_number,
      po_number: item.purchase_order.po_number,
      sku: item.product.sku,
      purchase_request_item_id: item.pr_item_id,
      cancellation_reason: cancellationForm.cancellation_reason || '', // Default from main form
    });
    addedCount++;
  }
});

if (cancellationItemsTableInstance) {
  cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
}

if (addedCount > 0) {
  toastr.success(`${addedCount} PO items added successfully.`, 'Success');
} else {
  toastr.warning('No new PR items were added.', 'Warning');
}
};

const removeItemCancel = (item) => {
  let index = -1;

  // Check if it's a PR item
  if (item.purchase_request_item_id) {
    index = cancellationForm.items.findIndex(
      (i) =>
        i.name === item.name &&
        i.purchase_request_item_id === item.purchase_request_item_id
    );
  }

  // Check if it's a PO item
  else if (item.purchase_order_item_id) {
    index = cancellationForm.items.findIndex(
      (i) =>
        i.name === item.name &&
        i.purchase_order_item_id === item.purchase_order_item_id
    );
  }

  // Remove the item if found
  if (index !== -1) {
    cancellationForm.items.splice(index, 1);
  }

  // Update the DataTable
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }
};


const saveCancellation = async () => {
  try {
    // Ensure all qty fields are decimals
    cancellationForm.items = cancellationForm.items.map(item => ({
      ...item,
      qty: parseFloat(item.qty).toFixed(8), // Convert qty to a decimal with 8 places
      cancellation_reason: item.cancellation_reason || '', // Ensure cancellation_reason is included
    }));

    const payload = {
      ...cancellationForm,
      pr_po_id: cancellationForm.pr_po_id, // Include pr_po_id in the payload
      approved_by: cancellationForm.approved_by,
      authorized_by: cancellationForm.authorized_by,
      items: cancellationForm.items,
    };

    const url = isEdit.value ? `/cancellations/${cancellationForm.id}` : '/cancellations';
    const method = isEdit.value ? 'put' : 'post';
    const response = await axios[method](url, cancellationForm);
    window.dispatchEvent(new CustomEvent('cancellation-saved', { detail: response.data.cancellation }));
    swal('Success!', `Cancellation ${isEdit.value ? 'updated' : 'created'} successfully.`, 'success');
    console.log('Cancellation saved:', response.data.cancellation);

    // Reset the form after saving
    Object.assign(cancellationForm, {
      id: null,
      cancellation_date: '',
      cancellation_reason: '',
      cancellation_docs: '',
      cancellation_by: '',
      pr_po_id: null,
      approved_by: null,
      authorized_by: null,
      items: [], // Clear items
    });
    validationErrors.value = {};

    // Clear the DataTable for items
    if (cancellationItemsTableInstance) {
      cancellationItemsTableInstance.clear().draw();
    }

    modalInstance?.hide();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      validationErrors.value = error.response.data.errors;
      swal('Error!', 'Validation failed. Please check the form.', 'error');
    } else {
      swal('Error!', 'An unexpected error occurred. Please try again.', 'error');
    }
  }
};

const openAddItemModal = () => {
  if (cancellationForm.cancellation_docs === 1) {
    fetchPrItems(cancellationForm.pr_po_id).then(() => {
      initializePrItemsTable();
      addItemModalInstance?.show();
    });
  } else if (cancellationForm.cancellation_docs === 2) {
    fetchPoItems(cancellationForm.pr_po_id).then(() => {
      initializePoItemsTable();
      addItemModalInstance?.show();
    });
  }
};

const selectedPurchaseRequestId = computed(() => {
  // Replace this logic with how you determine the selected purchase request
  const selectedRequest = props.purchaseRequests.find(pr => pr.id === cancellationForm.pr_po_id);
  return selectedRequest ? selectedRequest.pr_number : '';
});

const initializeSelect2 = () => {
  nextTick(() => {
    setTimeout(() => {
      if ($('#approved_by').length) {
        if ($.fn.select2 && $('#approved_by').data('select2')) {
          $('#approved_by').select2('destroy'); // Destroy existing Select2 instance
        }

        // Initialize Select2 with dropdownParent set to the modal
        $('#approved_by').select2({
          placeholder: 'Select an approver',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#cancellationModal'), // Ensure dropdown is rendered inside the modal
        }).on('change', function () {
          // Sync the selected value with the reactive form
          cancellationForm.approved_by = $(this).val();
        });
      }

      if ($('#authorized_by').length) {
        if ($.fn.select2 && $('#authorized_by').data('select2')) {
          $('#authorized_by').select2('destroy'); // Destroy existing Select2 instance
        }

        // Initialize Select2 with dropdownParent set to the modal
        $('#authorized_by').select2({
          placeholder: 'Select an authorizer',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#cancellationModal'), // Ensure dropdown is rendered inside the modal
        }).on('change', function () {
          // Sync the selected value with the reactive form
          cancellationForm.authorized_by = $(this).val();
        });
      }
    }, 300);
  });
};

const initializeSummernote = () => {
  nextTick(() => {
    if ($('.summernote').length) {
      $('.summernote').summernote({
        placeholder: 'Purpose <br> Root Cause <br> Conclusion',
        height: "300",
        toolbar: [
          // Default Summernote toolbar configuration
          ['style', ['bold', 'italic', 'underline', 'clear']],
          ['font', ['strikethrough', 'superscript', 'subscript']],
          ['fontname', ['fontname']],
          ['fontsize', ['fontsize']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph', 'lineheight']], // Adding 'lineheight' to the para group
          ['insert', ['link', 'picture', 'video']],
          ['view', ['fullscreen', 'codeview', 'help']],
          ['table', ['table']],
          ['height', ['height']],
          ['mybutton', ['mybutton']],
          ['custom', ['undo', 'redo']],
          ['custom', ['clear']],
          ['custom', ['hr']],
          ['custom', ['print']],
          ['custom', ['fullscreen']],
        ],
        fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Tw Cen MT', 'Khmer OS Content'],
        fontNamesIgnoreCheck: ['Tw Cen MT', 'Khmer OS Content'], // bypass checking if fonts are installed
        callbacks: {
          onChange: function (contents) {
            cancellationForm.cancellation_reason = contents;
          },
        },
      });
      // Set the initial value from DB to the editor
      $('.summernote').summernote('code', cancellationForm.cancellation_reason);
      
    }
  });
};


// End of Cancellation Form

// Local state
let dataTableInstance;

// Helper function to format dates
const format = (value, type) => {
  if (type === 'date') {
    const options = { year: 'numeric', month: 'short', day: '2-digit' };
    const date = new Date(value);
    return date.toLocaleDateString('en-US', options);
  }
};

// Initialize DataTable
onMounted(() => {
initializeSelect2();
initializeSummernote(); // Initialize Summernote editor
  nextTick(() => {
    const table = $('#cancellations-table');
    if (table.length) {
      console.log('Initializing DataTable');
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        pageLength: 15,
        lengthMenu: [15, 25, 50, 100],
        data: props.cancellations,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 }, // Row number
          { data: 'cancellation_no' }, // Cancellation number
          { data: 'cancellation_docs', render: (data) => {
              if (data == 1) return '<span class="badge bg-primary">PR CANCEL</span>';
              if (data == 2) return '<span class="badge bg-secondary">PO CANCEL</span>';
              return '<span class="badge bg-light">Unknown</span>'; // Default case
            },
          },
          {
            data: null,
            render: function (data, type, row) {
              return row.cancellation_docs == 1 && row.purchase_request?.pr_number || row.purchase_order?.po_number || '-';
            },
          },
          { data: 'cancellation_date', render: (data) => format(data, 'date') }, // Cancellation date
          // { data: 'cancellation_reason' }, // Reason
          { data: 'user.name', defaultContent: 'N/A' }, // User who created the cancellation
          { data: 'status', render: (data) => {
              if (data === 3) return '<span class="badge bg-success">Approved</span>';
              if (data === 5) return '<span class="badge bg-success">Authorized</span>';
              if (data === -1) return '<span class="badge bg-danger">Rejected</span>';
              return '<span class="badge bg-warning">Pending</span>'; // Default case
            },
          },
          {
            data: null,
            render: () => `
              <div class="btn-group">
                <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> View</a></li>
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners to the main table
      $('#cancellations-table')
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/cancellations/${rowData.id}`;
          }
        })
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            openEditModal(rowData);
          }
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            deleteCancellation(rowData.id);
          }
        });
      // Handle actions inside child rows (responsive details)
      $('#cancellations-table').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          window.location.href = `/cancellations/${rowData.id}`;
        }
      });

      $('#cancellations-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          openEditModal(rowData);
        }
      });

      $('#cancellations-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          deleteCancellation(rowData.id);
        }
      });
    }
  });
  const cancellationModalElement = document.getElementById('cancellationModal');
  if (cancellationModalElement) {
    modalInstance = new bootstrap.Modal(cancellationModalElement);
  }
 

  const addItemModalElement = document.getElementById('addItemModal');
  if (addItemModalElement) {
    addItemModalInstance = new bootstrap.Modal(addItemModalElement);
  }

  const editItemModalElement = document.getElementById('editItemModal');
  if (editItemModalElement) {
    editItemModalInstance = new bootstrap.Modal(editItemModalElement);
  }

  initializeCancellationItemsTable();
});

// Function to delete a cancellation
const deleteCancellation = async (cancellationId) => {
  swal({
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
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/cancellations/${cancellationId}`);
        dataTableInstance.row((idx, data) => data.id === cancellationId).remove().draw();
        swal('Deleted!', 'Cancellation has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete cancellation. Please try again.', 'error');
      }
    }
  });
};
</script>

<template>
  <Main>
    <Head :title="'Cancellations'" />
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <button @click="goBack" class="btn btn-xs btn-secondary mr-1">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <h4 class="panel-title">Cancellations</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <!-- Cancellations Table -->
        <div class="table-responsive">
          <table id="cancellations-table" class="table table-bordered table-sm align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th style="width: 2%;">#</th>
                <th style="width: 8%;">Cancellation No.</th>
                <th style="width: 5%;">Cancellation Type</th>
                <th style="width: 10%;">Reference No.</th>
                <th style="width: 8%;">Date</th>
                <!-- <th style="width: 50%;">Reason</th> -->
                <th style="width: 5%;">Created By</th>
                <th style="width: 5%;">Status</th>
                <th style="width: 2%;">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
    

    <!-- Modals -->
    <div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cancellationModalLabel">{{ isEdit ? 'Edit Cancellation' : 'Create Cancellation' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCancellation">
              <div class="mb-3">
                <label for="cancellation_date" class="form-label">Cancellation Date</label>
                <input v-model="cancellationForm.cancellation_date" type="date" class="form-control" id="cancellation_date" required />
                <div v-if="validationErrors.cancellation_date" class="text-danger">{{ validationErrors.cancellation_date[0] }}</div>
              </div>
              <div class="mb-3">
                <label for="cancellation_reason" class="form-label">Reason</label>
                <textarea class="summernote" id="cancellation_reason" rows="3"></textarea>
                <div v-if="validationErrors.cancellation_reason" class="text-danger">
                  {{ validationErrors.cancellation_reason[0] }}
                </div>
              </div>

              <div class="d-flex justify-content-between mt-2">
                <button type="button" class="btn btn-success btn-sm" @click="openAddItemModal">
                  <i class="fas fa-plus-circle"></i>
                  {{ cancellationForm.cancellation_docs === 1 ? 'SELECT PR ITEMS' : 'SELECT PO ITEMS' }}
                </button>
              </div>
              <div class="panel panel-inverse border mt-3 p-3">
                <div class="panel-heading bg-warning text-white mb-2">
                  <h4 class="panel-title">ITEM TO CANCEL</h4>
                </div>
                <div class="table-responsive">
                  <table id="cancellation-items-table" class="table table-bordered table-sm border-secondary align-middle" width="100%"></table>
                </div>

                <div class="row mb-2">
                  <div class="col-6 border">
                    <div class="row">
                      <span class="text-center">Approved By</span>
                    </div>
                    <div class="col-sm-12">
                      <select v-model="cancellationForm.approved_by" class="form-select select2" id="approved_by">
                        <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                      </select>
                      <div v-if="validationErrors.approved_by" class="text-danger">{{ validationErrors.approved_by[0] }}</div>
                    </div>
                  </div>
                  <div v-if="cancellationForm.cancellation_docs == 1" class="col-6 border"> <!-- Updated condition to true -->
                    <div class="row">
                      <span class="text-center">Authorized By</span>
                    </div>
                    <div class="col-sm-12">
                      <select v-model="cancellationForm.authorized_by" class="form-select select2" id="authorized_by">
                        <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                      </select>
                      <div v-if="validationErrors.authorized_by" class="text-danger">{{ validationErrors.authorized_by[0] }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update' : 'Create' }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade bg-secondary" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addItemModalLabel">
              {{ cancellationForm.cancellation_docs === 1 ? 'Select PR ITEM' : 'Select PO ITEM' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table
                id="pr-items-table"
                v-show="cancellationForm.cancellation_docs === 1"
                class="table table-bordered border-secondary align-middle"
                width="100%"
              ></table>

              <table
                id="po-items-table"
                v-show="cancellationForm.cancellation_docs === 2"
                class="table table-bordered border-secondary align-middle"
                width="100%"
              ></table>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-primary"
              @click="cancellationForm.cancellation_docs === 1 ? selectAllPrItems() : selectAllPoItems()"
            >
              <i class="fas fa-check-double"></i> Cancel All
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Main>
</template>