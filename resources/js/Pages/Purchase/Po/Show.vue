<script setup>
import { defineProps, ref, onMounted, computed } from 'vue';
import Main from '@/Layouts/Main.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

toastr.options = {
  closeButton: true,
  debug: true,
  newestOnTop: false,
  progressBar: true,
  positionClass: "toast-top-right",
  preventDuplicates: true,
  onclick: null,
  showDuration: "300",
  hideDuration: "1000",
  timeOut: "5000",
  extendedTimeOut: "1000",
  showEasing: "swing",
  hideEasing: "linear",
  showMethod: "fadeIn",
  hideMethod: "fadeOut"
};

const props = defineProps({
  purchaseOrder: { type: Object, required: true },
});

const goBack = () => {
  window.history.back();
};


const form = ref({
  id: props.purchaseOrder?.id || '',
  po_number: props.purchaseOrder?.po_number || '',
  date: props.purchaseOrder?.date ? props.purchaseOrder.date.split('T')[0] : '',
  supplier: props.purchaseOrder?.supplier?.name || '',
  supplier_phone: props.purchaseOrder?.supplier?.number || '',
  supplier_address: props.purchaseOrder?.supplier?.address || '',
  payment_term: props.purchaseOrder?.payment_term || '',
  purpose: props.purchaseOrder?.purpose || '',
  status: props.purchaseOrder?.status || '',
  purchaser: props.purchaseOrder?.purchaser?.name || '',
  cancelled_reason: props.purchaseOrder?.cancelled_reason || '',
  po_items: props.purchaseOrder?.po_items?.map(item => ({
    ...item,
    concatenated_description: `${item.pr_item.product.product_description} | ${item.description}`
  })) || [],
});

const validationErrors = ref({});
const cancelledReason = ref('');
const cancelledItemReason = ref('');
const currentItemId = ref(null);
const cancelledQty = ref(0);
const remainingQty = ref(0);
const isLoading = ref(false);

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

const calculateSubTotal = () => {
  return form.value.po_items.reduce((total, item) => total + parseFloat(item.unit_price * item.qty || 0), 0);
};

const calculateTotalDiscount = () => {
  return form.value.po_items.reduce((total, item) => total + parseFloat(item.discount || 0), 0);
};

const calculateTotalVAT = () => {
  return form.value.po_items.reduce((total, item) => total + parseFloat((item.unit_price * item.qty * item.vat / 100) || 0), 0);
};

const calculateGrandTotal = () => {
  return calculateSubTotal() - calculateTotalDiscount() + calculateTotalVAT();
};

