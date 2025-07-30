<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import SupplierFormModal from '@/Components/SupplierFormModal.vue';
import { formatCurrency, formatDate, getTransactionType, getPaymentType, getFileThumbnail, openPdfViewer } from './helpers.js';

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
  vatRate: Number,
  invoice: Object, // For edit mode
});

const form = reactive({
  id: props.invoice?.id || null,
  transaction_type: props.invoice?.transaction_type || null,
  cash_ref: props.invoice?.cash_ref || null,
  payment_type: props.invoice?.payment_type || 1,
  invoice_date: props.invoice?.invoice_date || '',
  invoice_no: props.invoice?.invoice_no || '',
  supplier: props.invoice?.supplier?.id || '',
  currency: props.invoice?.currency || 1,
  currency_rate: props.invoice?.currency_rate || null,
  payment_term: props.invoice?.payment_term || null,
  total_amount: props.invoice?.total_amount || null,
  paid_amount: props.invoice?.paid_amount || null,
  vat_rate: props.vatRate || props.invoice?.vat_rate || 0,
  created_by: props.currentUser?.id || null,
  items: props.invoice?.items?.map(item => ({
    ...item,
    total_price: calculateTotalPrice(item),
    paid_amount: calculateGrandTotal(item),
  })) || [],
  total_discount: props.invoice?.total_discount || 0,
  service_charge: props.invoice?.service_charge || 0,
  discount_total: props.invoice?.discount_total || 0,
  attachments: props.invoice?.attachments || [],
  purchased_by: props.invoice?.purchased_by || props.currentUser?.id || null,
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
      item.discount = item.discount || 0;
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

watch(
  () => form.items.map(item => [
    item.qty, item.unit_price, item.discount, item.return, item.retention,
    item.vat, item.service_charge, item.deposit, item.rounding_method, item.rounding_digits
  ]),
  () => {
    form.items.forEach(item => {
      item.paid_amount = calculateGrandTotal(item);
    });
    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
  },
  { deep: true }
);

watch(() => [form.service_charge, form.discount_total], () => {
  form.items.forEach(item => {
    item.paid_amount = calculateGrandTotal(item);
  });
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
});

const prItemsTableInstance = ref(null);
const invoiceItemsTableInstance = ref(null);
const poItemsTableInstance = ref(null);

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
  rounding_method: '',
  rounding_digits: 0,
  total_price: 0,
  uom: '',
  product_price: 0.0,
  service_charge_overwritten: false,
  deposit: 0.0,
  stop_purchase: 0,
});

const modalDiscount = computed(() => {
  if (form.discount_total > 0 && form.items.length > 0) {
    const totalPriceSum = form.items.reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.unit_price) || 0), 0);
    if (totalPriceSum > 0) {
      const itemTotal = parseFloat(editItemForm.qty) * parseFloat(editItemForm.unit_price) || 0;
      return ((itemTotal / totalPriceSum) * form.discount_total).toFixed(4);
    }
    return '0.0000';
  }
  return parseFloat(editItemForm.discount || 0).toFixed(4);
});

const modalServiceCharge = computed(() => {
  if (form.service_charge > 0 && form.items.length > 0) {
    return (form.service_charge / form.items.length).toFixed(4);
  }
  return parseFloat(editItemForm.service_charge || 0).toFixed(4);
});

const calculateGrandTotal = (item) => {
  const {
    qty = 0,
    unit_price = 0,
    discount = 0,
    return: returnAmount = 0,
    retention = 0,
    vat = 0,
    service_charge = 0,
    deposit = 0,
    rounding_method = '',
    rounding_digits = 0
  } = item;

  let grandTotal = 0;
  if (form.payment_type === 2) {
    const vatAmount = (deposit * vat) / 100;
    grandTotal = parseFloat(deposit) + vatAmount - parseFloat(retention) - parseFloat(returnAmount);
  } else {
    const total_price = qty * unit_price;
    const vatAmount = ((total_price - discount) * vat) / 100;
    grandTotal = total_price - discount - returnAmount - retention + vatAmount + parseFloat(service_charge);
  }

  if (rounding_method && rounding_digits >= 0) {
    const factor = Math.pow(10, rounding_digits);
    if (rounding_method === 'round') {
      grandTotal = Math.round(grandTotal * factor) / factor;
    } else if (rounding_method === 'ceil') {
      grandTotal = Math.ceil(grandTotal * factor) / factor;
    } else if (rounding_method === 'floor') {
      grandTotal = Math.floor(grandTotal * factor) / factor;
    }
  }

  return grandTotal.toFixed(4);
};

