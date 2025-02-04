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
  currentUser: Object,
  suppliers: Array,
  prItems: Array,
  purchaseRequests: Array,
  users: Array,
});

const getUserDetails = (userId) => {
  const user = props.users.find(user => user.id === userId);
  return user ? user : { name: 'Unknown User', card_id: '', position: '', phone: '', extension: '' };
};

const userDetails = getUserDetails(props.purchaseOrder?.user_id);

const form = ref({
  id: props.purchaseOrder?.id || '',
  po_number: props.purchaseOrder?.po_number || '',
  date: props.purchaseOrder?.date ? props.purchaseOrder.date.split('T')[0] : '',
  user_id: userDetails.name || '',
  supplier: props.purchaseOrder?.supplier?.name || '',
  supplier_phone: props.purchaseOrder?.supplier?.number || '',
  supplier_address: props.purchaseOrder?.supplier?.address || '',
  payment_term: props.purchaseOrder?.payment_term || '',
  purpose: props.purchaseOrder?.purpose || '',
  status: props.purchaseOrder?.status || '',
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

const printInvoice = () => {
  const printContents = document.querySelector('.invoice').innerHTML;
  const originalContents = document.body.innerHTML;

  document.body.innerHTML = printContents;
  window.print();
  document.body.innerHTML = originalContents;
  window.location.reload();
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

const cancelPoItem = async (poItemId) => {
  currentItemId.value = poItemId;
  const item = form.value.po_items.find(item => item.id === poItemId);
  remainingQty.value = item.qty - item.cancelled_qty;

  const modalElement = document.getElementById('cancelItemReasonModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    document.getElementById('cancelled_item_reason').focus();
  });
};

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
});
</script>

<template>
  <Main>
    <Head title="Purchase Order Details" />
    <div class="mb-3">
      <Link href="/purchase-orders" class="btn btn-primary"><i class="fa-solid fa-arrow-left-long"></i> Back</Link>
      <button class="btn btn-danger ms-2" @click="cancelPurchaseOrder" :disabled="form.status === 'Cancelled'"><i class="fas fa-ban"></i> Cancel PO</button>
    </div>
    <div class="invoice" ref="invoiceContent">
      <!-- Invoice Company -->
      <div class="invoice-company">
        <span class="float-end hidden-print">
          <a href="javascript:;" class="btn btn-sm btn-white mb-10px" @click="printInvoice">
            <i class="fa fa-print t-plus-1 fa-fw fa-lg"></i> Print
          </a>
        </span>
        <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="Company Logo" class="invoice-logo" />
      </div>

      <!-- Invoice Header -->
      <div class="invoice-header row">
        <div class="invoice-from col-md-6">
          <address class="mt-5px mb-5px">
            <strong class="text-dark">Purchaser: {{ form.user_id }}</strong><br />
            Supplier: {{ form.supplier }}<br />
            Phone: {{ form.supplier_phone }}<br />
            Address: {{ form.supplier_address }}<br />
            Payment Term: {{ form.payment_term }}<br />
          </address>
        </div>

        <div class="invoice-date col-md-6 text-end">
          <div class="invoice-detail text-start" style="float: right;">
            <div class="date text-dark mt-5px">{{ format(form.date, 'date') }}</div><br />
            {{ form.po_number }}<br />
            Status: <span :class="getStatusBadgeClass(form.status)">{{ form.status }}</span><br />
          </div>
        </div>
      </div>

      <!-- Invoice Content -->
      <div class="invoice-content">
        <!-- PO Items Section -->
        <div class="table-responsive">
          <table class="table table-invoice table-sm">
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
                <th class="no-print">Status</th>
                <th v-if="hasCancelledItems" class="no-print">Reason</th>
                <th class="no-print">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in form.po_items" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ item.pr_item.purchase_request.pr_number }}</td>
                <td>{{ item.pr_item.product.sku }}</td>
                <td>{{ item.concatenated_description }}</td>
                <td class="text-center">{{ item.qty }}</td>
                <td class="text-center">{{ item.uom }}</td>
                <td class="text-center">{{ item.campus }}</td>
                <td class="text-center">{{ item.division }}</td>
                <td class="text-center">{{ item.department }}</td>
                <td class="text-center">{{ item.location }}</td>
                <td class="text-center">{{ item.unit_price }}</td>
                <td class="text-center">{{ item.discount }}</td>
                <td class="text-center">{{ item.vat }}</td>
                <td class="text-center">{{ item.total_usd }}</td>
                <td class="text-center no-print"><span :class="getItemStatusBadgeClass(item.status)">{{ item.status }}</span></td>
                <td v-if="hasCancelledItems" class="text-center no-print">{{ item.cancelled_reason }}</td>
                <td class="text-center no-print">
                  <button type="button" class="btn btn-sm btn-warning" @click="cancelPoItem(item.id)" :disabled="item.status === 'Cancelled'">
                    <i class="fa fa-ban t-plus-1 fa-fw fa-lg"></i>
                  </button>
                </td>
              </tr>
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
        <!-- Purpose Section -->
        <div class="mt-3">
          <strong>Purpose:</strong> {{ form.purpose }}
        </div>
        <div v-if="form.status === 'Cancelled'">
          <strong>Reason:</strong> <p class="text-danger">{{ form.cancelled_reason }}</p>
        </div>
      </div>
      <!-- Invoice Footer -->
      <div class="invoice-footer-fixed mt-5">
        <p class="text-center mb-5px fw-bold">
          THANK YOU FOR YOUR BUSINESS
        </p>
        <p class="text-center">
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-globe"></i> mjqeducation.edu.kh</span>
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-phone-volume"></i> T:096-3612146</span>
          <span class="me-10px"><i class="fa fa-fw fa-lg fa-envelope"></i> <a href="mailto:vun.thy@mjqeducation.edu.kh">vun.thy@mjqeducation.edu.kh</a></span>
        </p>
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
              <textarea id="cancelled_reason" v-model="cancelledReason" class="form-control" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-danger" @click="confirmCancel">Confirm Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Cancel Item Reason -->
    <div class="modal fade" id="cancelItemReasonModal" tabindex="-1" aria-labelledby="cancelItemReasonModalLabel" aria-hidden="true">
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
    </div>
  </Main>
