<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import SupplierFormModal from '@/Components/SupplierFormModal.vue';
import PdfViewer from '@/Components/PdfViewer.vue';
import { formatCurrency, formatDate, getTransactionType, getPaymentType, getFileThumbnail, openPdfViewer } from './helpers.js'; // Import helper functions

toastr.options = {
  progressBar: true,
  closeButton: true,
  timeOut: 5000,
};

const props = defineProps({
  poItems: Array,
  purchaseRequests: Array,
  purchaseOrders: Array,
  suppliers: Array,
  users: Array,
  currentUser: Object,
  cashRequests: Array,
  prItems: Array,
  purchaseInvoices: Array,
  vatRate: Number,
});

const form = reactive({
  id: null, // Add the id field here
  transaction_type: null,
  cash_ref: null,
  payment_type: 1,
  invoice_date: '',
  invoice_no: '',
  supplier: '',
  currency: 1,
  currency_rate: null,
  payment_term: null,
  total_amount: null,
  paid_amount: null,
  vat_rate: props.vatRate || 0,
  created_by: null,
  items: [],
  total_discount: 0,
  service_charge: 0,
  discount_total: 0,
  attachments: [],
});

const calculateTotalDiscount = () => {
  form.total_discount = form.items.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0).toFixed(2);
};

const calculateTotalAmount = () => {
  form.total_amount = form.items.reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.unit_price) || 0), 0);
};

const calculateTotalPaidAmount = () => {
  form.paid_amount = form.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0);
};

const calculateServiceChargeForItems = () => {
  const itemCount = form.items.length;
  if (itemCount > 0 && form.service_charge > 0 && form.service_charge !== '') {
    const serviceChargePerItem = form.service_charge / itemCount;
    form.items.forEach(item => {
      item.service_charge = parseFloat(serviceChargePerItem);
      item.service_charge_overwritten = false;
    });
  } else {
    form.items.forEach(item => {
      item.service_charge = item.service_charge || 0;
    });
  }
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
};

const calculateItemDiscounts = () => {
  const itemCount = form.items.length;
  if (itemCount > 0 && form.discount_total > 0 && form.discount_total !== '') {
    const totalPriceSum = form.items.reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.unit_price) || 0), 0);
    const rateDiscount = form.discount_total / totalPriceSum;
    form.items.forEach(item => {
      item.discount = (parseFloat(item.qty) * parseFloat(item.unit_price) || 0) * rateDiscount;
      item.discount_overwritten = false;
    });
  } else {
    form.items.forEach(item => {
      item.discount = item.discount || 0; // Ensure discount is retrieved from the database
    });
  }
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
};

watch(() => form.items, () => {
  calculateTotalDiscount();
  calculateTotalAmount();
  calculateServiceChargeForItems();
  calculateTotalPaidAmount();
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
}, { deep: true });