const getItemStatusBadgeClass = (status) => {
  switch (status) {
    case 'Pending': return 'badge bg-warning';
    case 'Approved': return 'badge bg-success';
    case 'Rejected': return 'badge bg-danger';
    case 'Cancelled': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const cancelPurchaseOrder = async () => {
  const modalElement = document.getElementById('cancelReasonModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    document.getElementById('cancelled_reason').focus();
  });
};

const confirmCancel = async () => {
  if (cancelledReason.value) {
    isLoading.value = true;
    try {
      await axios.put(`/purchase-orders/${form.value.id}/cancel`, { cancelled_reason: cancelledReason.value });
      form.value.status = 'Cancelled';
      form.value.cancelled_reason = cancelledReason.value;
      toastr.success("You have cancelled successfully!", "Success!");
      const modalElement = document.getElementById('cancelReasonModal');
      const modal = bootstrap.Modal.getInstance(modalElement);
      modal.hide();
      // Store a flag in localStorage to show the alert after refresh
      localStorage.setItem('showCancelAlert', 'true');
      // Refresh the page after confirming the cancellation
      window.location.reload();
    } catch (error) {
      console.error('Error cancelling purchase order:', error);
      toastr.error('Failed to cancel purchase order. Please try again.', 'Error!');
    } finally {
      isLoading.value = false;
    }
  }
};

// const cancelPoItem = async (poItemId) => {
//   currentItemId.value = poItemId;
//   const item = form.value.po_items.find(item => item.id === poItemId);
//   remainingQty.value = item.qty - item.cancelled_qty - item.received_qty;

//   const modalElement = document.getElementById('cancelItemReasonModal');
//   const modal = new bootstrap.Modal(modalElement);
//   modal.show();

//   modalElement.addEventListener('shown.bs.modal', () => {
//     document.getElementById('cancelled_item_reason').focus();
//   });
// };

const confirmCancelItem = async () => {
  if (cancelledItemReason.value && currentItemId.value !== null && cancelledQty.value > 0) {
    const itemIndex = form.value.po_items.findIndex(item => item.id === currentItemId.value);
    const item = form.value.po_items[itemIndex];

    if (cancelledQty.value > remainingQty.value) {
      toastr.error('Cancelled quantity cannot exceed remaining quantity.', 'Error!');
      return;
    }

    isLoading.value = true;
    try {
      const response = await axios.put(`/purchase-orders/items/${currentItemId.value}/cancel`, { 
        cancelled_reason: cancelledItemReason.value,
        cancelled_qty: cancelledQty.value
      });

      const updatedItem = response.data;
      if (item) {
        item.cancelled_qty = updatedItem.cancelled_qty;
        item.pending = updatedItem.pending;
        remainingQty.value = parseFloat(item.pending.toFixed(4)); // Update remaining quantity immediately
        if (item.cancelled_qty === item.qty) {
          item.status = 'Cancelled';
        }
        // Ensure Vue reactivity updates the DOM
        form.value.po_items[itemIndex] = { ...item };
      }

      const allItemsCancelled = form.value.po_items.every(item => item.status === 'Cancelled');
      if (allItemsCancelled) {
        form.value.status = 'Cancelled';
      }

      // Change status immediately when sum of cancelled_qty equals qty
      if (item.cancelled_qty === item.qty) {
        item.status = 'Cancelled';
        form.value.po_items[itemIndex] = { ...item };
      }

      // Store a flag in localStorage to show the alert after refresh
      localStorage.setItem('showCancelItemAlert', 'true');
      const modalElement = document.getElementById('cancelItemReasonModal');
      const modal = bootstrap.Modal.getInstance(modalElement);
      modal.hide();
      // Refresh the page after confirming the cancellation
      window.location.reload();
    } catch (error) {
      console.error('Error cancelling PO item:', error);
      toastr.error('Failed to cancel PO item. Please try again.', 'Error!');
    } finally {
      isLoading.value = false;
    }
  } else {
    validationErrors.value = {
      cancelledItemReason: !cancelledItemReason.value ? 'Reason for cancellation is required.' : '',
      cancelledQty: cancelledQty.value <= 0 ? 'Cancelled quantity must be greater than 0.' : ''
    };
  }
};

const hasCancelledItems = computed(() => {
  return form.value.po_items.some(item => item.cancelled_qty > 0);
});

const invoiceItems = ref([]);
const poItemsTableInstance = ref(null);
const invoiceItemsTableInstance = ref(null);

const initializeDataTable = (selector, options) => {
  const table = $(selector);
  if ($.fn.DataTable.isDataTable(table)) {
    table.DataTable().clear().destroy();
  }
  return table.DataTable(options);
};

const isButtonDisabled = (status) => {
  return status === 'Cancelled' || status === 'Closed';
};

const fetchInvoiceItems = async () => {
  try {
    const response = await axios.get(`/purchase-invoice-itemspo/${props.purchaseOrder.id}`);
    invoiceItems.value = response.data;

    poItemsTableInstance.value = initializeDataTable('#po-items-table', {
      responsive: false,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: form.value.po_items,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'pr_item.purchase_request.pr_number' },
        { data: 'pr_item.product.sku' },
        { data: 'concatenated_description' },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'campus' },
        { data: 'division' },
        { data: 'department' },
        { data: 'location' },
        { data: 'unit_price' },
        { data: 'discount' },
        { data: 'vat' },
        { data: 'total_usd', render: (data) => parseFloat(data).toFixed(4) },
        { data: 'received_qty' },
        { data: 'cancelled_qty' },
        { data: 'pending', render: (data) => parseFloat(data).toFixed(4) },
        { data: 'status', render: (data) => `<span class="${getItemStatusBadgeClass(data)}">${data}</span>` },
        { data: 'force_close', render: (data) => data === 1 ? '<span class="badge bg-danger"><i class="fa fa-check-circle"></i> Yes</span>' : '<span class="badge bg-secondary"><i class="fa fa-times-circle"></i> No</span>' },
        // { data: 'cancelled_reason', visible: hasCancelledItems.value },
        // { data: null, render: (data) => `<button type="button" class="btn btn-sm btn-warning" onclick="cancelPoItem(${data.id})" ${isButtonDisabled(data.status) ? 'disabled' : ''}><i class="fa fa-ban t-plus-1 fa-fw fa-lg"></i></button>` },
      ],
    });

    invoiceItemsTableInstance.value = initializeDataTable('#invoice-items-table', {
      responsive: false,
      autoWidth: false,
      scrollX: false,
      select: true,
      data: invoiceItems.value, // Ensure data is set correctly
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'invoice_date', render: (data) => moment(data).format('MMM DD, YYYY') },
        { 
          data: 'invoice.pi_number', 
          render: (data, type, row) => `<a href="/invoices/${row.invoice.id}" class="text-primary">${data}</a>` 
        },
        { data: 'invoice_no' },
        { data: 'supplier.name' },
        { data: 'product.sku' },
        { data: 'description' },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: 'total_price', render: (data) => parseFloat(data).toFixed(4) },
        { data: 'currency', render: (data) => data === 1 ? 'USD' : 'KHR' },
        { data: 'purchased_by.name' },
        { data: 'stop_purchase', render: (data) => data === 1 ? '<span class="badge bg-danger"><i class="fa fa-check-circle"></i> Yes</span>' : '<span class="badge bg-secondary"><i class="fa fa-times-circle"></i> No</span>' },
      ],
    });
  } catch (error) {
    console.error('Error fetching invoice items:', error);
    toastr.error('Failed to fetch invoice items. Please try again.', 'Error!');
  }
};