</template>

<style scoped>
.invoice-company {
  font-size: 1.25rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.invoice-logo {
  max-width: 150px;
  margin-bottom: 1rem;
}

.invoice-header {
  margin-bottom: 1.5rem;
}

.invoice-header .invoice-from address {
  font-size: 0.875rem;
}

.invoice-header .invoice-date .invoice-detail {
  font-size: 0.875rem;
}

.table-invoice {
  border-collapse: collapse;
  width: 100%;
  font-size: 0.875rem;
}

.table-invoice th, .table-invoice td {
  border: 1px solid #dee2e6;
  padding: 0.75rem;
}

.table-invoice th {
  background-color: #0e5aa7;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered td {
  border: 1px solid #dee2e6;
  padding: 0.5rem;
}

.table-sm td {
  padding: 0.3rem;
}

.table-responsive.mt-3 {
  width: 20%; /* Adjusted from 30% to 20% */
  float: right;
}

@media print {
  .hidden-print {
    display: none !important;
  }
  .print-only {
    display: table-row !important;
  }
  .invoice {
    width: 100%;
    margin: 0;
    padding: 0;
  }
  .invoice-header, .invoice-content {
    page-break-inside: avoid;
  }
  .invoice-header .col-md-6, .invoice-date .col-md-6 {
    width: 50%;
    float: left;
  }
  .invoice-header .text-end {
    text-align: right;
  }
  .invoice-header .text-start {
    text-align: left;
  }
  .table-invoice {
    border-collapse: collapse;
    width: 100%;
    font-size: 10px;
  }
  .table-invoice th, .table-invoice td {
    border: 1px solid #000;
    padding: 8px;
  }
  .table-invoice tfoot td {
    border-top: none;
  }
  .invoice-footer-fixed {
    position: fixed;
    bottom: 0;
    width: 100%;
  }
  .no-print {
    display: none !important;
  }
}
</style>
