<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const props = defineProps({
  purchaseRequests: { type: Array, required: true },
  users: Array,
  products: Array,
  currentUser: Object,
  purchaseRequest: Object,
});

console.log('Users:', props.users);
const isEdit = ref(false);
const purchaseRequestForm = reactive({
  id: null,
  pr_number: '',
  request_date: '',
  user_id: null,
  request_by: '',
  campus: '',
  division: '',
  department: '',
  purpose: '',
  is_urgent: 0,
  items: []
});

// Cancellation Form
let modalInstance = null;
const cancellationForm = reactive({
  id: null,
  cancellation_date: '',
  cancellation_reason: '',
  cancellation_docs: '',
  cancellation_by: '',
  pr_po_id: null,
  // cancellation_reason: '',
  // cancellation_docs: '',
  // cancellation_by: '',
  approved_by: null,
  authorized_by: null,
  items: [],
});

let addItemModalInstance = null;
const prItems = ref([]);
let prItemsTableInstance;
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

// Watch for changes in filterPrNumber and reinitialize the PR items table
watch(filterPrNumber, (newVal) => {
  const filteredPrItems = prItems.value.filter((item) =>
    newVal ? item.purchase_request.id === newVal : true
  );
  if (prItemsTableInstance) {
    prItemsTableInstance.clear().rows.add(filteredPrItems).draw();
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
            title: 'Remarks',
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

const removeItemCancel = (item) => {
  const index = cancellationForm.items.findIndex((i) => i.name === item.name && i.purchase_request_item_id === item.purchase_request_item_id);
  if (index !== -1) {
    cancellationForm.items.splice(index, 1);
  }

  // Update the DataTable with the modified items array
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }
};

const openCreateModalCancel = ( purchaseRequest ) => {
  isEdit.value = false;
  Object.assign(cancellationForm, {
    id: null,
    cancellation_date: new Date().toISOString().split('T')[0],
    cancellation_reason: '',
    cancellation_docs: 1,
    pr_po_id: purchaseRequest?.id || null, // Set pr_po_id to the current purchase request ID
    cancellation_by: '',
    items: [],
  });
  validationErrors.value = {};
  if (modalInstance) {
    modalInstance.show(); // Show the modal
    nextTick(() => {
      initializeSelect2(); // Reinitialize select2 for the modal
      initializeSummernote(); // Initialize Summernote for the cancellation reason
    });
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
  // Fetch PR items filtered by the selected purchase request ID
  fetchPrItems(cancellationForm.pr_po_id).then(() => {
    // Initialize the PR items table after fetching the data
    initializePrItemsTable();
    if (addItemModalInstance) {
      addItemModalInstance.show(); // Show the modal
    }
  });
};

const selectedPurchaseRequestId = computed(() => {
  // Replace this logic with how you determine the selected purchase request
  const selectedRequest = props.purchaseRequests.find(pr => pr.id === cancellationForm.pr_po_id);
  return selectedRequest ? selectedRequest.pr_number : '';
});

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

// End Cacellation Form
const itemForm = reactive({ product_id: '', remark: '', qty: 1, uom: '', unit_price: 0, total_price: 0, campus: '', division: '', department: '' });
const validationErrors = ref({});
let dataTableInstance;
let editingItemIndex = ref(null);

const totalAmount = computed(() => {
  return purchaseRequestForm.items.reduce((sum, item) => sum + Number(item.total_price), 0).toFixed(2);
});

const itemCount = computed(() => purchaseRequestForm.items.length);

watch(() => [itemForm.qty, itemForm.unit_price], () => {
  itemForm.total_price = (itemForm.qty * itemForm.unit_price).toFixed(2);
});

watch(() => itemForm.product_id, (newProductId) => {
  const selectedProduct = props.products.find(product => product.id == newProductId);
  if (selectedProduct) {
    Object.assign(itemForm, {
      uom: selectedProduct.uom,
      product_description: selectedProduct.product_description,
      sku: selectedProduct.sku
    });
  } else {
    Object.assign(itemForm, { uom: '', product_description: '', sku: '' });
  }
});

const openCreatePage = () => {
  isEdit.value = false;
  Object.assign(purchaseRequestForm, {
    id: null, pr_number: '', request_date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', request_by: props.currentUser?.name || '',
    campus: '', division: '', department: '', purpose: '', is_urgent: false, items: [], total_amount: 0,
  });
};

const openEditPage = async (purchaseRequest) => {
  try {
    const response = await axios.get(`/purchase-requests/${purchaseRequest.id}/edit`);
    isEdit.value = true;
    const items = response.data.pr_items?.map(item => ({
      id: item.id, product_id: item.product_id, remark: item.remark, qty: item.qty, uom: item.uom,
      unit_price: item.unit_price, total_price: item.total_price, campus: item.campus, division: item.division,
      department: item.department, sku: item.product.sku, product_description: item.product.product_description,
      qty_last: item.qty_last, is_cancel: item.is_cancel
    })) || [];
    Object.assign(purchaseRequestForm, {
      id: response.data.id, pr_number: response.data.pr_number,
      request_date: response.data.request_date?.split('T')[0] || '',
      user_id: response.data.user_id, request_by: response.data.request_by?.name || '',
      campus: response.data.campus, division: response.data.division, department: response.data.department,
      purpose: response.data.purpose, is_urgent: response.data.is_urgent ? 1 : 0, items: items,
    });
    nextTick(() => {
      $('#campus').val(purchaseRequestForm.campus).trigger('change');
      $('#division').val(purchaseRequestForm.division).trigger('change');
      $('#department').val(purchaseRequestForm.department).trigger('change');
    });
  } catch (error) {
    console.error('Error fetching edit data:', error);
  }
};

if (props.purchaseRequest) openEditPage(props.purchaseRequest);

const savePurchaseRequest = async () => {
  try {
    for (const item of purchaseRequestForm.items) {
      if (!item.uom) {
        validationErrors.value = { items: [{ uom: ['UOM is required for all items.'] }] };
        return;
      }
    }
    const payload = {
      ...purchaseRequestForm,
      request_by: props.currentUser?.id || purchaseRequestForm.request_by,
      total_amount: totalAmount.value,
      total_item: itemCount.value,
      is_urgent: purchaseRequestForm.is_urgent ? 1 : 0,
    };
    if (isEdit.value) {
      const response = await axios.put(`/purchase-requests/${purchaseRequestForm.id}`, payload);
      const updatedRequest = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedRequest.id).index();
      dataTableInstance.row(rowIndex).data(updatedRequest).draw(false);
      swal('Success!', 'Purchase request updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/purchase-requests', payload);
      dataTableInstance.row.add(response.data).draw(false);
      swal('Success!', 'Purchase request created successfully!', 'success', { timer: 2000 });
    }
    clearForm();
    $('#nav-index-tab').tab('show');
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save purchase request. Please try again.', 'error');
    }
  }
};

const deletePurchaseRequest = async (purchaseRequestId) => {
  swal({
    title: 'Are you sure?', text: 'You will not be able to recover this purchase request!', icon: 'warning',
    buttons: { cancel: { text: 'No, cancel!', visible: true, className: 'btn btn-secondary', closeModal: true },
               confirm: { text: 'Yes, delete it!', visible: true, className: 'btn btn-danger', closeModal: true } },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/purchase-requests/${purchaseRequestId}`);
        dataTableInstance.row((idx, data) => data.id === purchaseRequestId).remove().draw();
        swal('Deleted!', 'Purchase request has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete purchase request. Please try again.', 'error');
      }
    }
  });
};


const clearForm = () => {
  Object.assign(purchaseRequestForm, {
    id: null, pr_number: '', request_date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', request_by: props.currentUser?.name || '',
    campus: '', division: '', department: '', purpose: '', is_urgent: false, items: [], total_amount: 0,
  });
  validationErrors.value = {};
};

const openItemModal = () => {
  Object.assign(itemForm, { product_id: '', remark: '', qty: 1, uom: '', unit_price: 0, total_price: 0, campus: purchaseRequestForm.campus, division: purchaseRequestForm.division, department: purchaseRequestForm.department });
  editingItemIndex.value = null;
  const modalElement = document.getElementById('itemModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    $('#product_id').select2({
      placeholder: 'Select a product',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#itemModal')
    }).on('change', function () {
      itemForm.product_id = $(this).val();
      const selectedProduct = props.products.find(product => product.id == itemForm.product_id);
      if (selectedProduct) {
        itemForm.uom = selectedProduct.uom;
        itemForm.product_description = selectedProduct.product_description;
        itemForm.sku = selectedProduct.sku;
      } else {
        itemForm.uom = '';
        itemForm.product_description = '';
        itemForm.sku = '';
      }
    });
  });
};

const addItem = () => {
  const item = { ...itemForm, total_price: itemForm.qty * itemForm.unit_price };
  if (editingItemIndex.value !== null) {
    purchaseRequestForm.items.splice(editingItemIndex.value, 1, item);
  } else {
    purchaseRequestForm.items.push(item);
  }
  const modalElement = document.getElementById('itemModal');
  const modal = bootstrap.Modal.getInstance(modalElement);
  modal.hide();
};

const editItem = (index) => {
  Object.assign(itemForm, purchaseRequestForm.items[index]);
  editingItemIndex.value = index;
  const modalElement = document.getElementById('itemModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    $('#product_id').select2({
      placeholder: 'Select a product',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#itemModal')
    }).val(itemForm.product_id).trigger('change');
  });
};

const removeItem = (index) => {
  purchaseRequestForm.items.splice(index, 1);
};

const format = (value, type) => {
  if (type === 'date') {
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
  }
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Pending': return 'badge bg-warning';
    case 'Approved': return 'badge bg-success';
    case 'Rejected': return 'badge bg-danger';
    case 'Void': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const initializeSelect2 = () => {
  nextTick(() => {
    setTimeout(() => {
      if ($('#campus').length) {
        $('#campus').select2({
          placeholder: 'Select a campus',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.campus = $(this).val();
        });
      }

      if ($('#division').length) {
        $('#division').select2({
          placeholder: 'Select a division',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.division = $(this).val();
        });
      }

      if ($('#department').length) {
        $('#department').select2({
          placeholder: 'Select a department',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.department = $(this).val();
        });
      }

      if ($('#approved_by').length) {
        if ($.fn.select2 && $('#approved_by').data('select2')) {
          $('#approved_by').select2('destroy');
        }

        // Initialize select2 with dropdownParent set to the modal
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
          $('#authorized_by').select2('destroy');
        }

        // Initialize select2 with dropdownParent set to the modal
        $('#authorized_by').select2({
          placeholder: 'Select an authorized person',
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

// $('#cancellationModal').on('shown.bs.modal', () => {
//   initializeSelect2();
// });

onMounted(() => {
  initializeSelect2();
  nextTick(() => {
    const table = $('#purchase-request');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true, 
        autoWidth: true, 
        scrollX: false,
        data: props.purchaseRequests,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'pr_number' },
          { data: 'request_date', render: (data) => format(data, 'date') },
          { data: 'request_by.name' },
          { data: 'campus' },
          { data: 'division' },
          { data: 'department' },
          { data: 'purpose' },
          {
            data: 'is_urgent',
            render: (data) => `<span class="badge ${data ? 'bg-primary' : 'bg-danger'}">${data ? 'Yes' : 'No'}</span>`,
            className: 'text-center'
          },
          { data: 'total_amount', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
          {
            data: 'status',
            render: (data) => `<span class="${getStatusBadgeClass(data)}">${data}</span>`,
            className: 'text-center'
          },
          {
            data: null,
            render: (data) => `
              <div class="btn-group">
                <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  ${data.status !== 'Void' && data.status !== 'Closed' ? '<li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>' : ''}
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> Detail</a></li>
                  ${data.status !== 'Void' && data.status !== 'Closed' ? '<li><a class="dropdown-item btn-cancel text-warning"><i class="fas fa-ban"></i> Cancel</a></li>' : ''}
                </ul>
              </div>
            `,
          },
        ],
      });

      $('#purchase-request').on('click', '.btn-cancel', function () {
        const rowData = dataTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          openCreateModalCancel(rowData); // Call the function to open the modal
        }
      });

      $('#purchase-request')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditPage(rowData);
          $('#nav-create-tab').tab('show');
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deletePurchaseRequest(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) window.location.href = `/purchase-requests/${rowData.id}`;
        });

      $('#purchase-request').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditPage(rowData);
        $('#nav-create-tab').tab('show');
      });

      $('#purchase-request').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deletePurchaseRequest(rowData.id);
      });

      $('#purchase-request').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) window.location.href = `/purchase-requests/${rowData.id}`;
      });
      $('#purchase-request').on('click', '.dtr-details .btn-cancel', function () {
          const tr = $(this).closest('tr').prev();
          const rowData = dataTableInstance.row(tr).data();
          if (rowData) openCreateModalCancel(rowData); // Pass rowData to the function
        });
        // $('#cancellationModal').on('shown.bs.modal', () => {
        //   initializeSelect2();
        // });
        
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
</script>

<template>
  <Main>
    <Head :title="'Purchase Request'" />
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a href="#nav-index" id="nav-index-tab" data-bs-toggle="tab" class="nav-link active" @click="clearForm">PR List</a>
      </li>
      <li class="nav-item">
        <a href="#nav-create" id="nav-create-tab" data-bs-toggle="tab" class="nav-link" @click="openCreatePage">Form</a>
      </li>
      <li class="nav-item">
          <!-- Pass 'cancellations_docs' as a query parameter when navigating -->
          <a href="/cancellations?cancellations_docs=1" class="btn btn-sm btn-secondary">PR Cancellations</a>
      </li>
    </ul>
    <div class="tab-content panel p-3 rounded-0 rounded-bottom">
      <div class="tab-pane fade active show" id="nav-index">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="purchase-request" class="table table-bordered table-sm align-middle text-nowrap" width="100%">
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
      </div>
      <div class="tab-pane fade" id="nav-create">
        <div class="panel-body">
          <form @submit.prevent="savePurchaseRequest">
            <div class="row">
              <div class="col-md-6">
                <div class="row mb-3">
                  <label for="request_by" class="col-sm-4 col-form-label">Request By</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseRequestForm.request_by" type="text" class="form-control" id="request_by" readonly />
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="campus" class="col-sm-4 col-form-label">Campus</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.campus" class="form-select select2" id="campus" required>
                      <option value="CEN">CEN</option>
                      <option value="TK">TK</option>
                    </select>
                    <div v-if="validationErrors.campus" class="text-danger">{{ validationErrors.campus[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="division" class="col-sm-4 col-form-label">Division</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.division" class="form-select select2" id="division" required>
                      <option value="Aii">Aii</option>
                      <option value="AIS">AIS</option>
                      <option value="OTHER">OTHER</option>
                      <option value="MJQES">MJQES</option>
                    </select>
                    <div v-if="validationErrors.division" class="text-danger">{{ validationErrors.division[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="department" class="col-sm-4 col-form-label">Department</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.department" class="form-select select2" id="department" required>
                      <option value="ESLP">ESLP</option>
                      <option value="AISAD">AISAD</option>
                      <option value="PROD">PROD</option>
                      <option value="IB">IB</option>
                    </select>
                    <div v-if="validationErrors.department" class="text-danger">{{ validationErrors.department[0] }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="row mb-3">
                  <label for="request_date" class="col-sm-4 col-form-label">Request Date</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseRequestForm.request_date" type="date" class="form-control" id="request_date" required />
                    <div v-if="validationErrors.request_date" class="text-danger">{{ validationErrors.request_date[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="is_urgent" class="col-sm-4 col-form-label">Urgent?</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.is_urgent" class="form-select" id="is_urgent" required>
                      <option :value="1">Yes</option>
                      <option :value="0">No</option>
                    </select>
                    <div v-if="validationErrors.is_urgent" class="text-danger">
                      {{ validationErrors.is_urgent[0] }}
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
                  <div class="col-sm-8">
                    <textarea v-model="purchaseRequestForm.purpose" class="form-control" id="purpose"></textarea>
                    <div v-if="validationErrors.purpose" class="text-danger">{{ validationErrors.purpose[0] }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <!-- <label for="items" class="form-label">Items</label> -->
                  <button type="button" class="btn btn-sm btn-primary mb-2" @click="openItemModal"><i class="fa-solid fa-circle-plus"></i> Add Item</button>
                  <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered">
                      <thead class="table-primary">
                        <tr>
                          <th>#</th>
                          <th>Product Code</th>
                          <th>Product</th>
                          <th>Remark</th>
                          <th>Campus</th>
                          <th>Division</th>
                          <th>Department</th>
                          <th>Qty</th>
                          <th>UOM</th>
                          <th>Price</th>
                          <th>Total Price</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in purchaseRequestForm.items" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.sku }}</td>
                          <td>{{ item.product_description }}</td>
                          <td>{{ item.remark }}</td>
                          <td>{{ item.campus }}</td>
                          <td>{{ item.division }}</td>
                          <td>{{ item.department }}</td>
                          <td>{{ item.qty }}</td>
                          <td>{{ item.uom }}</td>
                          <td>{{ item.unit_price }}</td>
                          <td>{{ item.total_price }}</td>
                          <td>
                            <button type="button" class="btn btn-sm btn-primary mr-1" @click="editItem(index)" :disabled="item.is_cancel === 1">
                              <i class="fa fa-edit t-plus-1 fa-fw fa-lg"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)" :disabled="item.is_cancel === 1">
                              <i class="fa fa-trash t-plus-1 fa-fw fa-lg"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div v-if="validationErrors.items" class="text-danger">{{ validationErrors.items[0] }}</div>
                </div>
              </div>
            </div>
            <div class="row justify-content-end">
              <div class="col-2">
                <div class="mb-3">
                  <label for="total_amount" class="form-label">Total Amount</label>
                  <input v-model="totalAmount" type="text" class="form-control" id="total_amount" readonly />
                </div>
              </div>
              <div class="col-2">
                <div class="mb-3">
                  <label for="item_count" class="form-label">Item Count</label>
                  <input v-model="itemCount" type="text" class="form-control" id="item_count" readonly />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="clearForm">Clear</button>
              <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update' : 'Create' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addItem">
              <div class="row mb-3">
                <label for="product_id" class="col-md-3 col-form-label">Product</label>
                <div class="col-md-9">
                  <select v-model="itemForm.product_id" class="form-select select2" id="product_id" required>
                    <option v-for="product in props.products" :key="product.id" :value="product.id">
                      {{ product.product_description }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="remark" class="col-md-3 col-form-label">Remark</label>
                <div class="col-md-9">
                  <textarea v-model="itemForm.remark" class="form-control" id="remark" rows="3"></textarea>
                </div>
              </div>
              <div class="row mb-3">
                <label for="qty" class="col-md-3 col-form-label">Qty</label>
                <div class="col-md-9">
                  <input v-model="itemForm.qty" type="number" step="0.0001" class="form-control" id="qty" required min="0.0001" />
                </div>
              </div>
              <div class="row mb-3">
                <label for="uom" class="col-md-3 col-form-label">UOM</label>
                <div class="col-md-9">
                  <input v-model="itemForm.uom" type="text" class="form-control" id="uom" required readonly />
                </div>
              </div>
              <div class="row mb-3">
                <label for="unit_price" class="col-md-3 col-form-label">Price</label>
                <div class="col-md-9">
                  <input v-model="itemForm.unit_price" type="number" step="0.0001" class="form-control" id="unit_price" required min="0.0001" />
                </div>
              </div>
              <div class="row mb-3">
                <label for="total_price" class="col-md-3 col-form-label">Total Price</label>
                <div class="col-md-9">
                  <input v-model="itemForm.total_price" type="text" class="form-control" id="total_price" readonly />
                </div>
              </div>
              <div class="row mb-3">
                <label for="campus" class="col-md-3 col-form-label">Campus</label>
                <div class="col-md-9">
                  <select v-model="itemForm.campus" class="form-select" id="campus" required>
                    <option value="CEN">CEN</option>
                    <option value="TK">TK</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="division" class="col-md-3 col-form-label">Division</label>
                <div class="col-md-9">
                  <select v-model="itemForm.division" class="form-select" id="division" required>
                    <option value="Aii">Aii</option>
                    <option value="AIS">AIS</option>
                    <option value="OTHER">OTHER</option>
                    <option value="MJQES">MJQES</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="department" class="col-md-3 col-form-label">Department</label>
                <div class="col-md-9">
                  <select v-model="itemForm.department" class="form-select" id="department" required>
                    <option value="ESLP">ESLP</option>
                    <option value="AISAD">AISAD</option>
                    <option value="PROD">PROD</option>
                    <option value="IB">IB</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

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
                <button type="button" class="btn btn-success btn-sm" @click="openAddItemModal"> <i class="fas fa-plus-circle"></i> SELECT PR ITEMS</button>
              </div>
              <div class="panel panel-inverse border mt-3 p-3">
                <div class="panel-heading bg-warning text-white mb-2">
                  <h4 class="panel-title">ITEM TO CANCEL</h4>
                </div>
                <div class="table-responsive ">
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
                  <div class="col-6 border">
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

  </Main>
</template>
