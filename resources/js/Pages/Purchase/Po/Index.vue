<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  purchaseOrders: { type: Array, required: true },
  users: Array,
  suppliers: Array,
  prItems: Array,
  purchaseRequests: Array,
  currentUser: Object,
  purchaseOrder: Object,
});

const isEdit = ref(false);
const purchaseOrderForm = reactive({
  date: '',
  user_id: null,
  supplier_id: '',
  currency: 'USD',
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

const openCreatePage = () => {
  isEdit.value = false;
  Object.assign(purchaseOrderForm, {
    id: null, po_number: '', date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', supplier_id: '', currency: 'USD', currency_rate: 1, payment_term: '', purpose: '', vat: 0, items: [], total_amount_usd: 0,
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
      currency: purchaseOrderForm.currency, // Ensure currency is included
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
  const qty = parseFloat(prItem.qty) || 0;
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

      $('#purchase-order').on('click', '.dtr-details .btn-cancel', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) cancelPurchaseOrder(rowData.id);
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
            { data: 'qty' },
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
                  <label for="user_id" class="col-sm-4 col-form-label">User</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.name" type="text" class="form-control" id="user_name" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_position" class="col-sm-4 col-form-label">Position</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.position" type="text" class="form-control" id="user_position" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_card_id" class="col-sm-4 col-form-label">Card ID</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.card_id" type="text" class="form-control" id="user_card_id" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_phone" class="col-sm-4 col-form-label">Phone</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.phone" type="text" class="form-control" id="user_phone" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="user_email" class="col-sm-4 col-form-label">Email</label>
                  <div class="col-sm-8">
                    <input v-model="props.currentUser.email" type="text" class="form-control" id="user_email" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.currency" type="text" class="form-control" id="currency" />
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
                    <select v-model="purchaseOrderForm.supplier_id" class="form-select" id="supplier_id">
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
                    <input v-model="purchaseOrderForm.supplier_phone" type="text" class="form-control" id="supplier_phone" readonly />
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="supplier_address" class="col-sm-4 col-form-label">Supplier Address</label>
                  <div class="col-sm-8">
                    <textarea v-model="purchaseOrderForm.supplier_address" class="form-control" id="supplier_address" rows="3" readonly></textarea>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.payment_term" type="text" class="form-control" id="payment_term" readonly />
                    <div v-if="validationErrors.payment_term" class="text-danger">{{ validationErrors.payment_term[0] }}</div>
                  </div>
                </div>
                <div class="row mb-1">
                  <label for="date" class="col-sm-4 col-form-label">Date</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseOrderForm.date" type="date" class="form-control" id="date" />
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
  <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
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
  </div>
</template>

<style scoped>
.description-column {
  word-wrap: break-word;
  white-space: pre-wrap;
}
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
