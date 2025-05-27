<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
// import 'bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css';
// import 'bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js';

const props = defineProps({
  purchaseOrders: { type: Array, required: true },
  users: Array,
  suppliers: Array,
  prItems: Array,
  purchaseRequests: Array,
  currentUser: Object,
  purchaseOrder: Object,
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
  items: [],
});

let addItemModalInstance = null;
const poItems = ref([]);
let poItemsTableInstance;
let cancellationItemsTableInstance;

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
watch(filterPoNumber, (newVal) => {
  const filteredPoItems = poItems.value.filter((item) =>
    newVal ? item.purchase_order.id === newVal : true
  );
  if (poItemsTableInstance) {
    poItemsTableInstance.clear().rows.add(filteredPoItems).draw();
  }
});

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
  const index = cancellationForm.items.findIndex((i) => i.name === item.name && i.purchase_order_item_id === item.purchase_order_item_id);
  if (index !== -1) {
    cancellationForm.items.splice(index, 1);
  }

  // Update the DataTable with the modified items array
  if (cancellationItemsTableInstance) {
    cancellationItemsTableInstance.clear().rows.add(cancellationForm.items).draw();
  }
};

const openCreateModalCancel = ( purchaseOrder ) => {
  isEdit.value = false;
  Object.assign(cancellationForm, {
    id: null,
    cancellation_date: new Date().toISOString().split('T')[0],
    cancellation_reason: '',
    cancellation_docs: 2,
    pr_po_id: purchaseOrder?.id || null, // Set pr_po_id to the current purchase order ID
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
  fetchPoItems(cancellationForm.pr_po_id).then(() => {
    // Initialize the PR items table after fetching the data
    initializePoItemsTable();
    if (addItemModalInstance) {
      addItemModalInstance.show(); // Show the modal
    }
  });
};

const selectedPurchaseOrderId = computed(() => {
  // Replace this logic with how you determine the selected purchase request
  const selectedOrder = props.purchaseOrders.find(pr => pr.id === cancellationForm.pr_po_id);
  return selectedRequest ? selectedRequest.po_number : '';
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

const isEdit = ref(false);
const purchaseOrderForm = reactive({
  date: '',
  user_id: null,
  supplier_id: '',
  currency: 1, // Ensure default to 1 (USD)
  currency_rate: 1,
  payment_term: '',
  purpose: '',
  vat: 0,
  items: []
});

const itemForm = reactive({ 
  id: null, // Add id field for updates
  pr_item_id: '',
  description: '',
  qty: 1, 
  uom: '',
  unit_price: 0, 
  total_price: 0, 
  campus: '', 
  division: '', 
  department: '', 
  total_usd: 0, 
  total_khr: 0, 
  purchaser_id: '', 
  discount: 0, 
  vat: 0,
  location: '',
});

const validationErrors = ref({});
let dataTableInstance;
let prItemsTableInstance;
let editingItemIndex = ref(null);
const cancelledReason = ref('');

const totalAmountUsd = computed(() => {
  return purchaseOrderForm.items.reduce((sum, item) => sum + Number(item.total_price), 0).toFixed(2);
});

const itemCount = computed(() => purchaseOrderForm.items.length);

const totalDiscount = computed(() => {
  return purchaseOrderForm.items.reduce((sum, item) => sum + Number(item.discount), 0).toFixed(2);
});

const totalVatAmount = computed(() => {
  return purchaseOrderForm.items.reduce((sum, item) => {
    const qty = parseFloat(item.qty) || 0;
    const unit_price = parseFloat(item.unit_price) || 0;
    const discount = parseFloat(item.discount) || 0;
    const vat = parseFloat(purchaseOrderForm.vat) || 0;
    return sum + Number(((qty * unit_price - discount) * (vat / 100)).toFixed(2));
  }, 0).toFixed(2);
});

const subTotal = computed(() => {
  return purchaseOrderForm.items.reduce((sum, item) => {
    const qty = parseFloat(item.qty) || 0;
    const unit_price = parseFloat(item.unit_price) || 0;
    return sum + (qty * unit_price);
  }, 0).toFixed(2);
});

watch(() => [itemForm.qty, itemForm.unit_price, itemForm.discount, purchaseOrderForm.vat], () => {
  const qty = parseFloat(itemForm.qty) || 0;
  const unit_price = parseFloat(itemForm.unit_price) || 0;
  const discount = parseFloat(itemForm.discount) || 0;
  const vat = parseFloat(purchaseOrderForm.vat) || 0; // Link VAT from PO form
  itemForm.total_price = Number(((qty * unit_price - discount) * (1 + vat / 100)).toFixed(2));
});

watch(() => itemForm.pr_item_id, (newPrItemId) => {
  if (!isEdit.value && props.prItems) { // Only set description to remark when not editing
    const selectedPrItem = props.prItems.find(prItem => prItem?.id == newPrItemId);
    if (selectedPrItem && selectedPrItem.product) {
      Object.assign(itemForm, {
        pr_number: selectedPrItem.purchase_request?.pr_number || 'N/A',
        description: itemForm.description || selectedPrItem.remark, // Preserve description if already set, otherwise set to remark
        uom: selectedPrItem.uom,
        unit_price: selectedPrItem.unit_price, // Use 'unit_price' for prItem
        total_price: (itemForm.qty * selectedPrItem.unit_price).toFixed(2),
        campus: selectedPrItem.campus,
        division: selectedPrItem.division,
        department: selectedPrItem.department,
        total_usd: (itemForm.qty * selectedPrItem.unit_price).toFixed(2),
        total_khr: (itemForm.qty * selectedPrItem.unit_price * purchaseOrderForm.currency_rate).toFixed(2), // Use currency_rate from form
        purchaser_id: props.currentUser?.id || '',
        discount: itemForm.discount, // Preserve discount
        vat: itemForm.vat, // Preserve vat
        location: itemForm.location // Preserve location
      });
    } else {
      Object.assign(itemForm, { 
        description: '', 
        uom: '', 
        unit_price: 0, 
        total_price: 0, 
        campus: '', 
        division: '', 
        department: '', 
        total_usd: 0, 
        total_khr: 0, 
        purchaser_id: '', 
        discount: itemForm.discount, 
        vat: itemForm.vat, 
        location: itemForm.location });
    }
  }
});

watch(() => purchaseOrderForm.vat, () => {
  purchaseOrderForm.items.forEach(item => {
    const qty = parseFloat(item.qty) || 0;
    const unit_price = parseFloat(item.unit_price) || 0;
    const discount = parseFloat(item.discount) || 0;
    const vat = parseFloat(purchaseOrderForm.vat) || 0; // Link VAT from PO form
    item.total_price = Number(((qty * unit_price - discount) * (1 + vat / 100)).toFixed(2));
  });
});

watch(() => purchaseOrderForm.supplier_id, (newSupplierId) => {
  const selectedSupplier = props.suppliers.find(supplier => supplier.id === newSupplierId);
  if (selectedSupplier) {
    purchaseOrderForm.payment_term = selectedSupplier.payment_term;
    purchaseOrderForm.supplier_phone = selectedSupplier.number; // Link supplier phone number
    purchaseOrderForm.supplier_address = selectedSupplier.address; // Link supplier address
  } else {
    purchaseOrderForm.payment_term = '';
    purchaseOrderForm.supplier_phone = ''; // Clear supplier phone number
    purchaseOrderForm.supplier_address = ''; // Clear supplier address
  }
});

const initializeSelect2 = () => {
  nextTick(() => {
    setTimeout(() => {
      if ($.fn.select2 && $('#supplier_id').data('select2')) {
        purchaseOrderForm.supplier_id = $(this).val();
        $('#supplier_id').select2('destroy');
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
    }, 300);
  });
};

const initializeSupplierSelect = () => {
  $('#supplier_id').select2({
    data: props.suppliers.map(supplier => ({ id: supplier.id, text: supplier.name })),
    dropdownParent: $('#supplier_id').parent(),
    placeholder: 'Select Supplier', // Ensure placeholder is set
    allowClear: true,
    width: 'resolve',
    ajax: {
      url: '/search-suppliers',
      dataType: 'json',
      delay: 250,
      data: (params) => ({ q: params.term }),
      processResults: (data) => ({ results: data.map(supplier => ({ id: supplier.id, text: supplier.name })) }),
      cache: true,
    },
  }).on('select2:select', function (e) {
    purchaseOrderForm.supplier_id = e.params.data.id;
  }).on('select2:unselect', function () {
    purchaseOrderForm.supplier_id = '';
    purchaseOrderForm.payment_term = '';
    purchaseOrderForm.supplier_phone = '';
    purchaseOrderForm.supplier_address = '';
  });
};

const openCreatePage = () => {
  isEdit.value = false;
  Object.assign(purchaseOrderForm, {
    id: null, po_number: '', date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', supplier_id: '', currency: 1, // Ensure default currency is set to 1 (USD)
    currency_rate: 4022, payment_term: '', purpose: '', vat: 0, items: [], total_amount_usd: 0,
  });

  // Clear itemForm fields
  Object.assign(itemForm, {
    id: null, // Clear id field
    pr_item_id: '',
    description: '',
    qty: 1,
    uom: '',
    unit_price: 0,
    total_price: 0,
    campus: '',
    division: '',
    department: '',
    total_usd: 0,
    total_khr: 0,
    purchaser_id: '',
    discount: 0,
    vat: 0,
    location: '',
  });

  nextTick(() => {
    initializeSupplierSelect(); // Re-initialize Select2 when opening the create form
    $('#supplier_id').val('').trigger('change'); // Ensure the select2 component is cleared
    $('#date').datepicker({
      todayHighlight: true,
      autoclose: true,
      format: 'yyyy-mm-dd'
    }).on('changeDate', function (e) {
      purchaseOrderForm.date = e.format('yyyy-mm-dd');
    });
  });
};

const openEditPage = async (purchaseOrder) => {
  try {
    const response = await axios.get(`/purchase-orders/${purchaseOrder.id}/edit`);
    isEdit.value = true;
    const items = response.data.po_items?.map(item => {
      const qty = parseFloat(item.qty) || 0;
      const unit_price = parseFloat(item.unit_price) || 0;
      const discount = parseFloat(item.discount) || 0;
      const vat = parseFloat(response.data.vat) || 0; // Use VAT from PO
      const total_price = ((qty * unit_price) - discount) * (1 + (vat / 100)).toFixed(2); // Calculate total_price
      return {
        id: item.id, 
        pr_item_id: item.pr_item_id, 
        description: item.description, // Use PO description for modal
        pr_description: item.pr_item.product.product_description + ' | ' + item.pr_item.remark, // Concatenate product description and remark for pr_description
        concatenated_description: item.pr_item.product.product_description + ' | ' + item.description, // Add concatenated_description for table
        qty: qty, 
        uom: item.uom,
        unit_price: unit_price, 
        total_price: total_price, // Use calculated total_price
        pr_number: item.pr_item.purchase_request?.pr_number || 'N/A',
        sku: item.pr_item.product?.sku || 'N/A',
        campus: item.campus, 
        division: item.division, 
        department: item.department, 
        location: item.location,
        total_usd: total_price, // Use calculated total_price for total_usd
        total_khr: (total_price * purchaseOrderForm.currency_rate).toFixed(2), // Calculate total_khr based on total_price and currency_rate
        purchaser_id: item.purchaser_id,
        discount: discount, 
        vat: vat
      };
    }) || [];
    Object.assign(purchaseOrderForm, {
      id: response.data.id, po_number: response.data.po_number,
      date: response.data.date?.split('T')[0] || '',
      user_id: response.data.user_id, supplier_id: response.data.supplier_id,
      status: response.data.status, currency: response.data.currency, currency_rate: response.data.currency_rate, payment_term: response.data.payment_term, vat: response.data.vat, items: items,
      total_amount_usd: response.data.total_amount_usd,
      purpose: response.data.purpose // Ensure purpose is included
    });

    // Reset itemForm fields
    Object.assign(itemForm, {
      id: null, // Clear id field
      pr_item_id: '',
      description: '', // Clear description
      qty: 1,
      uom: '',
      unit_price: 0,
      total_price: 0,
      campus: '',
      division: '',
      department: '',
      total_usd: 0,
      total_khr: 0,
      purchaser_id: '',
      discount: 0,
      vat: 0,
      location: '',
    });

    nextTick(() => {
      initializeSupplierSelect(); // Re-initialize Select2 when opening the edit form
      $('#date').datepicker({
        todayHighlight: true,
        autoclose: true,
        format: 'yyyy-mm-dd'
      }).on('changeDate', function (e) {
        purchaseOrderForm.date = e.format('yyyy-mm-dd');
      });
    });
  } catch (error) {
    console.error('Error fetching purchase order:', error); // Log the error
  }
};

if (props.purchaseOrder && props.purchaseOrder.id) openEditPage(props.purchaseOrder);

const fetchPurchaseOrders = async () => {
  try {
    const response = await axios.get('/purchase-orders');
    dataTableInstance.clear().rows.add(response.data.purchaseOrders).draw();
  } catch (error) {
    console.error('Error fetching purchase orders:', error); // Log the error
  }
};

const savePurchaseOrder = async () => {
  try {
    const payload = {
      ...purchaseOrderForm,
      user_id: props.currentUser?.id || purchaseOrderForm.user_id,
      total_amount_usd: parseFloat(totalAmountUsd.value), // Ensure total_amount_usd is a float
      total_item: parseInt(itemCount.value), // Ensure total_item is an integer
      currency: parseInt(purchaseOrderForm.currency), // Ensure currency is an integer
      currency_rate: parseFloat(purchaseOrderForm.currency_rate), // Ensure currency_rate is a float
      payment_term: purchaseOrderForm.payment_term, // Ensure payment_term is included
      purpose: purchaseOrderForm.purpose, // Ensure purpose is included
      vat: parseFloat(purchaseOrderForm.vat), // Ensure VAT is a float
      status: purchaseOrderForm.status || 'Pending', // Ensure status is included
      items: purchaseOrderForm.items.map(item => ({
        ...item,
        qty: parseFloat(item.qty), // Ensure qty is a float
        unit_price: parseFloat(item.unit_price), // Ensure unit_price is a float
        discount: parseFloat(item.discount), // Ensure discount is a float
        vat: parseFloat(item.vat), // Ensure VAT is a float
        total_usd: parseFloat(((item.qty * item.unit_price - item.discount) * (1 + item.vat / 100)).toFixed(2)), // Ensure total_usd is calculated and a float
        total_khr: parseFloat(((item.qty * item.unit_price - item.discount) * (1 + item.vat / 100) * purchaseOrderForm.currency_rate).toFixed(2)) // Ensure total_khr is calculated and a float using currency_rate
      })) // Ensure items are correctly formatted
    };

    let response;
    if (isEdit.value) {
      response = await axios.put(`/purchase-orders/${purchaseOrderForm.id}`, payload);
    } else {
      response = await axios.post('/purchase-orders', payload);
    }

    if (isEdit.value) {
      const updatedOrder = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedOrder.id).index();
      dataTableInstance.row(rowIndex).data(updatedOrder).draw(false);
      swal('Success!', 'Purchase order updated successfully!', 'success', { timer: 2000 });
    } else {
      dataTableInstance.row.add(response.data).draw(false);
      swal('Success!', 'Purchase order created successfully!', 'success', { timer: 2000 });
    }
    clearForm();
    $('#nav-index-tab').tab('show');
  } catch (error) {
    console.error('Error saving purchase order:', error); // Log the error
    if (error.response) {
      if (error.response?.status === 400) {
        validationErrors.value = error.response.data.errors;
      } else {
        swal('Error!', 'Failed to save purchase order. Please try again.', 'error');
      }
    }
  }
};

const deletePurchaseOrder = async (purchaseOrderId) => {
  swal({
    title: 'Are you sure?', text: 'You will not be able to recover this purchase order!', icon: 'warning',
    buttons: { cancel: { text: 'No, cancel!', visible: true, className: 'btn btn-secondary', closeModal: true },
               confirm: { text: 'Yes, delete it!', visible: true, className: 'btn btn-danger', closeModal: true } },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/purchase-orders/${purchaseOrderId}`);
        dataTableInstance.row((idx, data) => data.id === purchaseOrderId).remove().draw();
        swal('Deleted!', 'Purchase order has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        console.error('Error deleting purchase order:', error); // Log the error
        swal('Error!', 'Failed to delete purchase order. Please try again.', 'error');
      }
    }
  });
};

const cancelPurchaseOrder = async (purchaseOrderId) => {
  const modalElement = document.getElementById('cancelReasonModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    document.getElementById('cancelled_reason').focus();
  });

  const confirmCancel = async () => {
    if (cancelledReason.value) {
      try {
        await axios.put(`/purchase-orders/${purchaseOrderId}/cancel`, { cancelled_reason: cancelledReason.value });
        const rowIndex = dataTableInstance.row((idx, data) => data.id === purchaseOrderId).index();
        const rowData = dataTableInstance.row(rowIndex).data();
        rowData.status = 'Cancelled';
        dataTableInstance.row(rowIndex).data(rowData).draw(false);
        swal('Cancelled!', 'Purchase order has been cancelled.', 'success', { timer: 2000 });
        modal.hide();
      } catch (error) {
        console.error('Error cancelling purchase order:', error); // Log the error
        swal('Error!', 'Failed to cancel purchase order. Please try again.', 'error');
      }
    }
  };

  modalElement.querySelector('.btn-danger').addEventListener('click', confirmCancel);
};

const clearForm = () => {
  Object.assign(purchaseOrderForm, {
    id: null, po_number: '', date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', supplier_id: '', currency: 'USD', currency_rate: 1, payment_term: '', purpose: '', vat: 0, items: [], total_amount_usd: 0,
  });
  validationErrors.value = {};
};

const editItem = (index) => {
  // Set the editing item index
  editingItemIndex.value = index;

  // Clear itemForm before assigning new values
  Object.assign(itemForm, {
    id: null, // Clear id field
    pr_item_id: '',
    description: '', // Clear additional description
    qty: 1,
    uom: '',
    unit_price: 0,
    total_price: 0,
    campus: '',
    division: '',
    department: '',
    total_usd: 0,
    total_khr: 0,
    purchaser_id: '',
    discount: 0,
    vat: 0,
    location: '',
  });

  // Assign new values to itemForm
  nextTick(() => {
    Object.assign(itemForm, purchaseOrderForm.items[index]);
    itemForm.description = purchaseOrderForm.items[index].description; // Set description from PO item
    itemForm.location = purchaseOrderForm.items[index].location; // Ensure location is preserved

    // Show the modal after setting the itemForm values
    $('#itemEditModal').modal('show');
  });
};

const updateItem = () => {
  validationErrors.value = {}; // Clear previous validation errors

  if (!itemForm.description) {
    validationErrors.value.description = ['Description is required.'];
  }
  if (!itemForm.qty || itemForm.qty <= 0) {
    validationErrors.value.qty = ['Quantity must be greater than 0.'];
  }
  if (!itemForm.unit_price || itemForm.unit_price <= 0) {
    validationErrors.value.unit_price = ['Unit price must be greater than 0.'];
  }
  if (!itemForm.location) {
    validationErrors.value.location = ['Location is required.'];
  }
  if (!itemForm.campus) {
    validationErrors.value.campus = ['Campus is required.'];
  }
  if (!itemForm.division) {
    validationErrors.value.division = ['Division is required.'];
  }
  if (!itemForm.department) {
    validationErrors.value.department = ['Department is required.'];
  }

  const remaining_qty = itemForm.qty - (itemForm.cancelled_qty || 0) - (itemForm.received_qty || 0);
  if (itemForm.cancelled_qty > remaining_qty) {
    validationErrors.value.cancelled_qty = ['Cancelled quantity cannot exceed remaining quantity.'];
  }

  if (Object.keys(validationErrors.value).length === 0) {
    if (editingItemIndex.value !== null) {
      const item = purchaseOrderForm.items[editingItemIndex.value];
      const qty = parseFloat(itemForm.qty) || 0;
      const unit_price = parseFloat(itemForm.unit_price) || 0;
      const discount = parseFloat(itemForm.discount) || 0;
      const vat = parseFloat(purchaseOrderForm.vat) || 0; // Link VAT from PO form
      Object.assign(item, itemForm);
      item.total_price = Number(((qty * unit_price - discount) * (1 + vat / 100)).toFixed(2)); // Ensure total_price is calculated
      item.concatenated_description = `${itemForm.pr_description} | ${itemForm.description}`; // Concatenate product description and PO description for table
      item.location = itemForm.location; // Ensure location is updated
      item.total_khr = Number((item.total_price * purchaseOrderForm.currency_rate).toFixed(2)); // Calculate total_khr based on total_price and currency_rate
      editingItemIndex.value = null;
      $('#itemEditModal').modal('hide');
    }
  }
};

const removeItem = (index) => {
  purchaseOrderForm.items.splice(index, 1);
};

const duplicateItem = (index) => {
  const itemToDuplicate = purchaseOrderForm.items[index];
  const duplicatedItem = { ...itemToDuplicate, id: null }; // Create a copy of the item with a null id
  purchaseOrderForm.items.splice(index + 1, 0, duplicatedItem); // Insert the duplicated item after the original
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
    case 'Cancelled': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const openPrItemsModal = () => {
  const modalElement = document.getElementById('prItemsModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    prItemsTableInstance.clear().draw();

    if (!document.getElementById('pr-number-filter')) {
      const prNumberSelect = $('<select id="pr-number-filter" class="form-select form-select-sm" style="width: 200px; margin-left: 10px;"><option value="">Select PR Number</option></select>')
        .appendTo('#pr-items-table_filter')
        .on('change', function () {
          const val = $(this).val();
          if (val) {
            const filteredItems = props.prItems.filter(prItem => prItem.purchase_request?.pr_number === val);
            prItemsTableInstance.clear().rows.add(filteredItems).draw();
          } else {
            prItemsTableInstance.clear().draw();
          }
        });

      props.purchaseRequests.forEach(purchaseRequest => {
        if (purchaseRequest.pr_number) {
          prNumberSelect.append(`<option value="${purchaseRequest.pr_number}">${purchaseRequest.pr_number}</option>`);
        }
      });

      $('#pr-items-table_filter').css('display', 'flex').css('justify-content', 'flex-end');
    }
  });

  modalElement.addEventListener('hidden.bs.modal', () => {
    $('#pr-number-filter').val('').trigger('change');
  });
};

const addItemToPo = (prItem) => {
  const qty = parseFloat(prItem.qty_pending) || 0;
  const unit_price = parseFloat(prItem.unit_price) || 0;
  const discount = 0;
  const vat = 0;
  const total_price = Number(((qty * unit_price - discount) * (1 + vat / 100)).toFixed(2));
  purchaseOrderForm.items.push({
    pr_item_id: prItem.id,
    pr_number: prItem.purchase_request?.pr_number || 'N/A',
    sku: prItem.product?.sku || 'N/A',
    description: itemForm.description, // Preserve description from itemForm
    pr_description: prItem.product.product_description + ' | ' + prItem.remark, // Concatenate product description and remark for pr_description
    concatenated_description: `${prItem.product.product_description} | ${itemForm.description}`, // Concatenate product description and preserved description
    qty: qty,
    uom: prItem.uom,
    unit_price: unit_price,
    total_price: total_price,
    campus: prItem.campus,
    division: prItem.division,
    department: prItem.department,
    location: prItem.location,
    total_usd: total_price,
    total_khr: Number((total_price * purchaseOrderForm.currency_rate).toFixed(2)), // Calculate total_khr based on total_price and currency_rate
    purchaser_id: props.currentUser?.id || '',
    discount: discount,
    vat: vat
  });

  // Remove the added item from the PR items table
  const rowIndex = prItemsTableInstance.row((idx, data) => data.id === prItem.id).index();
  prItemsTableInstance.row(rowIndex).remove().draw();
};

onMounted(() => {
  initializeSelect2();
  nextTick(() => {
    const table = $('#purchase-order');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true, autoWidth: true, data: props.purchaseOrders,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'po_number' }, // Ensure po_number column is correctly defined
          { data: 'date', render: (data) => format(data, 'date') },
          { data: 'supplier.name' }, // Ensure supplier.name column is correctly defined
          { data: 'total_amount_usd', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
          { data: 'purpose' }, // Add purpose column
          { data: 'vat', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') }, // Add VAT column
          {
            data: 'status', // Ensure status column is correctly defined
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
                  ${data.status !== 'Cancelled' ? '<li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>' : ''}
                  ${data.status !== 'Cancelled' ? '<li><a class="dropdown-item btn-cancel text-warning"><i class="fas fa-ban"></i> Cancel</a></li>' : ''}
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                  <li><a class="dropdown-item text-primary" href="/purchase-orders/${data.id}"><i class="fas fa-eye"></i> View</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      initializeSupplierSelect(); // Initialize Select2 on mount

      $('#purchase-order').on('click', '.btn-cancel', function () {
        const rowData = dataTableInstance.row($(this).closest('tr')).data();
        if (rowData) {
          openCreateModalCancel(rowData); // Call the function to open the modal
        }
      });

      $('#purchase-order')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData && rowData.id) {
            openEditPage(rowData);
            $('#nav-create-tab').tab('show');
          }
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deletePurchaseOrder(rowData.id);
        })
        .on('click', '.btn-cancel', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) cancelPurchaseOrder(rowData.id);
        });

      $('#purchase-order').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData && rowData.id) {
          openEditPage(rowData);
          $('#nav-create-tab').tab('show');
        }
      });

      $('#purchase-order').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deletePurchaseOrder(rowData.id);
      });

      // $('#purchase-order').on('click', '.dtr-details .btn-cancel', function () {
      //   const tr = $(this).closest('tr').prev();
      //   const rowData = dataTableInstance.row(tr).data();
      //   if (rowData) openCreateModalCancel(rowData.id);
      // });

      $('#purchase-order').on('click', '.dtr-details .btn-cancel', function () {
          const tr = $(this).closest('tr').prev();
          const rowData = dataTableInstance.row(tr).data();
          if (rowData) openCreateModalCancel(rowData); // Pass rowData to the function
        });

      const prItemsTable = $('#pr-items-table');
      if (prItemsTable.length) {
        prItemsTableInstance = prItemsTable.DataTable({
          responsive: true,
          autoWidth: false,
          scrollX: false,
          data: [], // Start with empty data
          columns: [
            { 
              data: null,
              render: (data, type, row, meta) => meta.row + 1 // Show sequence number
            },
            { 
              data: null,
              render: (data) => data.product?.sku || 'N/A',
            },
            { 
              data: null,
              render: (data) => data.purchase_request?.pr_number || 'N/A',
            },
            { 
              data: null,
              render: (data) => `${data.product.product_description} | ${data.remark}`,
              className: 'wrap-cell',
            },
            { data: 'qty_pending' },
            { data: 'uom' },
            { data: 'unit_price' }, // Use 'unit_price' for prItem
            { data: 'total_price' },
            {
              data: null,
              render: (data) => `
                <button type="button" class="btn btn-sm btn-primary btn-add-to-po">
                  <i class="fa fa-plus t-plus-1 fa-fw fa-lg"></i> Add
                </button>
              `,
              className: 'text-center'
            },
          ],
        });

        $('#pr-items-table').on('click', '.btn-add-to-po', function () {
          const rowData = prItemsTableInstance.row($(this).closest('tr')).data();
          addItemToPo(rowData);
        });
      }
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
    <Head :title="'Purchase Orders'" />
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a href="#nav-index" id="nav-index-tab" data-bs-toggle="tab" class="nav-link active" @click="clearForm">PO List</a>
      </li>
      <li class="nav-item">
        <a href="#nav-create" id="nav-create-tab" data-bs-toggle="tab" class="nav-link" @click="openCreatePage">Form</a>
      </li>
      <li class="nav-item">
        <!-- Pass 'cancellations_docs' as a query parameter when navigating -->
        <a href="/cancellations?cancellations_docs=2" class="btn btn-sm btn-secondary">PO Cancellations</a>
    </li>
    </ul>
    <div class="tab-content panel p-3 rounded-0 rounded-bottom">
      <div class="tab-pane fade active show" id="nav-index">
        <div class="panel-body">
          <table id="purchase-order" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>PO Number</th>
                <th>Date</th>
                <th>Supplier</th>
                <th>Total Amount</th>
                <th>Purpose</th>
                <th>VAT(%)</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div class="tab-pane fade" id="nav-create">
        <div class="panel-body">
          <form @submit.prevent="savePurchaseOrder">
            <div class="row">
              <div class="col-md-6">
                <div class="row mb-1">
                  <label for="user_id" class="col-sm-4 col-form-label">Purchaser</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.name" type="text" class="form-control text-primary" id="user_name" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_position" class="col-sm-4 col-form-label">Position</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.position" type="text" class="form-control text-primary" id="user_position" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_card_id" class="col-sm-4 col-form-label">Card ID</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.card_id" type="text" class="form-control text-primary" id="user_card_id" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_phone" class="col-sm-4 col-form-label">Phone</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.phone" type="text" class="form-control text-primary" id="user_phone" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_email" class="col-sm-4 col-form-label">Email</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.email" type="text" class="form-control text-primary" id="user_email" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseOrderForm.currency" class="form-select" id="currency">
                      <option value="1">USD</option>
                      <option value="2">KHR</option>
                    </select>
                    <div v-if="validationErrors.currency" class="text-danger">{{ validationErrors.currency[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="currency_rate" class="col-sm-4 col-form-label">Currency Rate</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.currency_rate" type="number" step="0.01" class="form-control" id="currency_rate" />
                    <div v-if="validationErrors.currency_rate" class="text-danger">{{ validationErrors.currency_rate[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
                  <div class="col-sm-8">
                    <textarea v-model="purchaseOrderForm.purpose" class="form-control" id="purpose" rows="3"></textarea>
                    <div v-if="validationErrors.purpose" class="text-danger">{{ validationErrors.purpose[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="vat" class="col-sm-4 col-form-label">VAT (%)</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.vat" type="number" step="0.01" class="form-control" id="vat" />
                    <div v-if="validationErrors.vat" class="text-danger">{{ validationErrors.vat[0] }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="row mb-1">
                  <label for="supplier_id" class="col-sm-4 col-form-label">Supplier</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseOrderForm.supplier_id" class="form-select" id="supplier_id" style="width: 100%;">
                      <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.name }}
                      </option>
                    </select>
                    <div v-if="validationErrors.supplier_id" class="text-danger">{{ validationErrors.supplier_id[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="supplier_phone" class="col-sm-4 col-form-label">Supplier Phone</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.supplier_phone" type="text" class="form-control text-primary" id="supplier_phone" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="supplier_address" class="col-sm-4 col-form-label">Supplier Address</label>
                  <div class="col-sm-8">
                    <textarea v-model="purchaseOrderForm.supplier_address" class="form-control text-primary" id="supplier_address" rows="3" readonly></textarea>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.payment_term" type="text" class="form-control text-primary" id="payment_term" readonly />
                    <div v-if="validationErrors.payment_term" class="text-danger">{{ validationErrors.payment_term[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="date" class="col-sm-4 col-form-label">Date</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.date" type="text" class="form-control" id="date" />
                    <div v-if="validationErrors.date" class="text-danger">{{ validationErrors.date[0] }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <div v-if="!itemForm.pr_item_id"></div>
                  <button type="button" class="btn btn-sm btn-primary mb-2" @click="openPrItemsModal"><i class="fa-solid fa-circle-plus"></i> Select Item</button>
                  <div>
                    <table class="table table-bordered table-fixed-header table-sm">
                      <thead class="table-primary">
                        <tr>
                          <th>#</th>
                          <th style="width: 10%;">PR Number</th>
                          <th style="width: 7%;">Item Code</th>
                          <th style="width: 30%;">Description</th>
                          <th>Qty</th>
                          <th>UOM</th>
                          <th>Unit Price</th>
                          <th>Discount</th>
                          <th>VAT%</th>
                          <th>Total Price</th>
                          <th>Campus</th> <!-- Add campus column header -->
                          <th>Division</th> <!-- Add division column header -->
                          <th>Department</th> <!-- Add department column header -->
                          <th>Location</th> <!-- Add location column header -->
                          <th style="width: 10%;">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in purchaseOrderForm.items" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.pr_number }}</td> <!-- Show PR number -->
                          <td>{{ item.sku }}</td> <!-- Show SKU -->
                          <td>{{ item.concatenated_description }}</td> <!-- Show concatenated description -->
                          <td>{{ item.qty }}</td>
                          <td>{{ item.uom }}</td>
                          <td>{{ item.unit_price }}</td>
                          <td>{{ item.discount }}</td>
                          <td>{{ purchaseOrderForm.vat }}</td> <!-- Link VAT from PO form -->
                          <td>{{ item.total_price }}</td>
                          <td>{{ item.campus }}</td> <!-- Show campus -->
                          <td>{{ item.division }}</td> <!-- Show division -->
                          <td>{{ item.department }}</td> <!-- Show department -->
                          <td>{{ item.location }}</td> <!-- Show location -->
                          <td>
                            <button type="button" class="btn btn-sm btn-primary" @click="editItem(index)">
                              <i class="fa fa-edit t-plus-1 fa-fw fa-lg"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)">
                              <i class="fa fa-trash t-plus-1 fa-fw fa-lg"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" @click="duplicateItem(index)">
                              <i class="fa fa-copy t-plus-1 fa-fw fa-lg"></i>
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
              <div class="col-4">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <th>Sub Total</th>
                      <td>{{ subTotal }}</td>
                    </tr>
                    <tr>
                      <th>Discount</th>
                      <td>{{ totalDiscount }}</td>
                    </tr>
                    <tr>
                      <th>VAT</th>
                      <td>{{ totalVatAmount }}</td>
                    </tr>
                    <tr>
                      <th>Grand Total</th>
                      <td>{{ totalAmountUsd }}</td>
                    </tr>
                  </tbody>
                </table>
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
  </Main>

  <!-- Modal for PR Items -->
  <div class="modal fade" id="prItemsModal" tabindex="-1" aria-labelledby="prItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Make the modal wider -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="prItemsModalLabel">PR Items</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table id="pr-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 10%;">SKU</th> <!-- Add SKU column header -->
                <th style="width: 10%;">PR Number</th> <!-- Add PR Number column header -->
                <th style="width: 30px;">Description</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 10%;">UOM</th>
                <th style="width: 10%;">Unit Price</th>
                <th style="width: 10%;">Total Price</th>
                <th style="width: 5%;">Action</th> <!-- Add Action column header -->
              </tr>
            </thead>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Editing PO Item -->
  <div class="modal fade" id="itemEditModal" tabindex="-1" aria-labelledby="itemEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="itemEditModalLabel">Edit PO Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateItem">
            <div class="row mb-3">
              <label for="edit_pr_number" class="col-sm-4 col-form-label">PR Number</label>
              <div class="col-sm-8">
                <input v-model="itemForm.pr_number" type="text" class="form-control" id="edit_pr_number" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_sku" class="col-sm-4 col-form-label">SKU</label>
              <div class="col-sm-8">
                <input v-model="itemForm.sku" type="text" class="form-control" id="edit_sku" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_pr_description" class="col-sm-4 col-form-label">Description PR</label>
              <div class="col-sm-8">
                <textarea v-model="itemForm.pr_description" class="form-control" id="edit_pr_description" rows="3" readonly></textarea>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea v-model="itemForm.description" class="form-control" id="edit_description" rows="3"></textarea>
                <div v-if="validationErrors.description" class="text-danger">{{ validationErrors.description[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_qty" class="col-sm-4 col-form-label">Qty</label>
              <div class="col-sm-8">
                <input v-model="itemForm.qty" type="number" step="0.01" class="form-control" id="edit_qty" />
                <div v-if="validationErrors.qty" class="text-danger">{{ validationErrors.qty[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_uom" class="col-sm-4 col-form-label">UOM</label>
              <div class="col-sm-8">
                <input v-model="itemForm.uom" type="text" class="form-control" id="edit_uom" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_unit_price" class="col-sm-4 col-form-label">Unit Price</label>
              <div class="col-sm-8">
                <input v-model="itemForm.unit_price" type="number" step="0.01" class="form-control" id="edit_unit_price" />
                <div v-if="validationErrors.unit_price" class="text-danger">{{ validationErrors.unit_price[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_discount" class="col-sm-4 col-form-label">Discount</label>
              <div class="col-sm-8">
                <input v-model="itemForm.discount" type="number" step="0.01" class="form-control" id="edit_discount" />
                <div v-if="validationErrors.discount" class="text-danger">{{ validationErrors.discount[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_vat" class="col-sm-4 col-form-label">VAT</label>
              <div class="col-sm-8">
                <input v-model="purchaseOrderForm.vat" type="number" step="0.01" class="form-control" id="edit_vat" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_total_price" class="col-sm-4 col-form-label">Total Price</label>
              <div class="col-sm-8">
                <input v-model="itemForm.total_price" type="text" class="form-control" id="edit_total_price" readonly />
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_location" class="col-sm-4 col-form-label">Location</label>
              <div class="col-sm-8">
                <input v-model="itemForm.location" type="text" class="form-control" id="edit_location" />
                <div v-if="validationErrors.location" class="text-danger">{{ validationErrors.location[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_campus" class="col-sm-4 col-form-label">Campus</label>
              <div class="col-sm-8">
                <input v-model="itemForm.campus" type="text" class="form-control" id="edit_campus" />
                <div v-if="validationErrors.campus" class="text-danger">{{ validationErrors.campus[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_division" class="col-sm-4 col-form-label">Division</label>
              <div class="col-sm-8">
                <input v-model="itemForm.division" type="text" class="form-control" id="edit_division" />
                <div v-if="validationErrors.division" class="text-danger">{{ validationErrors.division[0] }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="edit_department" class="col-sm-4 col-form-label">Department</label>
              <div class="col-sm-8">
                <input v-model="itemForm.department" type="text" class="form-control" id="edit_department" />
                <div v-if="validationErrors.department" class="text-danger">{{ validationErrors.department[0] }}</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Cancel Reason -->
  <!-- <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cancelReasonModalLabel">Cancel Purchase Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="cancelled_reason" class="form-label">Reason for Cancellation</label>
            <textarea v-model="cancelledReason" class="form-control" id="cancelled_reason" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-danger">Confirm Cancel</button>
        </div>
      </div>
    </div>
  </div> -->


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
                <button type="button" class="btn btn-success btn-sm" @click="openAddItemModal"> <i class="fas fa-plus-circle"></i> SELECT PO ITEMS</button>
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
          <h5 class="modal-title" id="addItemModalLabel">Select PO ITEM</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="po-items-table" class="table table-bordered border-secondary align-middle" width="100%"></table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" @click="selectAllPoItems">
            <i class="fas fa-check-double"></i> Cancel All
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

</template>

<style scoped>
/* .description-column {
  word-wrap: break-word;
  white-space: pre-wrap;
} */
.wrap-text {
  word-wrap: break-word;
  white-space: pre-wrap;
}
.wrap-cell {
  white-space: normal; /* Allow wrapping for specific columns */
  word-break: break-word; /* Ensure long words break as needed */
  max-width: 300px; /* Optional: Limit the wrapping area for better alignment */
}
.table-fixed-header thead th {
  position: sticky;
  top: 0;
  background-color: #9cc0e4; /* Optional: Add a background color to the header */
  z-index: 1; /* Ensure the header is above the table body */
}
</style>