watch(() => form.service_charge, () => {
  calculateServiceChargeForItems();
  calculateTotalAmount();
  calculateTotalPaidAmount();
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

watch(() => form.discount_total, () => {
  calculateItemDiscounts();
  calculateTotalDiscount();
  calculateTotalAmount();
  calculateTotalPaidAmount();
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

watch(() => [form.service_charge, form.discount_total], () => {
  form.items.forEach(item => {
    item.paid_amount = calculateTotalPrice(item);
  });
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

const prItemsTableInstance = ref(null);
const invoiceItemsTableInstance = ref(null);
const poItemsTableInstance = ref(null);
const invoiceListTableInstance = ref(null);

const editItemForm = reactive({
  id: null,
  description: '',
  qty: 0.0,
  unit_price: 0.0,
  discount: 0.0,
  vat: 0.0,
  return: 0.0,
  retention: 0.0,
  service_charge: 0.0,
  campus: '',
  division: '',
  department: '',
  location: '',
  purpose: '',
  remark: '',
  pr_number: '',
  po_number: '',
  item_code: '',
  paid_amount: 0.0,
  total_price: 0,
  uom: '',
  service_charge_overwritten: false,
  deposit: 0.0,
  stop_purchase: 0,
});

const calculateGrandTotal = () => {
  const { qty, unit_price, discount, return: returnAmount, retention, vat, service_charge, deposit } = editItemForm;
  let vatAmount;

  if (form.payment_type === 2) { // Deposit
    vatAmount = (deposit * vat) / 100;
    editItemForm.paid_amount = (parseFloat(deposit) + parseFloat(vatAmount)).toFixed(2);
  } else {
    vatAmount = ((qty * unit_price - discount) * vat) / 100;
    editItemForm.total_price = (qty * unit_price);
    editItemForm.paid_amount = (qty * unit_price) - discount + parseFloat(vatAmount) - returnAmount - retention + parseFloat(service_charge);
  }
};

watch(() => [editItemForm.qty, editItemForm.unit_price, editItemForm.discount, editItemForm.return, editItemForm.retention, editItemForm.vat, editItemForm.service_charge, editItemForm.deposit], calculateGrandTotal);

const grandTotal = computed(() => {
  const total = form.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0);
  const grandTotal = total;
  return isNaN(grandTotal) ? '0.00' : grandTotal.toFixed(2);
});

const totalServiceCharge = computed(() => {
  return form.items.reduce((sum, item) => sum + (parseFloat(item.service_charge) || 0), 0).toFixed(2);
});

watch(() => [editItemForm.qty, editItemForm.unit_price, editItemForm.discount, editItemForm.return, editItemForm.retention, editItemForm.vat, editItemForm.service_charge, editItemForm.deposit], calculateGrandTotal);

const formErrors = reactive({});
const editItemFormErrors = reactive({});

const calculateTotalPrice = (item) => {
  const { qty, unit_price, discount, return: returnAmount, retention, vat, service_charge = 0, deposit = 0 } = item;
  const total_price = qty * unit_price;
  let vatAmount;

  if (form.payment_type === 2) { // Deposit
    vatAmount = (deposit * vat) / 100;
    return (parseFloat(deposit) + parseFloat(vatAmount)).toFixed(2);
  } else {
    vatAmount = ((total_price - discount) * vat) / 100;
    return (total_price - discount - returnAmount - retention + parseFloat(vatAmount) + parseFloat(service_charge)).toFixed(2);
  }
};

const formatNumber = (value, decimalPlaces = 2) => {
  return isNaN(parseFloat(value)) ? '0.00' : parseFloat(value).toFixed(decimalPlaces);
};

const prepareInvoiceItems = (items) => {
  const totalPriceSum = items.reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.unit_price) || 0), 0);
  const rateDiscount = form.discount_total / totalPriceSum;

  return items.map(item => ({
    ...item,
    total_price: calculateTotalPrice(item),
    service_charge: item.service_charge_overwritten ? parseFloat(item.service_charge) : (form.service_charge > 0 ? parseFloat(form.service_charge / form.items.length).toFixed(10) : parseFloat(item.service_charge) || 0),
    discount: form.discount_total > 0 ? (parseFloat(item.total_price) * rateDiscount).toFixed(2) : formatNumber(item.discount), // Ensure discount is calculated correctly
    service_charge: formatNumber(item.service_charge, 4), // Ensure service charge is formatted correctly
    deposit: item.deposit || 0,
  }));
};

const handleFormErrors = (error) => {
  if (error.response && error.response.status === 422) {
    const errors = error.response.data.messages;
    Object.keys(errors).forEach(key => {
      formErrors[key] = errors[key][0];
      toastr.error(errors[key][0]);
    });
  } else {
    toastr.error('Failed to submit invoice.');
    console.error('Error:', error);
  }
};

const isEditMode = ref(false);

const clearForm = () => {
  isEditMode.value = false;
  Object.assign(form, {
    id: null,
    transaction_type: null,
    cash_ref: null,
    payment_type: 1,
    invoice_date: '',
    invoice_no: '',
    supplier: '',
    currency: 1,
    currency_rate: null,
    payment_term: null,
    total_amount: null,
    paid_amount: null,
    vat_rate: props.vatRate || 0,
    created_by: null,
    items: [],
    total_discount: 0,
    service_charge: 0,
    discount_total: 0,
    attachments: [],
  });
  invoiceItemsTableInstance.value.clear().draw();
  $('#supplier').val(null).trigger('change');
};

const submitForm = async () => {
  form.cash_ref = $('#cash_ref').val(); // Ensure cash_ref is captured from select2
  form.items = prepareInvoiceItems(form.items);
  calculateTotalAmount();
  calculateServiceChargeForItems();
  calculateItemDiscounts(); // Ensure discounts are calculated correctly before submitting

  let response = null;
  if (isEditMode.value && form.id) {
    response = await updateInvoice();
  } else {
    response = await createInvoice();
  }

  if (response) {
    clearForm();
    updateInvoiceListTable(response.purchaseInvoices); // Update the table with the new data
    $('#nav-list-tab').tab('show'); // Switch to the invoice list tab
  }
};

const updateInvoiceListTable = (invoices) => {
  try {
    const formattedInvoices = invoices.map(invoice => ({
      id: invoice.id,
      pi_number: invoice.pi_number || '',
      invoice_date: invoice.invoice_date || '',
      supplier_name: invoice.supplier ? invoice.supplier.name : '',
      // total_amount: invoice.total_amount || 0,
      paid_amount: invoice.paid_amount || 0,
      currency: invoice.currency || 0,
      currency_rate: invoice.currency_rate || 0,
      paid_usd: invoice.paid_usd || 0,
      transaction_type: invoice.transaction_type || 0,
      payment_type: invoice.payment_type || 0,
    }));
    invoiceListTableInstance.value.clear().rows.add(formattedInvoices).draw(); // Update the table
  } catch (error) {
    console.error('Error updating invoice list table:', error);
    toastr.error('Failed to update invoice list table.');
  }
};

const createInvoice = async () => {
  try {

    if (Array.isArray(form.items)) {
      form.items.forEach(item => {
        item.pi_number = null;
        item.total_usd = item.qty * item.unit_price;
        item.total_khr = item.total_usd * form.currency_rate;
        item.requested_by = form.created_by;
        item.invoice_date = form.invoice_date;
        item.payment_type = form.payment_type;
        item.invoice_no = form.invoice_no;
        item.purchased_by = form.created_by;

        if (props.prItems) {
          const prItem = props.prItems.find(pr => pr.id === item.pr_item);
          if (prItem) {
            item.pr_number = prItem.purchase_request_id;
            item.item_code = prItem.product.sku;
          }
        }

        item.transaction_type = parseInt(item.transaction_type);
        item.payment_type = parseInt(item.payment_type);
        item.currency = parseInt(item.currency);

        if (form.service_charge != 0 && form.service_charge != '') {
          item.service_charge = parseFloat((form.service_charge / form.items.length).toFixed(10));
          item.service_charge_overwritten = false;
        } else {
          item.service_charge = parseFloat(item.service_charge) || 0;
          item.service_charge_overwritten = true;
        }

        item.discount = formatNumber(item.discount); // Ensure discount is formatted correctly
      });
    }

    const response = await axios.post('/invoices', form);
    toastr.success('Invoice submitted successfully.');
    return response.data; // Return the updated data
  } catch (error) {
    handleFormErrors(error);
    return null;
  }
};

const updateInvoice = async () => {
  try {
    if (!props.currentUser || !props.currentUser.id) throw new Error('Current user is not defined');
    form.created_by = props.currentUser.id;
    form.transaction_type = parseInt(form.transaction_type);
    form.payment_type = parseInt(form.payment_type);
    form.currency = parseInt(form.currency);
    form.vat_rate = parseFloat(form.vat_rate);
    form.discount_total = parseFloat(form.discount_total);
    form.cash_ref = form.cash_ref ? parseInt(form.cash_ref) : null; // Ensure cash_ref is captured
    if (form.transaction_type !== 2 && form.cash_ref !== null) form.cash_ref = parseInt(form.cash_ref);
    else form.cash_ref = null;

    if (Array.isArray(form.items)) {
      form.items.forEach(item => {
        item.pi_number = null;
        item.total_usd = item.qty * item.unit_price;
        item.total_khr = item.total_usd * form.currency_rate;
        item.requested_by = form.created_by;
        item.invoice_date = form.invoice_date;
        item.payment_type = form.payment_type;
        item.invoice_no = form.invoice_no;
        item.purchased_by = form.created_by;

        if (props.prItems) {
          const prItem = props.prItems.find(pr => pr.id === item.pr_item);
          if (prItem) {
            item.pr_number = prItem.purchase_request_id;
            item.item_code = prItem.product.sku;
          }
        }

        item.transaction_type = parseInt(item.transaction_type);
        item.payment_type = parseInt(item.payment_type);
        item.currency = parseInt(item.currency);

        item.discount = formatNumber(item.discount); // Ensure discount is formatted correctly
      });
    }

    const response = await axios.put(`/invoices/${form.id}`, form);
    toastr.success('Invoice updated successfully.');
    return response.data; // Return the updated data
  } catch (error) {
    handleFormErrors(error);
    return null;
  }
};

const fetchPrItems = async (prNumber) => {
  try {
    const response = await axios.get(`/pr-items?pr_number=${prNumber}`);
    prItemsTableInstance.value.clear().rows.add(response.data).draw();
  } catch (error) {
    console.error('Error fetching PR items:', error);
  }
};

const fetchPoItems = async (poNumber) => {
  try {
    const response = await axios.get(`/po-items?po_number=${poNumber}`);
    poItemsTableInstance.value.clear().rows.add(response.data).draw();
  } catch (error) {
    console.error('Error fetching PO items:', error);
  }
};

const openPrItemsModal = () => {
  const modalElement = document.getElementById('prItemsModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    prItemsTableInstance.value.clear().draw();

    if (!document.getElementById('pr-number-filter')) {
      const prNumberSelect = $('<select id="pr-number-filter" class="form-select form-select-sm" style="width: 200px; margin-left: 10px;"><option value="">Select PR Number</option></select>')
        .appendTo('#pr-items-table_filter')
        .on('change', function () {
          const val = $(this).val();
          if (val) fetchPrItems(val);
          else prItemsTableInstance.value.clear().draw();
        });

      props.purchaseRequests.forEach(purchaseRequest => {
        if (purchaseRequest.pr_number) {
          prNumberSelect.append(`<option value="${purchaseRequest.pr_number}">${purchaseRequest.pr_number}</option>`);
        }
      });

      $('#pr-number-filter').select2({
        placeholder: 'Select PR Number',
        allowClear: true,
        width: 'resolve',
        dropdownParent: $('#prItemsModal')
      });

      $('#pr-items-table_filter').css('display', 'flex').css('justify-content', 'flex-end');
    }
  });

  modalElement.addEventListener('hidden.bs.modal', () => {
    $('#pr-number-filter').val('').trigger('change');
  });
};

const openPoItemsModal = () => {
  const modalElement = document.getElementById('poItemsModal');
  if (!modalElement) {
    console.error('PO Items Modal element not found');
    return;
  }
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    poItemsTableInstance.value.clear().draw();

    if (!document.getElementById('po-number-filter')) {
      const poNumberSelect = $('<select id="po-number-filter" class="form-select form-select-sm" style="width: 200px; margin-left: 10px;"><option value="">Select PO Number</option></select>')
        .appendTo('#po-items-table_filter')
        .on('change', function () {
          const val = $(this).val();
          if (val) fetchPoItems(val);
          else poItemsTableInstance.value.clear().draw();
        });

      props.purchaseOrders.forEach(purchaseOrder => {
        if (purchaseOrder.po_number) {
          poNumberSelect.append(`<option value="${purchaseOrder.po_number}">${purchaseOrder.po_number}</option>`);
        }
      });

      $('#po-number-filter').select2({
        placeholder: 'Select PO Number',
        allowClear: true,
        width: 'resolve',
        dropdownParent: $('#poItemsModal')
      });

      $('#po-items-table_filter').css('display', 'flex').css('justify-content', 'flex-end');
    }
  });

  modalElement.addEventListener('hidden.bs.modal', () => {
    $('#po-number-filter').val('').trigger('change');
  });
};

