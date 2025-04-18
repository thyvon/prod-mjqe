<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch} from 'vue';
import axios from 'axios';
import swal from 'sweetalert';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const isEdit = ref(false);
const cancellationForm = reactive({
  id: null,
  cancellation_date: '',
  cancellation_reason: '',
  cancellation_docs: '',
  cancellation_by: '',
  pr_po_id: null,
  items: [],
});

const validationErrors = ref({});
let modalInstance = null;

const newItem = reactive({ name: '', quantity: 1 });
let addItemModalInstance = null;

const prItems = ref([]);
const selectedPrItem = ref(null);
let prItemsTableInstance;
let cancellationItemsTableInstance;

const editItem = reactive({ name: '', qty: 1, purchase_request_id: '', sku: '', purchase_request_item_id: '', pr_number: '' });
let editItemModalInstance = null;

const poItems = ref([]);
let poItemsTableInstance = null;

const filterPrNumber = ref(''); // Initialize as an empty string
const filterPoNumber = ref(''); // Initialize as an empty string

const fetchPrItems = async () => {
  try {
    const response = await axios.get(route('pr-items-cancellation')); // Use the named route
    prItems.value = response.data; // Assign the fetched data to `prItems`
    console.log('Fetched PR Items:', prItems.value);
  } catch (error) {
    console.error('Failed to fetch PR items:', error); // Log any errors
  }
};

const fetchPoItems = async () => {
  try {
    const response = await axios.get(route('po-items-cancellation')); // Use the named route
    poItems.value = response.data; // Assign the fetched data to `poItems`
  } catch (error) {
    console.error('Failed to fetch PO items:', error); // Log any errors
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

// Watch for changes in filterPoNumber and reinitialize the PO items table
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
          { data: 'pending', title: 'Pending Qty' },
          { data: 'unit_price', title: 'Unit Price', render: (data) => `$${parseFloat(data).toFixed(2)}` },
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
          // { data: 'pr_number', title: 'PR Number' },
          // { data: 'po_number', title: 'PO Number' },
          { data: 'qty', title: 'Cancel Qty' },
          {
            data: 'cancellation_reason',
            title: 'Reason for Cancellation',
            render: (data, type, row, meta) => {
              // Use cancellationForm.cancellation_reason as the default value if data is empty
              const defaultReason = cancellationForm.cancellation_reason || '';
              return `
                <textarea class="form-control cancellation-reason-input" 
                          data-index="${meta.row}" 
                          rows="2">${data || defaultReason}</textarea>
              `;
            },
          },
          {
            data: null,
            title: 'Actions',
            render: (data, type, row, meta) => `
              <button type="button" class="btn btn-danger btn-sm btn-remove">Remove</button>
              <button type="button" class="btn btn-warning btn-sm btn-edit">Edit</button>
            `,
            className: 'text-center',
          },
        ],
      });

      // Attach event listener for the "Remove" button
      $('#cancellation-items-table').on('click', '.btn-remove', function () {
        const rowData = cancellationItemsTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          removeItem(rowData);
        }
      });

      // Attach event listener for the "Edit" button
      $('#cancellation-items-table').on('click', '.btn-edit', function () {
        const rowData = cancellationItemsTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          openEditItemModal(rowData);
        }
      });
    }
  });
};

const selectPrItem = (item) => {
  // Prevent adding PR items if PO items already exist
  if (cancellationForm.items.some((existingItem) => existingItem.purchase_order_item_id)) {
    toastr.warning('You cannot add PR items when PO items are already added.', 'Warning');
    return;
  }

  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_request_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  cancellationForm.items.push({
    name: item.product.product_description,
    qty: item.qty,
    purchase_request_id: item.purchase_request.id,
    pr_number: item.purchase_request.pr_number,
    po_number: null,
    sku: item.product.sku,
    purchase_request_item_id: item.id,
    cancellation_reason: '',
  });

  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  toastr.success('PR item added successfully.', 'Success');
};

