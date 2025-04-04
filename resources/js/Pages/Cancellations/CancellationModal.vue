<script setup>
import { ref, reactive, onMounted, nextTick, computed } from 'vue';
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

const applyPrFilter = () => {
  if (prItemsTableInstance) {
    const filterValue = String(filterPrNumber.value).trim(); // Ensure value is a string
    if (filterValue) {
      prItemsTableInstance.column(0).search(filterValue, false, false).draw(); // Filter by purchase_request.id
    } else {
      prItemsTableInstance.search('').draw(); // Clear the filter
    }
  }
};

const applyPoFilter = () => {
  if (poItemsTableInstance) {
    const filterValue = String(filterPoNumber.value).trim(); // Ensure value is a string
    if (filterValue) {
      poItemsTableInstance.column(0).search(filterValue, false, false).draw(); // Filter by purchase_order.id
    } else {
      poItemsTableInstance.search('').draw(); // Clear the filter
    }
  }
};

const fetchPrItems = async () => {
  try {
    const response = await axios.get(route('pr-items-cancellation')); // Use the named route
    prItems.value = response.data; // Assign the fetched data to `prItems`
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

const initializePrItemsTable = () => {
  nextTick(() => {
    const table = $('#pr-items-table');
    if (table.length) {
      prItemsTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: prItems.value,
        columns: [
          { data: 'purchase_request.pr_number', title: 'PR Number' },
          { data: 'product.sku', title: 'SKU' },
          { data: 'product.product_description', title: 'Product Description' },
          { data: 'qty', title: 'Quantity' },
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
      poItemsTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        data: poItems.value,
        columns: [
          { data: 'purchase_order.po_number', title: 'PO Number' },
          { data: 'product.sku', title: 'SKU' },
          { data: 'product.product_description', title: 'Product Description' },
          { data: 'qty', title: 'Quantity' },
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
          { data: 'name', title: 'Item Name' },
          { data: 'pr_number', title: 'PR Number' }, // Updated to use pr_number directly
          { data: 'po_number', title: 'PO Number' }, // Added PO Number column
          { data: 'qty', title: 'Quantity' },
          { data: 'sku', title: 'SKU' },
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
  // Check if the item is already in the cancellationForm.items array
  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_request_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  cancellationForm.items.push({
    name: item.product.product_description,
    qty: item.qty, // Updated to use 'qty'
    purchase_request_id: item.purchase_request.id, // Use ID
    pr_number: item.purchase_request.pr_number, // Add pr_number directly
    po_number: null, // Add pr_number directly
    sku: item.product.sku,
    purchase_request_item_id: item.id,
  });

  // Update the cancellation items DataTable
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  toastr.success('Item added successfully.', 'Success');
};

const selectPoItem = (item) => {
  console.log('Selected PO Item:', item); // Log the selected item for debugging

  // Ensure purchaseOrder exists before accessing its properties
  if (!item.purchase_order) {
    toastr.error('Invalid PO item data. Missing purchaseOrder details.', 'Error');
    return;
  }

  // Check if the item is already in the cancellationForm.items array
  const isDuplicate = cancellationForm.items.some(
    (existingItem) => existingItem.purchase_order_item_id === item.id
  );

  if (isDuplicate) {
    toastr.warning('This item is already added.', 'Warning');
    return;
  }

  // Push the selected PO item into the cancellationForm.items array
  cancellationForm.items.push({
    name: item.product.product_description, // Product description
    qty: item.qty, // Quantity
    purchase_order_id: item.purchase_order.id, // Purchase order ID
    po_number: item.purchase_order.po_number, // PO number
    pr_number: item.purchase_request.pr_number, // PR number
    sku: item.product.sku, // SKU
    purchase_order_item_id: item.id, // PO item ID
  });

  // Update the cancellation items DataTable
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
        qty: item.qty, // Updated to use 'qty'
        purchase_request_id: item.purchase_request.id, // Use ID
        pr_number: item.purchase_request.pr_number, // Add pr_number directly
        sku: item.product.sku,
        purchase_request_item_id: item.id,
      });
      addedCount++;
    }
  });

  // Update the cancellation items DataTable
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }

  if (addedCount > 0) {
    toastr.success(`${addedCount} items added successfully.`, 'Success');
  } else {
    toastr.warning('No new items were added.', 'Warning');
  }
};

const selectAllPoItems = () => {
  let addedCount = 0;

  poItems.value.forEach((item) => {
    const isDuplicate = cancellationForm.items.some(
      (existingItem) => existingItem.purchase_request_item_id === item.id
    );

    if (!isDuplicate) {
      cancellationForm.items.push({
        name: item.product.product_description,
        qty: item.qty,
        purchase_order_id: item.purchase_order.id,
        po_number: item.purchase_order.po_number,
        pr_number: item.purchase_request.pr_number, // PR number
        sku: item.product.sku,
        purchase_order_item_id: item.id,
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
    cancellation_docs: '',
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
    id: cancellation.cancellation.id, // Ensure the correct ID is assigned
    cancellation_date: cancellation.cancellation.cancellation_date,
    cancellation_reason: cancellation.cancellation.cancellation_reason,
    cancellation_docs: cancellation.cancellation.cancellation_docs,
    cancellation_by: cancellation.cancellation.cancellation_by,
    items: cancellation.cancellation.items.map((item) => ({
      id: item.id,
      name: item.name,
      pr_number: item.pr_number,
      po_number: item.po_number,
      sku: item.sku,
      qty: item.qty,
      purchase_request_id: item.purchase_request_id,
      purchase_request_item_id: item.purchase_request_item_id,
      purchase_order_id: item.purchase_order_id,
      purchase_order_item_id: item.purchase_order_item_id,
    })),
  });

  // Set the filter fields based on cancellation_docs and pr_po_id
  if (cancellation.cancellation_docs === '1') {
    filterPrNumber.value = cancellation.pr_po_id || ''; // Assign pr_po_id to filterPrNumber
  } else if (cancellation.cancellation_docs === '2') {
    filterPoNumber.value = cancellation.pr_po_id || ''; // Assign pr_po_id to filterPoNumber
  } else {
    filterPrNumber.value = '';
    filterPoNumber.value = '';
  }

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

    // Dynamically set the pr_po_id field based on cancellation_docs
    if (cancellationForm.cancellation_docs === '1') {
      if (!filterPrNumber.value) {
        toastr.error('Please select a valid PR Number.', 'Error');
        return;
      }
      cancellationForm.pr_po_id = filterPrNumber.value;
    } else if (cancellationForm.cancellation_docs === '2') {
      if (!filterPoNumber.value) {
        toastr.error('Please select a valid PO Number.', 'Error');
        return;
      }
      cancellationForm.pr_po_id = filterPoNumber.value;
    } else {
      cancellationForm.pr_po_id = null;
    }

    if (!cancellationForm.pr_po_id) {
      toastr.error('Please select a valid PR or PO number.', 'Error');
      return;
    }

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
  // if (!filterPrNumber.value.trim()) {
  //   toastr.warning('Please input a PR Number to select items.', 'Warning');
  //   return;
  // }
  Object.assign(newItem, { name: '', quantity: 1 });
  if (addItemModalInstance) {
    addItemModalInstance.show();
  }
};

const confirmAddItem = () => {
  if (selectedPrItem.value) {
    cancellationForm.items.push({
      name: selectedPrItem.value.product.product_description,
      quantity: 1,
      purchase_request_id: selectedPrItem.value.purchase_request.id,
      purchase_request_item_id: selectedPrItem.value.id,
    });
    if (addItemModalInstance) {
      addItemModalInstance.hide();
    }
  }
};

const openEditItemModal = (item) => {
  Object.assign(editItem, item, { pr_number: item.pr_number, po_number: item.po_number }); // Ensure pr_number and po_number are set
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
  if (!filterPoNumber.value.trim()) {
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
  return [...new Set(prItems.value.map(item => item.purchase_request.pr_number))];
});

const uniquePoNumbers = computed(() => {
  return [...new Set(poItems.value.map(item => item.purchase_order.po_number))];
});

onMounted(() => {
  const modalElement = document.getElementById('cancellationModal');
  if (modalElement) {
    modalInstance = new bootstrap.Modal(modalElement); // Initialize Bootstrap modal
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
  window.addEventListener('open-create-modal', openCreateModal); // Listen for the event
  window.addEventListener('open-edit-modal', (event) => {
    openEditModal(event.detail);
  }); // Listen for the event
  fetchPrItems().then(() => initializePrItemsTable());
  fetchPoItems().then(() => initializePoItemsTable());
  initializeCancellationItemsTable();
});
</script>

<template>
  <div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
              <label for="cancellation_docs" class="form-label">Documents</label>
              <input v-model="cancellationForm.cancellation_docs" type="text" class="form-control" id="cancellation_docs" />
              <div v-if="validationErrors.cancellation_docs" class="text-danger">{{ validationErrors.cancellation_docs[0] }}</div>
            </div>
            <div class="mb-3">
              <label for="cancellation_by" class="form-label">Cancelled By</label>
              <input v-model="cancellationForm.cancellation_by" type="text" class="form-control" id="cancellation_by" />
              <div v-if="validationErrors.cancellation_by" class="text-danger">{{ validationErrors.cancellation_by[0] }}</div>
            </div>
            <div class="mb-3">
              <label for="filter-pr-number" class="form-label">Filter by PR Number</label>
              <select
                id="filter-pr-number"
                class="form-select"
                v-model="filterPrNumber"
                @change="applyPrFilter"
              >
                <option value="">Select PR Number</option>
                <option v-for="prItem in prItems" :key="prItem.purchase_request.id" :value="prItem.purchase_request.id">
                  {{ prItem.purchase_request.pr_number }}
                </option>
              </select>
            </div>
            <div class="mb-3">
              <label for="filter-po-number" class="form-label">Filter by PO Number</label>
              <select
                id="filter-po-number"
                class="form-select"
                v-model="filterPoNumber"
                @change="applyPoFilter"
              >
                <option value="">Select PO Number</option>
                <option v-for="poItem in poItems" :key="poItem.purchase_order.id" :value="poItem.purchase_order.id">
                  {{ poItem.purchase_order.po_number }}
                </option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Items to Cancel</label>
              <div class="table-responsive">
                <table id="cancellation-items-table" class="table table-bordered border-secondary align-middle" width="100%"></table>
              </div>
              <button type="button" class="btn btn-success btn-sm mt-2" @click="openAddItemModal">Add PR</button>
              <button type="button" class="btn btn-success btn-sm mt-2" @click="openPoItemModal">Add PO</button>
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