const selectPrItem = (prItem) => {
  const existingItem = form.items.find(item => item.pr_item === prItem.id);
  if (!existingItem) {
    const qty = form.payment_type === 2 ? 0 : parseFloat(prItem.qty_pending) || 0;
    const unit_price = parseFloat(prItem.unit_price) || 0;
    const discount = 0;
    const service_charge = 0;
    const total_price = calculateTotalPrice({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge });

    const receivedQty = form.items.reduce((sum, currentItem) => {
      return sum + (currentItem.pr_item === prItem.id ? parseFloat(currentItem.qty) : 0);
    }, 0);

    const pendingQty = prItem.qty - prItem.qty_cancel - receivedQty;

    if (qty > pendingQty) {
      toastr.warning('The quantity of the PR item cannot exceed the pending quantity.');
      return;
    }

    form.items.push({
      pr_item: prItem.id,
      po_item: null,
      remark: prItem.remark,
      qty: qty,
      uom: prItem.uom,
      unit_price: unit_price,
      discount: discount,
      vat: form.vat_rate,
      return: 0,
      retention: 0,
      service_charge: service_charge,
      paid_amount: total_price,
      campus: prItem.campus,
      division: prItem.division,
      department: prItem.department,
      purpose: prItem.purchase_request.purpose,
      location: '',
      description: `${prItem.product.product_description} | ${prItem.remark}`,
      pr_number: prItem.purchase_request.pr_number,
      po_number: '',
      item_code: prItem.product.sku,
      requested_by: prItem.purchase_request.requested_by,
      cash_ref: form.cash_ref,
      transaction_type: form.transaction_type,
      payment_term: form.payment_term,
      purchased_by: form.created_by,
      currency_rate: form.currency_rate,
      currency: form.currency,
      invoice_date: form.invoice_date,
      payment_type: form.payment_type,
      invoice_no: form.invoice_no,
      total_price: total_price,
      deposit: form.payment_type === 2 ? parseFloat(prItem.deposit) || 0 : 0, // Set deposit correctly based on payment type
      stop_purchase: 0, // Initialize stop_purchase to 0
    });

    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();

    const rowIndex = prItemsTableInstance.value.row((idx, data) => data.id === prItem.id).index();
    prItemsTableInstance.value.row(rowIndex).remove().draw();
  } else {
    toastr.warning('Item already exists in the invoice items table.');
  }
};
const selectPoItem = (poItem) => {
  const existingItem = form.items.find(item => item.po_item === poItem.id);
  if (!existingItem) {
    const qty = form.payment_type === 2 ? 0 : parseFloat(poItem.pending) || 0;
    const unit_price = parseFloat(poItem.unit_price) || 0;
    const discount = parseFloat(poItem.discount) || 0;
    const service_charge = 0;
    const total_price = calculateTotalPrice({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge });

    const receivedQty = form.items.reduce((sum, currentItem) => {
      return sum + (currentItem.po_item === poItem.id ? parseFloat(currentItem.qty) : 0);
    }, 0);

    const pendingQty = poItem.qty - poItem.cancelled_qty - receivedQty;

    if (qty > pendingQty) {
      toastr.warning('The quantity of the PO item cannot exceed the pending quantity.');
      return;
    }

    const prNumber = poItem.purchase_request.pr_number;
    form.items.push({
      pr_item: poItem.pr_item_id,
      po_item: poItem.id,
      remark: '',
      qty: qty,
      uom: poItem.uom,
      unit_price: unit_price,
      discount: discount,
      vat: form.vat_rate,
      return: 0,
      retention: 0,
      service_charge: service_charge,
      paid_amount: total_price,
      campus: poItem.campus,
      division: poItem.division,
      department: poItem.department,
      purpose: poItem.purchase_order.purpose,
      location: poItem.location,
      description: `${poItem.product.product_description} | ${poItem.description}`,
      pr_number: prNumber,
      po_number: poItem.purchase_order.po_number,
      item_code: poItem.product.sku,
      requested_by: poItem.purchase_request.requested_by,
      cash_ref: form.cash_ref,
      transaction_type: form.transaction_type,
      payment_term: form.payment_term,
      purchased_by: form.created_by,
      currency_rate: form.currency_rate,
      currency: form.currency,
      invoice_date: form.invoice_date,
      payment_type: form.payment_type,
      invoice_no: form.invoice_no,
      total_price: total_price,
      deposit: form.payment_type === 2 ? parseFloat(poItem.deposit) || 0 : 0, // Set deposit correctly based on payment type
      stop_purchase: 0, // Initialize stop_purchase to 0
    });

    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();

    const rowIndex = poItemsTableInstance.value.row((idx, data) => data.id === poItem.id).index();
    poItemsTableInstance.value.row(rowIndex).remove().draw();
  } else {
    toastr.warning('Item already exists in the invoice items table.');
  }
};

const openCreateSupplierModal = () => {
  const modal = new bootstrap.Modal(document.getElementById('supplierFormModal'));
  modal.show();
};

const initializeSupplierSelect = () => {
  $('#supplier').select2({
    data: props.suppliers.map(supplier => ({ id: supplier.id, text: supplier.name })),
    dropdownParent: $('#supplier').parent(),
    placeholder: 'Select Supplier',
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
    form.supplier = e.params.data.id;
  }).on('select2:unselect', function () {
    form.supplier = '';
    form.vat_rate = 0;
  });
};

watch(() => form.supplier, (newSupplierId) => {
  if (newSupplierId) {
    // Enable the buttons when a supplier is selected
    document.querySelectorAll('.btn-select-item').forEach(button => button.disabled = false);
  } else {
    // Disable the buttons when no supplier is selected
    document.querySelectorAll('.btn-select-item').forEach(button => button.disabled = true);
  }
});

const addSupplier = (newSupplier) => {
  props.suppliers.push(newSupplier);
  form.supplier = newSupplier.id;
};