const selectPoItem = (item) => {
  // Prevent adding PO items if PR items already exist
  if (cancellationForm.items.some((existingItem) => existingItem.purchase_request_item_id)) {
    toastr.warning('You cannot add PO items when PR items are already added.', 'Warning');
    return;
  }

  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_order_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  cancellationForm.items.push({
    name: item.product.product_description,
    qty: item.qty,
    purchase_order_id: item.purchase_order.id,
    po_number: item.purchase_order.po_number,
    pr_number: item.purchase_request.pr_number,
    sku: item.product.sku,
    purchase_order_item_id: item.id,
    cancellation_reason: '',
  });

  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  toastr.success('PO item added successfully.', 'Success');
};

const selectAllPrItems = () => {
  // Prevent adding PR items if PO items already exist
  if (cancellationForm.items.some((existingItem) => existingItem.purchase_order_item_id)) {
    toastr.warning('You cannot add PR items when PO items are already added.', 'Warning');
    return;
  }

  let addedCount = 0;

  prItems.value.forEach((item) => {
    const isDuplicate = cancellationForm.items.some(
      (existingItem) => existingItem.purchase_request_item_id === item.id
    );

    if (!isDuplicate) {
      cancellationForm.items.push({
        name: item.product.product_description,
        qty: item.qty,
        purchase_request_id: item.purchase_request.id,
        pr_number: item.purchase_request.pr_number,
        sku: item.product.sku,
        purchase_request_item_id: item.id,
        cancellation_reason: '',
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
  // Prevent adding PO items if PR items already exist
  if (cancellationForm.items.some((existingItem) => existingItem.purchase_request_item_id)) {
    toastr.warning('You cannot add PO items when PR items are already added.', 'Warning');
    return;
  }

  let addedCount = 0;

  poItems.value.forEach((item) => {
    const isDuplicate = cancellationForm.items.some(
      (existingItem) => existingItem.purchase_order_item_id === item.id
    );

    if (!isDuplicate) {
      cancellationForm.items.push({
        name: item.product.product_description,
        qty: item.qty,
        purchase_order_id: item.purchase_order.id,
        po_number: item.purchase_order.po_number,
        pr_number: item.purchase_request.pr_number,
        sku: item.product.sku,
        purchase_order_item_id: item.id,
        cancellation_reason: '',
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
    toastr.warning('No new PO items were added.', 'Warning');
  }
};

const removeItem = (item) => {
  const index = cancellationForm.items.findIndex((i) => i.name === item.name && i.purchase_request_item_id === item.purchase_request_item_id);
  if (index !== -1) {
    cancellationForm.items.splice(index, 1);
  }

  // Update the DataTable with the modified items array
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }
};

const openCreateModal = () => {
  isEdit.value = false;
  Object.assign(cancellationForm, {
    id: null,
    cancellation_date: new Date().toISOString().split('T')[0],
    cancellation_reason: '',
    cancellation_docs: cancellationForm.cancellation_docs,
    cancellation_by: '',
    items: [],
  });
  validationErrors.value = {};
  if (modalInstance) {
    modalInstance.show(); // Show the modal
  }
};

const openEditModal = (cancellation) => {
  isEdit.value = true;

  // Map the cancellation data to the form
  Object.assign(cancellationForm, {
    id: cancellation.id, // Ensure the correct ID is assigned
    cancellation_date: cancellation.cancellation_date,
    cancellation_reason: cancellation.cancellation_reason,
    cancellation_docs: cancellation.cancellation_docs,
    cancellation_by: cancellation.cancellation_by,
    items: cancellation.items.map((item) => ({
      id: item.id,
      name: item.purchase_request_item?.product?.product_description || item.purchase_order_item?.product?.product_description || null,
      pr_number: item.purchase_request_item?.purchase_request?.pr_number || item.purchase_order_item?.purchase_request?.pr_number || null,
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

  validationErrors.value = {};

  // Refresh the cancellation-items-table
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  if (modalInstance) {
    modalInstance.show(); // Show the modal
  }
};

const saveCancellation = async () => {
  try {
    // Ensure all qty fields are decimals
    cancellationForm.items = cancellationForm.items.map(item => ({
      ...item,
      qty: parseFloat(item.qty).toFixed(8), // Convert qty to a decimal with 8 places
    }));

    const url = isEdit.value ? `/cancellations/${cancellationForm.id}` : '/cancellations';
    const method = isEdit.value ? 'put' : 'post';
    const response = await axios[method](url, cancellationForm);
    window.dispatchEvent(new CustomEvent('cancellation-saved', { detail: response.data.cancellation }));
    swal('Success!', `Cancellation ${isEdit.value ? 'updated' : 'created'} successfully.`, 'success');

    // Reset the form after saving
    Object.assign(cancellationForm, {
      id: null,
      cancellation_date: '',
      cancellation_reason: '',
      cancellation_docs: '',
      cancellation_by: '',
      pr_po_id: null,
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
  Object.assign(newItem, { name: '', quantity: 1 });
  if (addItemModalInstance) {
    addItemModalInstance.show();
  }
};

const openEditItemModal = (item) => {
  Object.assign(editItem, item, { pr_number: item.pr_number, po_number: item.po_number, cancellation_reason: item.cancellation_reason }); // Ensure pr_number and po_number are set
  if (editItemModalInstance) {
    editItemModalInstance.show();
  }
};

const confirmEditItem = () => {
  const index = cancellationForm.items.findIndex((i) => i.purchase_request_item_id === editItem.purchase_request_item_id);
  if (index !== -1) {
    cancellationForm.items[index] = { ...editItem };
  }

  // Update the DataTable with the modified items array
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  if (editItemModalInstance) {
    editItemModalInstance.hide();
  }
};

const openPoItemModal = () => {
  const poNumber = filterPoNumber.value ? String(filterPoNumber.value).trim() : '';
  if (!poNumber) {
    toastr.warning('Please input a PO Number to select items.', 'Warning');
    return;
  }
  const poItemModalElement = document.getElementById('poItemModal');
  if (poItemModalElement) {
    const poItemModalInstance = new bootstrap.Modal(poItemModalElement); // Initialize Bootstrap modal
    poItemModalInstance.show(); // Show the modal
  } else {
    console.error('PO Item Modal element not found.');
  }
};

// Computed properties to get unique PR and PO numbers
const uniquePrNumbers = computed(() => {
  return prItems.value.map(item => ({
    id: item.purchase_request.id,
    number: item.purchase_request.pr_number,
  })).filter((value, index, self) =>
    index === self.findIndex((t) => t.number === value.number)
  );
});

const uniquePoNumbers = computed(() => {
  return poItems.value.map(item => ({
    id: item.purchase_order.id,
    number: item.purchase_order.po_number,
  })).filter((value, index, self) =>
    index === self.findIndex((t) => t.number === value.number)
  );
});

onMounted(() => {
  const modalElement = document.getElementById('cancellationModal');
  if (modalElement) {
    modalInstance = new bootstrap.Modal(modalElement); // Initialize Bootstrap modal
    modalElement.addEventListener('hidden.bs.modal', () => {
      modalInstance.dispose(); // Dispose of the modal instance
      modalInstance = null; // Reset the modal instance
    });

    // Handle the "open-create-modal" event
    const handleOpenCreateModal = (event) => {
      const { prId, docs } = event.detail;
      cancellationForm.cancellation_docs = docs; // Set the cancellation_docs value
      cancellationForm.cancellation_date = new Date().toISOString().split('T')[0];
      filterPrNumber.value = prId; // Set the PR ID in the filter
      fetchPrItems().then(() => initializePrItemsTable()); // Fetch PR items and initialize the table
      openCreateModal(); // Open the create modal
    };

    // Handle the "open-edit-modal" event
    const handleOpenEditModal = (event) => {
      const cancellation = event.detail;
      fetchPrItems().then(() => initializePrItemsTable());
      openEditModal(cancellation); // Open the modal in edit mode
    };

    // Add event listeners
    window.addEventListener('open-create-modal', handleOpenCreateModal);
    window.addEventListener('open-edit-modal', handleOpenEditModal);

    modalElement.addEventListener('show.bs.modal', () => {
      fetchPoItems().then(() => initializePoItemsTable()); // Only fetch PO items here
    });
  }

  const addItemModalElement = document.getElementById('addItemModal');
  if (addItemModalElement) {
    addItemModalInstance = new bootstrap.Modal(addItemModalElement); // Initialize Bootstrap modal
  }

  const editItemModalElement = document.getElementById('editItemModal');
  if (editItemModalElement) {
    editItemModalInstance = new bootstrap.Modal(editItemModalElement); // Initialize Bootstrap modal
  }

  const poItemModalElement = document.getElementById('poItemModal');
  if (poItemModalElement) {
    poItemsTableInstance = new bootstrap.Modal(poItemModalElement); // Initialize Bootstrap modal for PO Items
  }

  initializeCancellationItemsTable();
});
</script>

<template>
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
              <textarea v-model="cancellationForm.cancellation_reason" class="form-control" id="cancellation_reason" rows="3"></textarea>
              <div v-if="validationErrors.cancellation_reason" class="text-danger">{{ validationErrors.cancellation_reason[0] }}</div>
            </div>
            <div class="mb-3">
              <label for="cancellation_docs" class="form-label">PO cancel or PR Cancel</label>
              <select v-model="cancellationForm.cancellation_docs" class="form-control" id="cancellation_docs">
                <option value="1">PR</option>
                <option value="2">PO</option>
              </select>
              <div v-if="validationErrors.cancellation_docs" class="text-danger">{{ validationErrors.cancellation_docs[0] }}</div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="filter-pr-number" class="form-label">Filter by PR Number</label>
                  <select
                    id="filter-pr-number"
                    class="form-select"
                    v-model="filterPrNumber"
                  >
                    <option value="">Select PR Number</option>
                    <option v-for="pr in uniquePrNumbers" :key="pr.id" :value="pr.id">
                      {{ pr.number }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="filter-po-number" class="form-label">Filter by PO Number</label>
                  <select
                    id="filter-po-number"
                    class="form-select"
                    v-model="filterPoNumber"
                  >
                    <option value="">Select PO</option>
                    <option v-for="po in uniquePoNumbers" :key="po.id" :value="po.id">
                      {{ po.number }}
                    </option>
                  </select>
                </div>
              </div>
              
            </div>
            <div class="d-flex justify-content-between mt-2">
              <button type="button" class="btn btn-success btn-sm" @click="openAddItemModal">Add PR</button>
              <button type="button" class="btn btn-success btn-sm" @click="openPoItemModal">Add PO</button>
            </div>
            <div class="mb-3">
              <label class="form-label">Items to Cancel</label>
              <div class="table-responsive">
                <table id="cancellation-items-table" class="table table-bordered border-secondary align-middle" width="100%"></table>
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
          <h5 class="modal-title" id="addItemModalLabel">Select PR ITEM</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="pr-items-table" class="table table-bordered border-secondary align-middle" width="100%"></table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" @click="selectAllPrItems">
            <i class="fas fa-check-double"></i> Cancel All
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add PO Item Modal -->
  <div
    class="modal fade bg-secondary"
    id="poItemModal"
    tabindex="-1"
    aria-labelledby="poItemModalLabel"
    aria-hidden="true"
    style="z-index: 1055;"
  >
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="poItemModalLabel">Select PO ITEM</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="po-items-table" class="table table-bordered border-secondary align-middle" width="100%"></table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" @click="selectAllPoItems">
            <i class="fas fa-check-double"></i> Add All
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Item Modal -->
  <div class="modal fade bg-secondary" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_item_name" class="form-label">Item Name</label>
            <input v-model="editItem.name" type="text" class="form-control" id="edit_item_name" readonly />
          </div>
          <div class="mb-3">
            <label for="edit_item_qty" class="form-label">Quantity</label>
            <input v-model="editItem.qty" type="number" class="form-control" id="edit_item_qty" min="1" />
          </div>
          <div class="mb-3">
            <label for="edit_item_cancellation_reason" class="form-label">Reason for Cancellation</label>
            <textarea v-model="editItem.cancellation_reason" class="form-control" id="edit_item_cancellation_reason" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label for="edit_item_sku" class="form-label">SKU</label>
            <input v-model="editItem.sku" type="text" class="form-control" id="edit_item_sku" readonly />
          </div>
          <div class="mb-3">
            <label for="edit_item_pr_number" class="form-label">PR Number</label>
            <input v-model="editItem.pr_number" type="text" class="form-control" id="edit_item_pr_number" readonly />
          </div>
          <div class="mb-3">
            <label for="edit_item_po_number" class="form-label">PO Number</label>
            <input v-model="editItem.po_number" type="text" class="form-control" id="edit_item_po_number" readonly />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="button" class="btn btn-primary" @click="confirmEditItem">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