const calculateTotalPrice = (item) => {
  const { qty = 0, unit_price = 0 } = item;
  if (form.payment_type === 2) {
    return "0.00";
  }
  const total_price = qty * unit_price;
  return total_price.toFixed(2);
};

watch(
  () => [
    editItemForm.qty,
    editItemForm.unit_price,
    editItemForm.discount,
    editItemForm.return,
    editItemForm.retention,
    editItemForm.vat,
    editItemForm.service_charge,
    editItemForm.deposit,
    editItemForm.rounding_method,
    editItemForm.rounding_digits
  ],
  () => {
    const grandTotal = calculateGrandTotal(editItemForm);
    editItemForm.paid_amount = grandTotal;
  },
  { immediate: true }
);

watch(
  () => [editItemForm.qty, editItemForm.unit_price],
  () => {
    const totalPrice = calculateTotalPrice(editItemForm);
    editItemForm.total_price = totalPrice;
  },
  { immediate: true }
);

const grandTotal = computed(() => {
  const total = form.items.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0);
  const grandTotal = total;
  return isNaN(grandTotal) ? '0.0000' : grandTotal.toFixed(4);
});

const totalServiceCharge = computed(() => {
  return form.items.reduce((sum, item) => sum + (parseFloat(item.service_charge) || 0), 0).toFixed(2);
});

const totalVat = computed(() => {
  const vatRate = form.vat_rate / 100;
  if (form.payment_type === 2) {
    const totalDeposit = form.items.reduce((sum, item) => sum + (parseFloat(item.deposit) || 0), 0);
    return (totalDeposit * vatRate).toFixed(2);
  } else {
    return ((form.total_amount - form.total_discount) * vatRate).toFixed(2);
  }
});

const formErrors = reactive({});
const editItemFormErrors = reactive({});

const formatNumber = (value, decimalPlaces = 2) => {
  return isNaN(parseFloat(value)) ? '0.0000' : parseFloat(value).toFixed(decimalPlaces);
};

const prepareInvoiceItems = (items) => {
  const totalPriceSum = items.reduce((sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.unit_price) || 0), 0);
  const rateDiscount = form.discount_total / totalPriceSum;

  return items.map(item => ({
    ...item,
    total_price: calculateTotalPrice(item),
    paid_amount: calculateGrandTotal(item),
    service_charge: item.service_charge_overwritten ? parseFloat(item.service_charge) : (form.service_charge > 0 ? parseFloat(form.service_charge / form.items.length).toFixed(10) : parseFloat(item.service_charge) || 0),
    discount: form.discount_total > 0 ? (parseFloat(item.total_price) * rateDiscount).toFixed(2) : formatNumber(item.discount),
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

const isEditMode = ref(!!props.invoice);

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
    purchased_by: null,
  });
  invoiceItemsTableInstance.value.clear().draw();
  $('#supplier').val(null).trigger('change');
  $('#purchased_by').val(null).trigger('change');
};