const deleteInvoice = async (invoiceId) => {
  swal({
    title: 'Are you sure?', text: 'You will not be able to recover this invoice!', icon: 'warning',
    buttons: { cancel: { text: 'No, cancel!', visible: true, className: 'btn btn-secondary', closeModal: true },
               confirm: { text: 'Yes, delete it!', visible: true, className: 'btn btn-danger', closeModal: true } },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/invoices/${invoiceId}`);
        toastr.success('Invoice deleted successfully.');
        const rowIndex = invoiceListTableInstance.value.row((idx, data) => data.id === invoiceId).index();
        invoiceListTableInstance.value.row(rowIndex).remove().draw();
      } catch (error) {
        toastr.error('Failed to delete invoice.');
        console.error('Error:', error);
      }
    }
  });
};

const editInvoice = async (invoiceId) => {
  try {
    isEditMode.value = true;
    const response = await axios.get(`/invoices/${invoiceId}/edit`);
    const invoice = response.data.invoice; // Ensure the correct data is accessed

    if (!invoice) {
      throw new Error('Invoice data not found');
    }

    Object.assign(form, {
      id: invoice.id, // Ensure the id is captured here
      transaction_type: invoice.transaction_type,
      cash_ref: invoice.cash_ref, // Retain the old cash_ref value
      payment_type: invoice.payment_type,
      invoice_date: invoice.invoice_date,
      invoice_no: invoice.invoice_no,
      supplier: invoice.supplier.id,
      currency: invoice.currency,
      currency_rate: formatNumber(invoice.currency_rate),
      payment_term: invoice.payment_term,
      total_amount: formatNumber(invoice.total_amount),
      paid_amount: formatNumber(invoice.paid_amount),
      vat_rate: formatNumber(invoice.vat_rate),
      service_charge: formatNumber(invoice.service_charge),
      discount_total: formatNumber(invoice.discount_total),
      items: prepareInvoiceItems(invoice.items.map(item => ({
        pr_item: item.pr_item,
        po_item: item.po_item,
        description: item.description,
        remark: item.remark,
        qty: formatNumber(item.qty),
        uom: item.uom,
        unit_price: formatNumber(item.unit_price),
        discount: formatNumber(item.discount), // Ensure discount is formatted correctly
        vat: formatNumber(item.vat),
        return: formatNumber(item.return),
        retention: formatNumber(item.retention),
        paid_amount: formatNumber(item.paid_amount),
        campus: item.campus,
        division: item.division,
        department: item.department,
        location: item.location,
        purpose: item.purchase_order?.purpose || item.purchase_request?.purpose || '',
        pr_number: item.purchase_request?.pr_number || '',
        po_number: item.purchase_order?.po_number || '',
        item_code: item.product?.sku || '',
        requested_by: item.requested_by,
        cash_ref: item.cash_ref, // Ensure cash_ref is retained for each item
        transaction_type: item.transaction_type,
        payment_term: item.payment_term,
        purchased_by: item.purchased_by,
        currency_rate: formatNumber(item.currency_rate),
        currency: item.currency,
        invoice_date: item.invoice_date,
        payment_type: item.payment_type,
        invoice_no: item.invoice_no,
        service_charge: item.service_charge_overwritten ? parseFloat(item.service_charge) : (form.service_charge > 0 ? parseFloat(form.service_charge / form.items.length).toFixed(10) : parseFloat(item.service_charge) || 0),
        discount: formatNumber(item.discount), // Ensure discount is formatted correctly
        service_charge_overwritten: item.service_charge !== 0,
        discount_overwritten: item.discount !== 0,
        deposit: formatNumber(item.deposit), // Ensure deposit is fetched correctly
        stop_purchase: item.stop_purchase || 0,
      }))),
      attachments: invoice.attachments || [],
    });

    calculateTotalAmount();
    calculateServiceChargeForItems();
    calculateItemDiscounts(); // Ensure discounts are calculated correctly
    calculateTotalDiscount(); // Ensure total discount is calculated correctly
    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
    $('#supplier').val(invoice.supplier.id).trigger('change');
    $('#nav-create-tab').tab('show');
  } catch (error) {
    toastr.error('Failed to load invoice data.');
    console.error('Error:', error);
  }
};

const isCreditTransaction = computed(() => form.transaction_type == 2);

watch(() => form.transaction_type, (newTransactionType) => {
  console.log('Transaction type selected:', newTransactionType);
  if (!isEditMode.value) { // Prevent resetting cash_ref in edit mode
    if (newTransactionType === 2) {
      form.cash_ref = null; // Clear cash_ref for Credit transactions
    } else if (newTransactionType === 1 || newTransactionType === 3) {
      form.cash_ref = null; // Ensure cash_ref is not auto-selected
    }
  }
  if (newTransactionType === 1) {
    form.payment_term = 4;
  } else if (form.payment_term === 4) {
    form.payment_term = null;
  }
});

watch(() => form.transaction_type, (newValue) => {
  if (formErrors.transaction_type) {
    delete formErrors.transaction_type;
  }
  form.items.forEach(item => {
    item.transaction_type = newValue;
  });
});

watch(() => form.cash_ref, (newValue) => {
  if (formErrors.cash_ref) {
    delete formErrors.cash_ref;
  }
});

watch(() => form.payment_type, (newValue) => {
  if (formErrors.payment_type) {
    delete formErrors.payment_type;
  }
  if (newValue === 2) { // Deposit
    form.items.forEach(item => {
      item.qty = 0;
      // Preserve existing deposit values instead of setting to 0
      item.deposit = item.deposit || 0;
    });
  } else {
    form.items.forEach(item => {
      item.qty = item.qty || 0;
      item.deposit = 0; // Reset deposit to 0 when not Deposit
    });
  }
  calculateTotalPaidAmount();
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

watch(() => form.invoice_date, (newValue) => {
  if (formErrors.invoice_date) {
    delete formErrors.invoice_date;
  }
});

watch(() => form.invoice_no, (newValue) => {
  if (formErrors.invoice_no) {
    delete formErrors.invoice_no;
  }
});

watch(() => form.supplier, async (newSupplierId) => {
  if (newSupplierId && !isEditMode.value) {
  }
});

watch(() => form.vat_rate, (newVatRate) => {
  form.items.forEach(item => {
    item.vat = newVatRate;
    item.total_price = calculateTotalPrice(item);
    item.paid_amount = calculateTotalPrice(item);
  });
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

watch(() => form.currency, (newValue) => {
  if (formErrors.currency) {
    delete formErrors.currency;
  }
});

watch(() => form.currency_rate, (newValue) => {
  if (formErrors.currency_rate) {
    delete formErrors.currency_rate;
  }
  form.items.forEach(item => {
    item.currency_rate = newValue;
  });
});

watch(() => form.payment_term, (newValue) => {
  if (formErrors.payment_term) {
    delete formErrors.payment_term;
  }
  form.items.forEach(item => {
    item.payment_term = newValue;
  });
});

watch(() => form.total_amount, (newValue) => {
  if (formErrors.total_amount) {
    delete formErrors.total_amount;
  }
});

watch(() => form.paid_amount, (newValue) => {
  if (formErrors.paid_amount) {
    delete formErrors.paid_amount;
  }
});

const isServiceChargeDisabled = computed(() => {
  return form.service_charge !== 0 && form.service_charge !== '' && editItemForm.service_charge !== 0 && !editItemForm.service_charge_overwritten;
});

const editItem = (rowIndex) => {
  const item = form.items[rowIndex];
  editItemForm.id = item.id || rowIndex;
  Object.assign(editItemForm, item);
  const modal = new bootstrap.Modal(document.getElementById('editInvoiceItemModal'));
  modal.show();
  if (form.discount_total > 0 && form.discount_total !== '') {
    document.getElementById('editDiscount').setAttribute('disabled', 'true');
    document.getElementById('editDiscount').classList.add('bg-light');
  } else {
    document.getElementById('editDiscount').removeAttribute('disabled');
    document.getElementById('editDiscount').classList.remove('bg-light');
  }
  if (isServiceChargeDisabled.value) {
    document.getElementById('editServiceCharge').setAttribute('disabled', 'true');
    document.getElementById('editServiceCharge').classList.add('bg-light');
  } else {
    document.getElementById('editServiceCharge').removeAttribute('disabled');
    document.getElementById('editServiceCharge').classList.remove('bg-light');
  }
  if (form.payment_type === 1) { // Final
    document.getElementById('editDeposit').setAttribute('disabled', 'true');
    document.getElementById('editDeposit').classList.add('bg-light');
  } else {
    document.getElementById('editDeposit').removeAttribute('disabled');
    document.getElementById('editDeposit').classList.remove('bg-light');
  }
  if (form.payment_type === 2) { // Deposit
    document.getElementById('editQty').setAttribute('disabled', 'true');
    document.getElementById('editQty').classList.add('bg-light');
    document.getElementById('editDeposit').removeAttribute('disabled');
    document.getElementById('editDeposit').classList.remove('bg-light');
  } else {
    document.getElementById('editQty').removeAttribute('disabled');
    document.getElementById('editQty').classList.remove('bg-light');
    document.getElementById('editDeposit').setAttribute('disabled', 'true');
    document.getElementById('editDeposit').classList.add('bg-light');
  }
  // Ensure deposit field is correctly set in the editItemForm
  editItemForm.deposit = item.deposit || 0;
  // Ensure qty field is correctly set in the editItemForm
  editItemForm.qty = item.qty || 0;
};

const updateInvoiceItem = () => {
  try {
    const index = form.items.findIndex(item => item.id === editItemForm.id || form.items.indexOf(item) === editItemForm.id);
    if (index !== -1) {
      Object.assign(form.items[index], editItemForm);
      form.items[index].service_charge_overwritten = form.service_charge === 0 || form.service_charge === '' || editItemForm.service_charge !== 0;
      form.items[index].total_price = calculateTotalPrice(form.items[index]);
      form.items[index].paid_amount = calculateTotalPrice(form.items[index]);
      invoiceItemsTableInstance.value.row(index).data(form.items[index]).draw();
      toastr.success('Invoice item updated successfully.');
    } else {
      throw new Error('Item not found');
    }
    const modal = bootstrap.Modal.getInstance(document.getElementById('editInvoiceItemModal'));
    modal.hide();
    if (form.discount_total > 0 && form.discount_total !== '') {
      calculateItemDiscounts();
    } else {
      calculateTotalDiscount();
    }
  } catch (error) {
    toastr.error('Failed to update invoice item.');
    console.error('Error:', error);
  }
};

const removeItem = (rowIndex) => {
  form.items.splice(rowIndex, 1);
  invoiceItemsTableInstance.value.row(rowIndex).remove().draw();
};

const duplicateItem = (rowIndex) => {
  const item = { ...form.items[rowIndex] };
  if (item.po_item) {
    const poItem = props.poItems.find(po => po.id === item.po_item);
    if (poItem) {
      const receivedQty = form.items.reduce((sum, currentItem) => {
        return sum + (currentItem.po_item === item.po_item ? parseFloat(currentItem.qty) : 0);
      }, 0);

      const pendingQty = poItem.qty - poItem.cancelled_qty - receivedQty;

      if (parseFloat(item.qty) > pendingQty) {
        toastr.warning('The quantity of the PO item cannot exceed the pending quantity.');
        return;
      }
    }
  }
  if (item.pr_item) {
    const prItem = props.prItems.find(pr => pr.id === item.pr_item);
    if (prItem) {
      const receivedQty = form.items.reduce((sum, currentItem) => {
        return sum + (currentItem.pr_item === item.pr_item ? parseFloat(currentItem.qty) : 0);
      }, 0);

      const pendingQty = prItem.qty - prItem.qty_cancel - receivedQty;

      if (parseFloat(item.qty) > pendingQty) {
        toastr.warning('The quantity of the PR item cannot exceed the pending quantity.');
        return;
      }
    }
  }
  item.id = form.items.length;
  item.service_charge_overwritten = form.service_charge === 0 || form.service_charge === '';
  item.service_charge = form.service_charge === 0 || form.service_charge === '' ? item.service_charge : 0;
  form.items.push(item);
  calculateServiceChargeForItems();
  calculateItemDiscounts(); // Ensure discounts are recalculated when an item is duplicated
  invoiceItemsTableInstance.value.row.add(item).draw();
};

const getPrNumberById = (id) => {
  const prItem = props.prItems.find(pr => pr.id === id);
  return prItem ? prItem.purchase_request.pr_number : '';
};

const getPoNumberById = (id) => {
  const poItem = props.poItems.find(po => po.id === id);
  return poItem ? poItem.purchase_order.po_number : '';
};

const filteredCashRequests = ref([]);

watch(() => form.transaction_type, async (newTransactionType) => {
  try {
    if (!newTransactionType) {
      filteredCashRequests.value = []; // Clear the list if transaction_type is not set
      return;
    }

    const response = await axios.get('/filter-cash-requests', {
      params: { transaction_type: newTransactionType }, // Ensure transaction_type is sent as a parameter
    });

    filteredCashRequests.value = response.data;
    console.log('Filtered Cash Requests:', filteredCashRequests.value); // Log the data to verify
  } catch (error) {
    console.error('Error fetching filtered cash requests:', error);
    toastr.error('Failed to fetch filtered cash requests.');
  }
});

const attachFile = async (invoiceId, file) => {
  try {
    const formData = new FormData();
    formData.append('file', file);
    const response = await axios.post(`/invoices/${invoiceId}/attach-file`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    toastr.success('File attached successfully.');
    return response.data.attachment;
  } catch (error) {
    toastr.error('Failed to attach file.');
    console.error('Error:', error);
  }
};

const deleteFile = async (attachmentId) => {
  try {
    await axios.delete(`/invoices/attachments/${attachmentId}`);
  } catch (error) {
    toastr.error('Failed to delete file.');
    console.error('Error:', error);
  }
};

const updateFile = async (attachmentId, file) => {
  try {
    const formData = new FormData();
    formData.append('file', file);
    const response = await axios.post(`/invoices/attachments/${attachmentId}/update-file`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    toastr.success('File updated successfully.');
    return response.data.attachment;
  } catch (error) {
    toastr.error('Failed to update file.');
    console.error('Error:', error);
  }
};

const handleFileUpload = async (file) => {
  if (file && form.id) {
    const formData = new FormData();
    formData.append('file', file);
    try {
      const response = await axios.post(`/invoices/${form.id}/attach-file`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      toastr.success('File attached successfully.');
      form.attachments.push(response.data.attachment); // Ensure reactivity
    } catch (error) {
      toastr.error('Failed to attach file.');
      console.error('Error during file upload:', error);
    }
  }
};

const removeAttachment = async (attachmentId) => {
  try {
    await deleteFile(attachmentId);
    form.attachments = form.attachments.filter(att => att.id !== attachmentId); // Ensure reactivity
    toastr.success('Attachment removed successfully.');
  } catch (error) {
    toastr.error('Failed to remove attachment.');
    console.error('Error during attachment removal:', error);
  }
};

const initializeDropzone = () => {
  const dropzoneElement = document.getElementById('demo-upload');
  if (dropzoneElement && !Dropzone.instances.length) { // Check if Dropzone is already initialized
    const dropzone = new Dropzone(dropzoneElement, {
      url: '/upload',
      autoProcessQueue: false,
      addRemoveLinks: true,
      clickable: true, // Ensure the dropzone is clickable
      init: function () {
        this.on('addedfile', async function (file) {
          try {
            await handleFileUpload(file);
            this.removeFile(file); // Remove the file from Dropzone after upload
          } catch (error) {
            console.error('Error during file upload:', error);
          }
        });
        this.on('removedfile', function (file) {
          try {
            const attachment = form.attachments.find(att => att.file_name === file.name);
            if (attachment) {
              removeAttachment(attachment.id);
            }
          } catch (error) {
            console.error('Error during file removal:', error);
          }
        });
      },
      error: function (file, errorMessage) {
        console.error('Dropzone error:', errorMessage);
        toastr.error('Error during file upload.');
      },
    });

    // Load existing attachments into Dropzone
    form.attachments.forEach(attachment => {
      const mockFile = { name: attachment.file_name, size: attachment.file_size, dataURL: attachment.file_url };
      dropzone.emit('addedfile', mockFile);
      dropzone.emit('thumbnail', mockFile, attachment.file_url);
      dropzone.emit('complete', mockFile);
    });
  }
};

const initializeDataTable = (selector, options) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable(options);
};

onMounted(() => {
  try {
    console.log('purchaseInvoices:', props.purchaseInvoices); // Debugging
    const invoices = (props.purchaseInvoices || []).map(invoice => ({
      id: invoice.id,
      pi_number: invoice.pi_number || '',
      invoice_date: invoice.invoice_date || '',
      supplier_name: invoice.supplier ? invoice.supplier.name : '',
      // total_amount: invoice.total_amount || 0,
      paid_amount: invoice.paid_amount || 0,
      transaction_type: invoice.transaction_type || 0,
      payment_type: invoice.payment_type || 0,
      paid_usd: invoice.paid_usd || 0,
      currency: invoice.currency || 0,
      currency_rate: invoice.currency_rate || 0,
    }));

    invoiceListTableInstance.value = initializeDataTable('#invoice-list-table', {
      responsive: true,
      autoWidth: true,
      data: invoices, // Use the fallback-safe invoices array
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'pi_number' },
        { data: 'invoice_date', render: (data) => format(data, 'date') },
        { data: 'supplier_name' },
        // { data: 'total_amount', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'paid_amount', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'currency', render: (data) => (data === 1 ? 'USD' : data === 2 ? 'KHR' : 'Unknown') }, // Corrected currency rendering
        { data: 'currency_rate', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'paid_usd', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
        { data: 'transaction_type', render: (data) => getTransactionType(data) },
        { data: 'payment_type', render: (data) => getPaymentType(data) },
        { data: null, render: (data) => `
            <div class="btn-group">
              <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                <li><a class="dropdown-item btn-view-pdf"><i class="fas fa-file-pdf"></i> View PDF</a></li>
                <li><a class="dropdown-item btn-show"><i class="fas fa-eye"></i> Show</a></li>
              </ul>
            </div>
          `, className: 'text-center'
        },
      ],
    });

    $('#invoice-list-table')
      .on('click', '.btn-edit', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.id) {
          editInvoice(rowData.id);
          $('#nav-create-tab').tab('show');
        }
      })
      .on('click', '.btn-delete', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData) deleteInvoice(rowData.id);
      })
      .on('click', '.btn-view-pdf', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.pdf_url) {
          openPdfViewer(rowData.pdf_url);
        }
      })
      .on('click', '.btn-show', function () {
        const rowData = invoiceListTableInstance.value.row($(this).closest('tr')).data();
        if (rowData && rowData.id) {
          window.location.href = `/invoices/${rowData.id}`;
        }
      });

    $('#invoice-list-table').on('click', '.dtr-details .btn-edit', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.id) {
        editInvoice(rowData.id);
        $('#nav-create-tab').tab('show');
      }
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-delete', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData) deleteInvoice(rowData.id);
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-view-pdf', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.pdf_url) {
        openPdfViewer(rowData.pdf_url);
      }
    });

    $('#invoice-list-table').on('click', '.dtr-details .btn-show', function () {
      const tr = $(this).closest('tr').prev();
      const rowData = invoiceListTableInstance.value.row(tr).data();
      if (rowData && rowData.id) {
        window.location.href = `/invoices/${rowData.id}`;
      }
    });

    $('#invoice_date').datepicker({
      todayHighlight: true,
      autoclose: true,
      format: 'yyyy-mm-dd' // Ensure the date format is correct
    }).on('changeDate', function (e) {
      form.invoice_date = e.format('yyyy-mm-dd');
    });

    initializeSupplierSelect();
    watch(() => form.supplier, (newSupplierId) => {
      if (newSupplierId) {
      }
    });

    $('#nav-create-tab').on('click', () => {
      clearForm();
    });

    prItemsTableInstance.value = initializeDataTable('#pr-items-table', {
      responsive: true,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: [],
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'product.sku' },
        { data: 'purchase_request.pr_number' },
        { data: null, render: (data) => `<div class="wrap-cell">${data.product?.product_description || ''} | ${data.remark || ''}</div>`},
        { data: 'qty_pending' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: null, render: (data) => (data.qty * data.unit_price).toFixed(2) },
        { data: null, render: (data, type, row, meta) => `
            <button type="button" class="btn btn-sm btn-primary" @click="selectPrItem(data)">
              <i class="fa fa-plus"></i> Select
            </button>
          `, className: 'text-center'
        },
      ],
    });

    $('#pr-items-table').on('click', '.btn-primary', function () {
      const rowData = prItemsTableInstance.value.row($(this).closest('tr')).data();
      selectPrItem(rowData);
    });

    $('#pr-items-search').on('keyup', function () {
      prItemsTableInstance.value.search(this.value).draw();
    });

    invoiceItemsTableInstance.value = initializeDataTable('#invoice-items-table', {
      responsive: true,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: prepareInvoiceItems(form.items),
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'pr_item', render: (data) => getPrNumberById(data) },
        { data: 'po_item', render: (data) => getPoNumberById(data) },
        { data: 'item_code' },
        { data: null, render: (data) => `<div>${data.description}</div>` },
        { data: 'remark', render: (data) => `<div>${data}</div>` },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: null, render: (data) => (data.qty * data.unit_price).toFixed(4) },
        { data: 'discount', render: (data) => formatNumber(data, 4) }, // Ensure discount is displayed with 4 decimal places
        { data: 'service_charge', render: (data) => formatNumber(data, 4) }, // Ensure service charge is displayed with 4 decimal places
        { data: 'vat' },
        { data: 'return' },
        { data: 'retention' },
        { data: 'paid_amount', render: (data) => formatNumber(data, 4) }, // Ensure grand total is displayed with 4 decimal places
        { data: 'campus' },
        { data: 'division' },
        { data: 'department' },
        { data: 'location' },
        { data: null, render: (data) => `<div>${data.purpose}</div>` },
        { data: 'deposit' }, // Ensure this line is included
        { data: 'stop_purchase', render: (data) => data === 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No' }, // Ensure this line is included
        { data: null, render: (data, type, row, meta) => `
            <div class="dropdown">
              <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton${meta.row}" data-bs-toggle="dropdown" aria-expanded="false">
                ...
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton${meta.row}">
                <li><a class="dropdown-item text-primary" href="#" @click="editItem(${meta.row})"><i class="fa fa-edit"></i> Edit</a></li>
                <li><a class="dropdown-item text-danger" href="#" @click="removeItem(${meta.row})"><i class="fa fa-trash"></i> Delete</a></li>
                <li><a class="dropdown-item" href="#" @click="duplicateItem(${meta.row})"><i class="fa fa-copy"></i> Duplicate</a></li>
              </ul>
            </div>
          `, className: 'text-center'
        },
      ],
    });

    $('#invoice-items-table').on('click', '.dropdown-item', function (e) {
      e.preventDefault();
      const action = $(this).text().trim();
      const rowIndex = invoiceItemsTableInstance.value.row($(this).closest('tr')).index();
      if (action.includes('Edit')) {
        editItem(rowIndex);
      } else if (action.includes('Delete')) {
        removeItem(rowIndex);
      } else if (action.includes('Duplicate')) {
        duplicateItem(rowIndex);
      }
    });

    $('#invoice-items-table tbody').on('click', 'tr', function (e) {
      const rowIndex = invoiceItemsTableInstance.value.row(this).index();
      const isActionColumn = $(e.target).closest('.dropdown').length > 0;
      if (!isActionColumn) {
        editItem(rowIndex);
      }
    });

    poItemsTableInstance.value = initializeDataTable('#po-items-table', {
      responsive: true,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: [],
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'product.sku' },
        { data: 'purchase_order.po_number' },
        { data: null, render: (data) => `<div class="wrap-cell">${data.product?.product_description || ''} | ${data.description || ''}</div>`},
        { data: 'pending' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: null, render: (data) => (data.qty * data.unit_price).toFixed(2) },
        { data: null, render: (data, type, row, meta) => `
            <button type="button" class="btn btn-sm btn-primary" @click="selectPoItem(data)">
              <i class="fa fa-plus"></i> Select
            </button>
          `, className: 'text-center'
        },
      ],
    });

    $('#po-items-table').on('click', '.btn-primary', function () {
      const rowData = poItemsTableInstance.value.row($(this).closest('tr')).data();
      selectPoItem(rowData);
    });

    $('#po-items-search').on('keyup', function () {
      poItemsTableInstance.value.search(this.value).draw();
    });

    initializeDropzone();
  } catch (error) {
    console.error('Error during onMounted:', error);
  }
});

watch(isEditMode, (newValue) => {
  if (newValue) {
    initializeDropzone();
  }
});

const DeleteInvoice = (invoiceId) => {
  deleteInvoice(invoiceId);
};

const format = (value, type) => {
  if (type === 'date') {
    const date = new Date(value);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
  }
  return value;
};

const formattedInvoiceDate = computed({
  get() {
    return form.invoice_date;
  },
  set(value) {
    form.invoice_date = value;
  }
});

const pdfUrl = ref('');

const totalVat = computed(() => {
  const vatRate = form.vat_rate / 100;
  if (form.payment_type === 2) { // Deposit
    const totalDeposit = form.items.reduce((sum, item) => sum + (parseFloat(item.deposit) || 0), 0);
    return (totalDeposit * vatRate).toFixed(2);
  } else {
    return ((form.total_amount - form.total_discount) * vatRate).toFixed(2);
  }
});

const formattedTotalAmount = computed(() => formatCurrency(form.total_amount, form.currency));
const formattedTotalDiscount = computed(() => formatCurrency(form.total_discount, form.currency));
const formattedTotalServiceCharge = computed(() => formatCurrency(totalServiceCharge.value, form.currency));
const formattedTotalVat = computed(() => formatCurrency(totalVat.value, form.currency));
const formattedGrandTotal = computed(() => formatCurrency(grandTotal.value, form.currency));
</script>

<template>
  <Main>
    <Head :title="'Invoices'" />
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a href="#nav-list" id="nav-list-tab" data-bs-toggle="tab" class="nav-link active">Invoice List</a>
      </li>
      <li class="nav-item">
        <a href="#nav-create" id="nav-create-tab" data-bs-toggle="tab" class="nav-link">Form</a>
      </li>
    </ul>
    <div class="tab-content panel p-3">
      <div class="tab-pane fade active show" id="nav-list">
        <div class="panel-body">
          <table id="invoice-list-table" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Invoice Ref</th>
                <th>Invoice Date</th>
                <th>Supplier</th>
                <!-- <th>Total Amount</th> -->
                <th>Grand Total</th>
                <th>Currency</th>
                <th>Exchange Rate</th>
                <th>Paid USD</th>
                <th>Transaction Type</th>
                <th>Payment Type</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div class="tab-pane fade" id="nav-create">
        <div class="panel-body">
          <form @submit.prevent="submitForm">
            <div class="panel panel-inverse mb-4 border">
              <div class="panel-heading">
                <h4 class="panel-title">Invoice Detail</h4>
                <div class="panel-heading-btn">
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
                </div>
              </div>
              <div class="panel-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="row mb-1 align-items-center">
                      <label for="transaction_type" class="col-sm-4 col-form-label">Transaction Type</label>
                      <div class="col-sm-8">
                        <select v-model="form.transaction_type" class="form-select" id="transaction_type">
                          <option value="1">Petty Cash</option>
                          <option value="2">Credit</option>
                          <option value="3">Advance</option>
                        </select>
                        <div v-if="formErrors.transaction_type" class="text-danger">{{ formErrors.transaction_type }}</div>
                      </div>
                    </div>
                    <div v-if="form.transaction_type !== 2" class="row mb-1 align-items-center">
                      <label for="cash_ref" class="col-sm-4 col-form-label">Cash Reference</label>
                      <div class="col-sm-8">
                        <select v-model="form.cash_ref" class="form-select" id="cash_ref">
                          <option value="" disabled>Select Cash Reference</option>
                          <option v-for="cashRequest in filteredCashRequests" :key="cashRequest.id" :value="cashRequest.id">
                            {{ cashRequest.ref_no }}
                          </option>
                        </select>
                        <div v-if="formErrors.cash_ref" class="text-danger">{{ formErrors.cash_ref }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="invoice_date" class="col-sm-4 col-form-label">Invoice Date</label>
                      <div class="col-sm-8">
                        <input type="date" v-model="form.invoice_date" class="form-control" id="invoice_date" />
                        <div v-if="formErrors.invoice_date" class="text-danger">{{ formErrors.invoice_date }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="invoice_no" class="col-sm-4 col-form-label">Invoice No</label>
                      <div class="col-sm-8">
                        <input type="text" v-model="form.invoice_no" class="form-control" id="invoice_no" />
                        <div v-if="formErrors.invoice_no" class="text-danger">{{ formErrors.invoice_no }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="supplier" class="col-sm-4 col-form-label">Supplier</label>
                      <div class="col-sm-8 d-flex">
                        <select v-model="form.supplier" class="form-select me-2" id="supplier" style="width: 100%;">
                          <option value="" disabled>Select Supplier</option>
                          <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                            {{ supplier.name }}
                          </option>
                        </select>
                        <button type="button" class="btn btn-primary" @click="openCreateSupplierModal">New</button>
                        <div v-if="formErrors.supplier" class="text-danger">{{ formErrors.supplier }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="vat_rate" class="col-sm-4 col-form-label">VAT(%)</label>
                      <div class="col-sm-8">
                        <input type="number" v-model="form.vat_rate" class="form-control" id="vat_rate"/>
                        <div v-if="formErrors.vat_rate" class="text-danger">{{ formErrors.vat_rate }}</div>
                      </div>
                  </div>
                  </div>
                  <div class="col-md-6">
                    <div class="row mb-1 align-items-center">
                      <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                      <div class="col-sm-8">
                        <select v-model="form.currency" class="form-select" id="currency">
                          <option value="1">USD</option>
                          <option value="2">KHR</option>
                        </select>
                        <div v-if="formErrors.currency" class="text-danger">{{ formErrors.currency }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="currency_rate" class="col-sm-4 col-form-label">Currency Rate</label>
                      <div class="col-sm-8">
                        <input type="number" v-model="form.currency_rate" class="form-control" id="currency_rate" />
                        <div v-if="formErrors.currency_rate" class="text-danger">{{ formErrors.currency_rate }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                      <div class="col-sm-8">
                        <select v-model="form.payment_term" class="form-select" id="payment_term">
                          <option value="1">Credit 1 week</option>
                          <option value="2">Credit 2 weeks</option>
                          <option value="3">Credit 1 month</option>
                          <option value="4">Non-Credit</option>
                        </select>
                        <div v-if="formErrors.payment_term" class="text-danger">{{ formErrors.payment_term }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="payment_type" class="col-sm-4 col-form-label">Payment Type</label>
                      <div class="col-sm-8">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" v-model="form.payment_type" id="payment_type_final" :value="1">
                          <label class="form-check-label" for="payment_type_final">Final</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" v-model="form.payment_type" id="payment_type_deposit" :value="2">
                          <label class="form-check-label" for="payment_type_deposit">Deposit</label>
                        </div>
                        <div v-if="formErrors.payment_type" class="text-danger">{{ formErrors.payment_type }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <button type="button" class="btn btn-primary btn-select-item" @click="openPrItemsModal" :disabled="!form.supplier">Select PR Item</button>
              <button type="button" class="btn btn-secondary btn-select-item" @click="openPoItemsModal" :disabled="!form.supplier">Select PO Item</button>
            </div>
            <div class="panel panel-inverse border">
              <div class="panel-heading">
                <h4 class="panel-title">Invoice Items</h4>
                <div class="panel-heading-btn">
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
                </div>
              </div>
              <div class="panel-body">
                <div class="table-responsive mt-3">
                  <table id="invoice-items-table" class="table table-bordered align-middle m-3" width="100%">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>PR Number</th>
                        <th>PO Number</th>
                        <th>Item Code</th>
                        <th style="min-width: 250px;">Description</th>
                        <th>Remark</th>
                        <th>Qty</th>
                        <th>UOM</th>
                        <th>Price</th>
                        <th>Total Price</th>
                        <th>Discount</th>
                        <th>Delivery</th>
                        <th>VAT(%)</th>
                        <th>Return</th>
                        <th>Retention</th>
                        <th>Grand Total</th>
                        <th>Campus</th>
                        <th>Division</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th style="width: 20%;">Purpose</th>
                        <th>Deposit</th>
                        <th>Stop Purchase</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                </div>
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="row mb-1 align-items-center">
                      <label for="service_charge" class="col-sm-4 col-form-label">Delivery</label>
                      <div class="col-sm-4">
                        <input type="number" v-model="form.service_charge" class="form-control" id="service_charge">
                        <div v-if="formErrors.service_charge" class="text-danger">{{ formErrors.service_charge }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="discount_total" class="col-sm-4 col-form-label">Discount Overall</label>
                      <div class="col-sm-4">
                        <input type="number" v-model="form.discount_total" class="form-control" id="discount_total">
                        <div v-if="formErrors.discount_total" class="text-danger">{{ formErrors.discount_total }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="row mb-1 align-items-center">
                      <label for="total_amount" class="col-sm-4 col-form-label">Sub Total</label>
                      <div class="col-sm-4">
                        <input type="text" :value="formattedTotalAmount" class="form-control" id="total_amount" disabled>
                        <div v-if="formErrors.total_amount" class="text-danger">{{ formErrors.total_amount }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="total_discount" class="col-sm-4 col-form-label">Discount</label>
                      <div class="col-sm-4">
                        <input type="text" :value="formattedTotalDiscount" class="form-control" id="total_discount" disabled>
                        <div v-if="formErrors.total_discount" class="text-danger">{{ formErrors.total_discount }}</div>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="total_vat" class="col-sm-4 col-form-label">Total VAT</label>
                      <div class="col-sm-4">
                        <input type="text" :value="formattedTotalVat" class="form-control" id="total_vat" disabled>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="total_service_charge" class="col-sm-4 col-form-label">Delivery</label>
                      <div class="col-sm-4">
                        <input type="text" :value="formattedTotalServiceCharge" class="form-control" id="total_service_charge" disabled>
                      </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                      <label for="paid_amount" class="col-sm-4 col-form-label">Grand Total</label>
                      <div class="col-sm-4">
                        <input type="text" :value="formattedGrandTotal" class="form-control" id="paid_amount" disabled>
                        <div v-if="formErrors.paid_amount" class="text-danger">{{ formErrors.paid_amount }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-show="isEditMode" class="panel panel-inverse border rounded-0 mt-4">
              <div class="panel-heading">
                <h4 class="panel-title">Attachments</h4>
                <div class="panel-heading-btn">
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
                  <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
                </div>
              </div>
              <div class="panel-body">
                <div id="dropzone">
                  <form action="/upload" class="dropzone needsclick" id="demo-upload">
                    <div class="dz-message needsclick">
                      Drop files <b>here</b> or <b>click</b> to upload.<br />
                      <span class="dz-note needsclick">
                        (For <strong class="text-danger">IMPORTANT</strong> documents only.)
                      </span>
                    </div>
                  </form>
                </div>
                <div class="row mb-1 align-items-center" v-if="form.attachments && form.attachments.length">
                  <div class="col-sm-8">
                    <div class="d-flex flex-wrap">
                      <div v-for="(attachment, index) in form.attachments" :key="attachment.id" class="attachment-thumbnail position-relative me-3 mb-3">
                        <img :src="getFileThumbnail(attachment.file_url)" @click="openPdfViewer(attachment.file_url)" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;" />
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 start-0 translate-middle p-1" @click="removeAttachment(attachment.id)" style="border-radius: 50%;">
                          <i class="fa fa-times"></i>
                        </button>
                        <div class="position-absolute bottom-0 start-50 translate-middle-x bg-light px-2 py-1 rounded">{{ index + 1 }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="clearForm">Clear</button>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="modal fade" id="prItemsModal" tabindex="-1" aria-labelledby="prItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="prItemsModalLabel">PR Items</h5>
            <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table id="pr-items-table" class="table table-bordered align-middle" width="100%">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>PR Number</th>
                    <th>Description</th>
                    <th>Qty Pending</th>
                    <th>UOM</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                    <th>Action</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="poItemsModal" tabindex="-1" aria-labelledby="poItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="poItemsModalLabel">PO Items</h5>
            <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table id="po-items-table" class="table table-bordered align-middle" width="100%">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Item Code</th>
                  <th>PO Number</th>
                  <th>Description</th>
                  <th>Qty Pending</th>
                  <th>UOM</th>
                  <th>Unit Price</th>
                  <th>Total Price</th>
                  <th>Action</th>
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
    <div class="modal fade" id="editInvoiceItemModal" tabindex="-1" aria-labelledby="editInvoiceItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editInvoiceItemModalLabel">{{ editItemForm.po_item ? 'Edit PO Item' : 'Edit PR Item' }}</h5>
            <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateInvoiceItem">
              <div class="row">
                <div class="col-md-6">
                  <div class="row mb-2 align-items-center">
                    <label for="editDescription" class="col-sm-4 col-form-label">Description</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.description" class="form-control" id="editDescription"></textarea>
                      <div v-if="editItemFormErrors.description" class="text-danger">{{ editItemFormErrors.description }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editQty" class="col-sm-4 col-form-label">Quantity</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.qty" class="form-control bg-light" id="editQty" step="0.0001" :disabled="form.payment_type === 2">
                      <div v-if="editItemFormErrors.qty" class="text-danger">{{ editItemFormErrors.qty }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editUom" class="col-sm-4 col-form-label">UoM</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.uom" class="form-control bg-light" id="editUom" disabled>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editUnitPrice" class="col-sm-4 col-form-label">Unit Price</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.unit_price" class="form-control" id="editUnitPrice" step="0.0001">
                      <div v-if="editItemFormErrors.unit_price" class="text-danger">{{ editItemFormErrors.unit_price }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editTotalPrice" class="col-sm-4 col-form-label">Total Price</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.total_price" class="form-control bg-light" id="editTotalPrice" disabled>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editDiscount" class="col-sm-4 col-form-label">Discount</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.discount" class="form-control" id="editDiscount" step="0.0001">
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editServiceCharge" class="col-sm-4 col-form-label">Delivery</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.service_charge" class="form-control" id="editServiceCharge" step="0.0001">
                      <div v-if="editItemFormErrors.service_charge" class="text-danger">{{ editItemFormErrors.service_charge }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editVat" class="col-sm-4 col-form-label">VAT(%)</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.vat" class="form-control bg-light" id="editVat" step="0.01" disabled>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editReturn" class="col-sm-4 col-form-label">Return</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.return" class="form-control" id="editReturn" step="0.0001">
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editRetention" class="col-sm-4 col-form-label">Retention</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.retention" class="form-control" id="editRetention" step="0.0001">
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editDeposit" class="col-sm-4 col-form-label">Deposit</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.deposit" class="form-control" id="editDeposit" step="0.0001">
                      <div v-if="editItemFormErrors.deposit" class="text-danger">{{ editItemFormErrors.deposit }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editPaidAmount" class="col-sm-4 col-form-label">Grand Total</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.paid_amount" class="form-control bg-light" id="editPaidAmount" step="0.0001" disabled>
                      <div v-if="editItemFormErrors.paid_amount" class="text-danger">{{ editItemFormErrors.paid_amount }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row mb-2 align-items-center">
                    <label for="editPrNumber" class="col-sm-4 col-form-label">PR Number</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.pr_number" class="form-control bg-light" id="editPrNumber" disabled>
                    </div>
                  </div>
                  <div v-if="editItemForm.po_item" class="row mb-2 align-items-center">
                    <label for="editPoNumber" class="col-sm-4 col-form-label">PO Number</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.po_number" class="form-control bg-light" id="editPoNumber" disabled>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editCampus" class="col-sm-4 col-form-label">Campus</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.campus" class="form-control" id="editCampus">
                      <div v-if="editItemFormErrors.campus" class="text-danger">{{ editItemFormErrors.campus }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editDivision" class="col-sm-4 col-form-label">Division</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.division" class="form-control" id="editDivision">
                      <div v-if="editItemFormErrors.division" class="text-danger">{{ editItemFormErrors.division }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editDepartment" class="col-sm-4 col-form-label">Department</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.department" class="form-control" id="editDepartment">
                      <div v-if="editItemFormErrors.department" class="text-danger">{{ editItemFormErrors.department }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editLocation" class="col-sm-4 col-form-label">Location</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.location" class="form-control" id="editLocation">
                      <div v-if="editItemFormErrors.location" class="text-danger">{{ editItemFormErrors.location }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editPurpose" class="col-sm-4 col-form-label">Purpose</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.purpose" class="form-control" id="editPurpose"></textarea>
                      <div v-if="editItemFormErrors.purpose" class="text-danger">{{ editItemFormErrors.purpose }}</div>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editRemark" class="col-sm-4 col-form-label">Remark</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.remark" class="form-control" id="editRemark"></textarea>
                    </div>
                  </div>
                  <div class="row mb-2 align-items-center">
                    <label for="editStopPurchase" class="col-sm-4 col-form-label">Force Close</label>
                    <div class="col-sm-8">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" v-model="editItemForm.stop_purchase" id="editStopPurchase" :true-value="1" :false-value="0">
                        <label class="form-check-label" for="editStopPurchase">{{ editItemForm.stop_purchase === 1 ? 'Yes' : 'No' }}</label>
                      </div>
                    </div>
                  </div>
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
    <SupplierFormModal @supplierUpdated="addSupplier" />
  </Main>
</template>

<style scoped>
.description-column {
  min-width: 200px;
  white-space: normal;
  word-break: break-word;
}
.wrap-cell {
  white-space: normal;
  word-break: break-word;
}
</style>