// window.cancelPoItem = cancelPoItem;

onMounted(() => {
  // Check if the flag is set in localStorage and show the alert
  if (localStorage.getItem('showCancelAlert') === 'true') {
    toastr.success("You have cancelled successfully!", "Success!");
    localStorage.removeItem('showCancelAlert');
  }
  // Check if the flag is set in localStorage and show the alert
  if (localStorage.getItem('showCancelItemAlert') === 'true') {
    toastr.success("You have cancelled successfully!", "Success!");
    localStorage.removeItem('showCancelItemAlert');
  }
  fetchInvoiceItems();
});
</script>

<template>
  <Main>
    <Head title="Purchase Order Details" />
    <div class="mb-3">
      <button @click="goBack" class="btn btn-primary">
        <i class="fa-solid fa-arrow-left-long"></i> Back
      </button>
      <!-- <button class="btn btn-danger ms-2" @click="cancelPurchaseOrder" :disabled="form.status === 'Cancelled' || form.status === 'Closed'"><i class="fas fa-ban"></i> Cancel PO</button> -->
    </div>
    <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Purchase Order Details</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="invoice">
          <!-- Invoice Company -->
          <div class="invoice-company">
            {{ form.po_number }}
          </div>

          <!-- Invoice Header -->
          <div class="invoice-header row">
            <div class="invoice-from col-md-6">
              <address class="mt-5px mb-5px">
                <strong class="text-dark">Purchaser: {{ form.purchaser }}</strong><br />
                Supplier: {{ form.supplier }}<br />
                Phone: {{ form.supplier_phone }}<br />
                Address: {{ form.supplier_address }}<br />
                Payment Term: {{ form.payment_term }}<br />
                Purpose: {{ form.purpose }}<br/>
                <!-- Cancell Reason: {{ form.cancelled_reason }} -->
              </address>
            </div>

            <div class="invoice-date col-md-6 text-end">
              <div class="invoice-detail text-start" style="float: right;">
                <div class="date text-dark mt-5px">{{ format(form.date, 'date') }}</div>
                Status: <span :class="getStatusBadgeClass(form.status)">{{ form.status }}</span><br />
              </div>
            </div>
          </div>

          <!-- Invoice Content -->
          <div class="invoice-content">
            <!-- PO Items Section -->
            <div class="table-responsive">
              <table id="po-items-table" class="table table-bordered align-middle" width="100%">
                <thead class="text-center">
                  <tr>
                    <th>#</th>
                    <th>PR Number</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>UOM</th>
                    <th>Campus</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>VAT%</th>
                    <th>Total Price</th>
                    <th>Received Qty</th>
                    <th>Cancelled Qty</th>
                    <th>Pending Qty</th>
                    <th>Status</th>
                    <th>Force Close?</th>
                    <!-- <th>Reason</th> -->
                    <!-- <th>Actions</th> -->
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
            <!-- Summary Table Section -->
            <div class="table-responsive mt-0" style="width: 20%; float: right;">
              <table class="table table-sm">
                <tbody>
                  <tr>
                    <td class="text-start"><strong>Sub Total:</strong></td>
                    <td class="text-end"><strong>${{ calculateSubTotal().toFixed(2) }}</strong></td>
                  </tr>
                  <tr>
                    <td class="text-start"><strong>Total Discount:</strong></td>
                    <td class="text-end"><strong>${{ calculateTotalDiscount().toFixed(2) }}</strong></td>
                  </tr>
                  <tr>
                    <td class="text-start"><strong>Total VAT:</strong></td>
                    <td class="text-end"><strong>${{ calculateTotalVAT().toFixed(2) }}</strong></td>
                  </tr>
                  <tr>
                    <td class="text-start"><strong>Grand Total:</strong></td>
                    <td class="text-end"><strong>${{ calculateGrandTotal().toFixed(2) }}</strong></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Panel for Invoice Items -->
    <div class="panel panel-inverse mt-2">
      <div class="panel-heading">
        <h4 class="panel-title">Purchased Items</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="invoice-items-table" class="table table-bordered align-middle m-3" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>PI Number</th>
                <th>Invoice Number</th>
                <th>Supplier</th>
                <th>Product Code</th>
                <th>Product Description</th>
                <th>Qty</th>
                <th>UOM</th>
                <th>Price</th>
                <th>Total Price</th>
                <th>Currency</th>
                <th>Purchaser</th>
                <th>Force Close?</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
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
              <textarea id="cancelled_reason" v-model="cancelledReason" class="form-control" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-danger" @click="confirmCancel">Confirm Cancel</button>
          </div>
        </div>
      </div>
    </div> -->

    <!-- Modal for Cancel Item Reason -->
    <!-- <div class="modal fade" id="cancelItemReasonModal" tabindex="-1" aria-labelledby="cancelItemReasonModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cancelItemReasonModalLabel">Cancel PO Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Field</th>
                  <th>Input</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><label for="cancelled_item_reason" class="form-label">Reason for Cancellation</label></td>
                  <td>
                    <textarea id="cancelled_item_reason" v-model="cancelledItemReason" class="form-control" rows="3"></textarea>
                    <div v-if="validationErrors.cancelledItemReason" class="text-danger">{{ validationErrors.cancelledItemReason }}</div>
                  </td>
                </tr>
                <tr>
                  <td><label for="cancelled_qty" class="form-label">Cancelled Quantity</label></td>
                  <td>
                    <input type="number" id="cancelled_qty" v-model="cancelledQty" step="0.01" class="form-control" />
                    <small class="text-muted">Remaining Quantity: {{ remainingQty.toFixed(4) }}</small>
                    <div v-if="validationErrors.cancelledQty" class="text-danger">{{ validationErrors.cancelledQty }}</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-danger" @click="confirmCancelItem">Confirm Cancel</button>
          </div>
        </div>
      </div>
    </div> -->
  </Main>
</template>

<style>
.invoice-company {
  font-size: 1.25rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.invoice-logo {
  max-width: 150px;
  margin-bottom: 1rem;
}
</style>