const submitForm = async () => {
  form.cash_ref = $('#cash_ref').val();
  form.items = prepareInvoiceItems(form.items);
  calculateTotalAmount();
  calculateServiceChargeForItems();
  calculateItemDiscounts();

  if (!form.purchased_by) {
    form.purchased_by = props.currentUser.id;
  }

  let response = null;
  if (isEditMode.value && form.id) {
    response = await updateInvoice();
  } else {
    response = await createInvoice();
  }

  if (response) {
    clearForm();
    window.location.href = '/invoices';
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
        item.purchased_by = form.purchased_by;

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

        item.discount = formatNumber(item.discount);
      });
    }

    const response = await axios.post('/invoices', form);
    toastr.success('Invoice submitted successfully.');
    return response.data;
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
    form.cash_ref = form.cash_ref ? parseInt(form.cash_ref) : null;
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
        item.purchased_by = form.purchased_by;

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

        item.discount = formatNumber(item.discount);
      });
    }

    const response = await axios.put(`/invoices/${form.id}`, form);
    toastr.success('Invoice updated successfully.');
    return response.data;
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
    const unit_price = form.payment_type === 2 ? 0 : parseFloat(prItem.unit_price) || 0;
    const discount = 0;
    const deposit = 0;
    const service_charge = 0;
    const total_price = calculateTotalPrice({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge });
    const paid_amount = calculateGrandTotal({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge, deposit });

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
      product_price: prItem.product.price,
      discount: discount,
      vat: form.vat_rate,
      return: 0,
      retention: 0,
      service_charge: service_charge,
      paid_amount: paid_amount,
      rounding_method: '',
      rounding_digits: 0,
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
      purchased_by: form.purchased_by,
      currency_rate: form.currency_rate,
      currency: form.currency,
      invoice_date: form.invoice_date,
      payment_type: form.payment_type,
      invoice_no: form.invoice_no,
      total_price: total_price,
      deposit: form.payment_type === 2 ? parseFloat(prItem.deposit) || 0 : 0,
      stop_purchase: 0,
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
    const unit_price = form.payment_type === 2 ? 0 : parseFloat(poItem.unit_price) || 0;
    const discount = parseFloat(poItem.discount) || 0;
    const service_charge = 0;
    const deposit = 0;
    const total_price = calculateTotalPrice({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge });
    const paid_amount = calculateGrandTotal({ qty, unit_price, discount, return: 0, retention: 0, vat: form.vat_rate, service_charge, deposit });

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
      product_price: poItem.product.price,
      unit_price: unit_price,
      discount: discount,
      vat: form.vat_rate,
      return: 0,
      retention: 0,
      service_charge: service_charge,
      paid_amount: paid_amount,
      rounding_method: '',
      rounding_digits: 0,
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
      purchased_by: form.purchased_by,
      currency_rate: form.currency_rate,
      currency: form.currency,
      invoice_date: form.invoice_date,
      payment_type: form.payment_type,
      invoice_no: form.invoice_no,
      total_price: total_price,
      deposit: form.payment_type === 2 ? parseFloat(poItem.deposit) || 0 : 0,
      stop_purchase: 0,
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

  if (form.supplier) {
    $('#supplier').val(form.supplier).trigger('change');
  }
};

watch(() => form.supplier, (newSupplierId) => {
  if (newSupplierId) {
    document.querySelectorAll('.btn-select-item').forEach(button => button.disabled = false);
  } else {
    document.querySelectorAll('.btn-select-item').forEach(button => button.disabled = true);
  }
});

const addSupplier = (newSupplier) => {
  props.suppliers.push(newSupplier);
  form.supplier = newSupplier.id;
  $('#supplier').val(newSupplier.id).trigger('change');
};

const isCreditTransaction = computed(() => form.transaction_type == 2);

watch(() => form.transaction_type, (newTransactionType) => {
  if (!isEditMode.value) {
    if (newTransactionType === 2) {
      form.cash_ref = null;
    } else if (newTransactionType === 1 || newTransactionType === 3) {
      form.cash_ref = null;
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

  if (newValue === 2) {
    form.items.forEach(item => {
      item.qty = 0;
      item.unit_price = 0;
      item.deposit = item.deposit || 0;
    });
  } else {
    form.items.forEach(item => {
      item.qty = item.qty || 0;
      item.deposit = 0;
    });
  }

  form.items.forEach(item => {
    item.total_price = calculateTotalPrice(item);
    item.paid_amount = calculateGrandTotal(item);
  });

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

const fetchSupplierVat = async (supplierId) => {
  try {
    const response = await axios.get(`/suppliers/${supplierId}/vat`);
    form.vat_rate = response.data.vat || 0;
  } catch (error) {
    console.error('Error fetching supplier VAT:', error);
    toastr.error('Failed to fetch VAT for the selected supplier.');
  }
};

watch(() => form.supplier, async (newSupplierId) => {
  if (newSupplierId && !isEditMode.value) {
    await fetchSupplierVat(newSupplierId);
  }
});

watch(() => form.vat_rate, (newVatRate) => {
  form.items.forEach(item => {
    item.vat = newVatRate;
    item.total_price = calculateTotalPrice(item);
    item.paid_amount = calculateGrandTotal(item);
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
    item.discount = 0;
    item.service_charge = 0;
    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
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

  const modalEl = document.getElementById('editInvoiceItemModal');
  if (modalEl) {
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }

  const editDiscountEl = document.getElementById('editDiscount');
  if (editDiscountEl) {
    if (form.discount_total > 0 && form.discount_total !== '') {
      editDiscountEl.setAttribute('disabled', 'true');
      editDiscountEl.classList.add('bg-light');
    } else {
      editDiscountEl.removeAttribute('disabled');
      editDiscountEl.classList.remove('bg-light');
    }
  }

  const editServiceChargeEl = document.getElementById('editServiceCharge');
  if (editServiceChargeEl) {
    if (isServiceChargeDisabled.value) {
      editServiceChargeEl.setAttribute('disabled', 'true');
      editServiceChargeEl.classList.add('bg-light');
    } else {
      editServiceChargeEl.removeAttribute('disabled');
      editServiceChargeEl.classList.remove('bg-light');
    }
  }

  const editDepositEl = document.getElementById('editDeposit');
  const editQtyEl = document.getElementById('editQty');

  if (form.payment_type === 1) {
    if (editDepositEl) {
      editDepositEl.setAttribute('disabled', 'true');
      editDepositEl.classList.add('bg-light');
    }
    if (editQtyEl) {
      editQtyEl.removeAttribute('disabled');
      editQtyEl.classList.remove('bg-light');
    }
  } else if (form.payment_type === 2) {
    if (editDepositEl) {
      editDepositEl.removeAttribute('disabled');
      editDepositEl.classList.remove('bg-light');
    }
    if (editQtyEl) {
      editQtyEl.setAttribute('disabled', 'true');
      editQtyEl.classList.add('bg-light');
    }
  } else {
    if (editDepositEl) {
      editDepositEl.removeAttribute('disabled');
      editDepositEl.classList.remove('bg-light');
    }
    if (editQtyEl) {
      editQtyEl.removeAttribute('disabled');
      editQtyEl.classList.remove('bg-light');
    }
  }

  editItemForm.deposit = item.deposit || 0;
  editItemForm.qty = item.qty || 0;
};

const updateInvoiceItem = () => {
  try {
    const index = form.items.findIndex(item => item.id === editItemForm.id || form.items.indexOf(item) === editItemForm.id);
    if (index !== -1) {
      Object.assign(form.items[index], editItemForm);
      form.items[index].service_charge_overwritten = form.service_charge === 0 || form.service_charge === '' || editItemForm.service_charge !== 0;
      form.items[index].total_price = calculateTotalPrice(form.items[index]);
      form.items[index].paid_amount = calculateGrandTotal(form.items[index]);
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
  calculateItemDiscounts();
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
      filteredCashRequests.value = [];
      return;
    }

    const response = await axios.get('/filter-cash-requests', {
      params: { transaction_type: newTransactionType },
    });

    filteredCashRequests.value = response.data;
  } catch (error) {
    console.error('Error fetching filtered cash requests:', error);
    toastr.error('Failed to fetch filtered cash requests.');
  }
});

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
      form.attachments.push(response.data.attachment);
    } catch (error) {
      toastr.error('Failed to attach file.');
      console.error('Error during file upload:', error);
    }
  }
};

const removeAttachment = async (attachmentId) => {
  try {
    await axios.delete(`/invoices/attachments/${attachmentId}`);
    form.attachments = form.attachments.filter(att => att.id !== attachmentId);
    toastr.success('Attachment removed successfully.');
  } catch (error) {
    toastr.error('Failed to remove attachment.');
    console.error('Error during attachment removal:', error);
  }
};

const initializeDropzone = () => {
  const dropzoneElement = document.getElementById('demo-upload');
  if (Dropzone.instances.length > 0) {
    Dropzone.instances.forEach(instance => instance.destroy());
  }

  if (dropzoneElement) {
    const dropzone = new Dropzone(dropzoneElement, {
      url: '/upload',
      autoProcessQueue: false,
      addRemoveLinks: true,
      clickable: true,
      init: function () {
        this.on('addedfile', async function (file) {
          try {
            await handleFileUpload(file);
            this.removeFile(file);
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
  initializeSupplierSelect();
  $('#invoice_date').datepicker({
    todayHighlight: true,
    autoclose: true,
    format: 'yyyy-mm-dd'
  }).on('changeDate', function (e) {
    form.invoice_date = e.format('yyyy-mm-dd');
  });

  $('#purchased_by').select2({
    placeholder: 'Select User',
    allowClear: true,
    width: 'resolve',
    ajax: {
      url: '/search-purchaser',
      dataType: 'json',
      delay: 250,
      data: params => ({
        q: params.term,
      }),
      processResults: data => ({
        results: data.map(user => ({
          id: user.id,
          text: user.name,
        })),
      }),
    },
  }).on('select2:select', e => {
    form.purchased_by = e.params.data.id;
  }).on('select2:unselect', () => {
    form.purchased_by = null;
  });

  if (props.currentUser && !form.purchased_by) {
    const option = new Option(props.currentUser.name, props.currentUser.id, true, true);
    $('#purchased_by').append(option).trigger('change');
    form.purchased_by = props.currentUser.id;
  } else if (form.purchased_by) {
    const selectedOption = new Option(
      props.users.find(user => user.id === form.purchased_by)?.name || 'Unknown',
      form.purchased_by,
      true,
      true
    );
    $('#purchased_by').append(selectedOption).trigger('change');
  }

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
      {
        data: null,
        render: (data, type, row, meta) => `
          <button type="button" class="btn btn-sm btn-primary" @click="selectPrItem(data)">
            <i class="fa fa-plus"></i> Select
          </button>
        `,
        className: 'text-center'
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
      {
        data: null,
        render: (data) => `
          <div>
            <span class="badge bg-primary">${formatNumber(data.product_price, 2)} $</span>
            ${data.description}
          </div>
        `,
      },
      { data: 'remark', render: (data) => `<div>${data}</div>` },
      { data: 'qty' },
      { data: 'uom' },
      { data: 'unit_price' },
      { data: null, render: (data) => (data.qty * data.unit_price).toFixed(4) },
      { data: 'discount', render: (data) => formatNumber(data, 4) },
      { data: 'service_charge', render: (data) => formatNumber(data, 4) },
      { data: 'vat' },
      { data: 'return' },
      { data: 'retention' },
      { data: 'paid_amount', render: (data) => formatNumber(data, 4) },
      { data: 'campus' },
      { data: 'division' },
      { data: 'department' },
      { data: 'location' },
      { data: null, render: (data) => `<div>${data.purpose}</div>` },
      { data: 'deposit' },
      { data: 'stop_purchase', render: (data) => data === 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No' },
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
      {
        data: null,
        render: (data, type, row, meta) => `
          <button type="button" class="btn btn-sm btn-primary" @click="selectPoItem(data)">
            <i class="fa fa-plus"></i> Select
          </button>
        `,
        className: 'text-center'
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
});

watch(isEditMode, (newValue) => {
  if (newValue) {
    initializeDropzone();
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
    <Head :title="isEditMode ? 'Edit Invoice' : 'Create Invoice'" />
    <div class="panel panel-inverse border">
      <div class="panel-heading">
        <h4 class="panel-title">{{ isEditMode ? 'Edit Invoice' : 'Create Invoice' }}</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
        </div>
      </div>
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
                          {{ cashRequest.label }}
                        </option>
                      </select>
                      <div v-if="formErrors.cash_ref" class="text-danger">{{ formErrors.cash_ref }}</div>
                    </div>
                  </div>
                  <div class="row mb-1 align-items-center">
                    <label for="invoice_date" class="col-sm-4 col-form-label">Invoice Date</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="form.invoice_date" class="form-control" id="invoice_date" placeholder="YYYY-MM-DD" />
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
                      <input type="number" v-model="form.vat_rate" class="form-control" id="vat_rate" step="0.01" />
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
                      <input type="number" v-model="form.currency_rate" class="form-control" id="currency_rate" step="0.0001" />
                      <div v-if="formErrors.currency_rate" class="text-danger">{{ formErrors.currency_rate }}</div>
                    </div>
                  </div>
                  <div class="row mb-1 align-items-center">
                    <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                    <div class="col-sm-8">
                      <select v-model="form.payment_term" class="form-select" id="payment_term" :disabled="form.transaction_type === 1">
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
                  <div class="row mb-1 align-items-center">
                    <label for="purchased_by" class="col-sm-4 col-form-label">Purchased By</label>
                    <div class="col-sm-8">
                      <select id="purchased_by" class="form-select" v-model="form.purchased_by" style="width: 100%;"></select>
                      <div v-if="formErrors.purchased_by" class="text-danger">{{ formErrors.purchased_by }}</div>
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
                  <tbody></tbody>
                </table>
              </div>
              <div class="row mt-3">
                <div class="col-md-6">
                  <div class="row mb-1 align-items-center">
                    <label for="service_charge" class="col-sm-4 col-form-label">Delivery</label>
                    <div class="col-sm-4">
                      <input 
                        type="number" 
                        v-model="form.service_charge" 
                        class="form-control" 
                        id="service_charge"
                        :disabled="form.payment_type === 2" 
                        :value="form.payment_type === 2 ? 0 : form.service_charge" 
                        step="0.01"
                      />
                      <div v-if="formErrors.service_charge" class="text-danger">{{ formErrors.service_charge }}</div>
                    </div>
                  </div>
                  <div class="row mb-1 align-items-center">
                    <label for="discount_total" class="col-sm-4 col-form-label">Discount Overall</label>
                    <div class="col-sm-4">
                      <input 
                        type="number" 
                        v-model="form.discount_total" 
                        class="form-control" 
                        id="discount_total"
                        step="0.01"
                      />
                      <div v-if="formErrors.discount_total" class="text-danger">{{ formErrors.discount_total }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row mb-1">
                    <label class="col-sm-4 col-form-label">Total Amount</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" :value="formattedTotalAmount" disabled />
                    </div>
                  </div>
                  <div class="row mb-1">
                    <label class="col-sm-4 col-form-label">Total Discount</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" :value="formattedTotalDiscount" disabled />
                    </div>
                  </div>
                  <div class="row mb-1">
                    <label class="col-sm-4 col-form-label">Total Delivery</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" :value="formattedTotalServiceCharge" disabled />
                    </div>
                  </div>
                  <div class="row mb-1">
                    <label class="col-sm-4 col-form-label">Total VAT</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" :value="formattedTotalVat" disabled />
                    </div>
                  </div>
                  <div class="row mb-1">
                    <label class="col-sm-4 col-form-label">Grand Total</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" :value="formattedGrandTotal" disabled />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="isEditMode" class="panel panel-inverse border mt-4">
            <div class="panel-heading">
              <h4 class="panel-title">Attachments</h4>
              <div class="panel-heading-btn">
                <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
                <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
              </div>
            </div>
            <div class="panel-body">
              <div id="demo-upload" class="dropzone"></div>
              <div class="mt-3">
                <h5>Uploaded Files:</h5>
                <div v-if="form.attachments.length">
                  <div v-for="attachment in form.attachments" :key="attachment.id" class="d-flex align-items-center mb-2">
                    <img :src="getFileThumbnail(attachment.file_url)" alt="thumbnail" style="width: 50px; height: 50px; margin-right: 10px;" />
                    <a :href="attachment.file_url" target="_blank">{{ attachment.file_name }}</a>
                    <button type="button" class="btn btn-sm btn-danger ms-2" @click="removeAttachment(attachment.id)">Remove</button>
                  </div>
                </div>
                <div v-else>No attachments uploaded.</div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-4">
            <Link :href="'/invoices'" class="btn btn-secondary me-2">Cancel</Link>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- PR Items Modal -->
    <div class="modal fade" id="prItemsModal" tabindex="-1" aria-labelledby="prItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="prItemsModalLabel">Select PR Items</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <input type="text" id="pr-items-search" class="form-control" placeholder="Search PR Items..." />
            </div>
            <div class="table-responsive">
              <table id="pr-items-table" class="table table-bordered align-middle" width="100%">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>PR Number</th>
                    <th>Description</th>
                    <th>Pending Qty</th>
                    <th>UOM</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- PO Items Modal -->
    <div class="modal fade" id="poItemsModal" tabindex="-1" aria-labelledby="poItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="poItemsModalLabel">Select PO Items</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <input type="text" id="po-items-search" class="form-control" placeholder="Search PO Items..." />
            </div>
            <div class="table-responsive">
              <table id="po-items-table" class="table table-bordered align-middle" width="100%">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>PO Number</th>
                    <th>Description</th>
                    <th>Pending Qty</th>
                    <th>UOM</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Invoice Item Modal -->
    <div class="modal fade" id="editInvoiceItemModal" tabindex="-1" aria-labelledby="editInvoiceItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editInvoiceItemModalLabel">Edit Invoice Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <label for="editDescription" class="col-sm-3 col-form-label">Description</label>
              <div class="col-sm-9">
                <textarea v-model="editItemForm.description" class="form-control" id="editDescription" rows="3"></textarea>
                <div v-if="editItemFormErrors.description" class="text-danger">{{ editItemFormErrors.description }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editQty" class="col-sm-3 col-form-label">Quantity</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.qty" class="form-control" id="editQty" step="0.01" />
                <div v-if="editItemFormErrors.qty" class="text-danger">{{ editItemFormErrors.qty }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editUnitPrice" class="col-sm-3 col-form-label">Unit Price</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.unit_price" class="form-control" id="editUnitPrice" step="0.01" />
                <div v-if="editItemFormErrors.unit_price" class="text-danger">{{ editItemFormErrors.unit_price }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editDiscount" class="col-sm-3 col-form-label">Discount</label>
              <div class="col-sm-9">
                <input type="number" v-model="modalDiscount" class="form-control" id="editDiscount" step="0.01" />
                <div v-if="editItemFormErrors.discount" class="text-danger">{{ editItemFormErrors.discount }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editServiceCharge" class="col-sm-3 col-form-label">Delivery</label>
              <div class="col-sm-9">
                <input type="number" v-model="modalServiceCharge" class="form-control" id="editServiceCharge" step="0.01" />
                <div v-if="editItemFormErrors.service_charge" class="text-danger">{{ editItemFormErrors.service_charge }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editVat" class="col-sm-3 col-form-label">VAT (%)</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.vat" class="form-control" id="editVat" step="0.01" />
                <div v-if="editItemFormErrors.vat" class="text-danger">{{ editItemFormErrors.vat }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editReturn" class="col-sm-3 col-form-label">Return</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.return" class="form-control" id="editReturn" step="0.01" />
                <div v-if="editItemFormErrors.return" class="text-danger">{{ editItemFormErrors.return }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editRetention" class="col-sm-3 col-form-label">Retention</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.retention" class="form-control" id="editRetention" step="0.01" />
                <div v-if="editItemFormErrors.retention" class="text-danger">{{ editItemFormErrors.retention }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editDeposit" class="col-sm-3 col-form-label">Deposit</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.deposit" class="form-control" id="editDeposit" step="0.01" />
                <div v-if="editItemFormErrors.deposit" class="text-danger">{{ editItemFormErrors.deposit }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editRoundingMethod" class="col-sm-3 col-form-label">Rounding Method</label>
              <div class="col-sm-9">
                <select v-model="editItemForm.rounding_method" class="form-select" id="editRoundingMethod">
                  <option value="">None</option>
                  <option value="round">Round</option>
                  <option value="ceil">Ceil</option>
                  <option value="floor">Floor</option>
                </select>
                <div v-if="editItemFormErrors.rounding_method" class="text-danger">{{ editItemFormErrors.rounding_method }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editRoundingDigits" class="col-sm-3 col-form-label">Rounding Digits</label>
              <div class="col-sm-9">
                <input type="number" v-model="editItemForm.rounding_digits" class="form-control" id="editRoundingDigits" step="1" min="0" />
                <div v-if="editItemFormErrors.rounding_digits" class="text-danger">{{ editItemFormErrors.rounding_digits }}</div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="editTotalPrice" class="col-sm-3 col-form-label">Total Price</label>
              <div class="col-sm-9">
                <input type="text" v-model="editItemForm.total_price" class="form-control" id="editTotalPrice" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="editPaidAmount" class="col-sm-3 col-form-label">Grand Total</label>
              <div class="col-sm-9">
                <input type="text" v-model="editItemForm.paid_amount" class="form-control" id="editPaidAmount" disabled />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="updateInvoiceItem">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Supplier Form Modal -->
    <SupplierFormModal @supplier-added="addSupplier" />
  </Main>
</template>

<style scoped>
.dropzone {
  border: 2px dashed #d9d9d9;
  padding: 20px;
  text-align: center;
  background: #f9f9f9;
}
.dropzone .dz-message {
  font-size: 16px;
  color: #666;
}
.table-responsive {
  overflow-x: auto;
}
.wrap-cell {
  max-width: 250px;
  white-space: normal;
  word-wrap: break-word;
}
</style>