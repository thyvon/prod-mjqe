<script setup>
import { ref, reactive, onMounted, nextTick, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define the props using defineProps()
const props = defineProps({
  clearInvoice: {
    type: Object,
    required: true,
  },
  users: Array,
  cashRequests: Array,
  purchaseInvoiceItems: Array,
  groupedByCampus: Array, // Add groupedByCampus to props
  approvals: {
    type: Array,
    default: () => [], // Ensure approvals has a default value
  },
  currentUser: Object,
});

// Local state for clear invoice
const clearInvoice = reactive(props.clearInvoice);
const purchaseInvoiceItems = ref(props.purchaseInvoiceItems);

const getClearTypeHeading = (clearType) => {
  return clearType === 1 ? 'Clear Petty Cash' : 'Clear Advance';
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};

// Computed property to calculate the total actual expense (sum of the "paid_amount" column) with 4 decimal places
const actualExpense = computed(() => {
  const total = purchaseInvoiceItems.value.reduce((sum, item) => sum + (parseFloat(item.paid_amount) || 0), 0);
  return parseFloat(total.toFixed(4));
});

// Computed property to calculate the balance with 4 decimal places
const balance = computed(() => {
  const result = (parseFloat(clearInvoice.cash_request?.amount) || 0) - actualExpense.value;
  return parseFloat(result.toFixed(4));
});

const printForm = () => {
  const printableContent = document.getElementById('printable-area').innerHTML;
  const originalContent = document.body.innerHTML;

  document.body.innerHTML = printableContent;
  window.print();
  document.body.innerHTML = originalContent;

  // Reload the page to restore functionality
  window.location.reload();
};

const goBack = () => {
  window.history.back();
};

const getSignatureUrl = (signature) => {
  return signature ? `/storage/${signature}` : 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png'; // Fallback image
};

const getStatusTypeString = (statusType) => {
  switch (statusType) {
    case 1:
      return 'Check';
    case 2:
      return 'Approve';
    default:
      return 'Unknown';
  }
};

const approveRequest = async (statusType) => {
  const confirmResult = await swal({
    title: 'Confirm',
    text: `Are you sure you want to ${getStatusTypeString(statusType)}?`,
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'No',
        value: null,
        visible: true,
        className: 'btn btn-secondary',
        closeModal: true,
      },
      confirm: {
        text: 'Yes',
        value: true,
        visible: true,
        className: 'btn btn-primary',
        closeModal: true,
      },
    },
    dangerMode: true,
  });

  if (!confirmResult) {
    return;
  }

  try {
    await axios.post(`/clear-invoice/${props.clearInvoice.id}/approve`, {
      status_type: statusType,
    });
    await swal({
      title: 'Success',
      text: `The Request is successfully ${getStatusTypeString(statusType)}.`,
      icon: 'success',
      button: {
        text: 'OK',
        className: 'btn btn-primary',
      },
    });
    window.location.reload(); // Reload the page after showing the alert
  } catch (error) {
    console.error('Approval Error:', error);
    await swal({
      title: 'Error',
      text: `The request failed to ${getStatusTypeString(statusType)}.`,
      icon: 'error',
      button: {
        text: 'OK',
        className: 'btn btn-danger',
      },
    });
  }
};

const rejectRequest = async (statusType) => {
  const confirmResult = await swal({
    title: 'Confirm',
    text: `Are you sure you want to Reject?`,
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
        text: 'Yes, reject it!',
        value: true,
        visible: true,
        className: 'btn btn-danger',
        closeModal: true,
      },
    },
    dangerMode: true,
  });

  if (!confirmResult) {
    return;
  }

  try {
    await axios.post(`/clear-invoice/${props.clearInvoice.id}/reject`, {
      status_type: statusType,
    });
    await swal({
      title: 'Success',
      text: `Request has been rejected for ${getStatusTypeString(statusType)} step.`,
      icon: 'success',
      button: {
        text: 'OK',
        className: 'btn btn-primary',
      },
    });
    window.location.reload(); // Reload the page after showing the alert
  } catch (error) {
    console.error('Rejection Error:', error);
    await swal({
      title: 'Error',
      text: `Failed to reject request for ${getStatusTypeString(statusType)} step.`,
      icon: 'error',
      button: {
        text: 'OK',
        className: 'btn btn-danger',
      },
    });
  }
};

</script>

<template>
  <Main>
    <Head :title="'Clear Invoice Details'" />
    <div class="container a4-size">
      <!-- Add a back button -->
      <div class="row">
        <div class="col text-end">
          <button class="btn btn-secondary" @click="goBack">Back</button>
          <button class="btn btn-primary" @click="printForm">Print</button>
        </div>
      </div>
      <!-- Wrap the content in a unique ID -->
      <div id="printable-area">
        <div class="row justify-content-center px-2">
          <!-- Header Section -->
          <div class="row mb-3">
            <div class="col-3">
              <a class="d-block text-start" href="#!">
                <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
              </a>
            </div>
            <div class="col-6 pt-5">
              <div class="row font-monospace">
                <h5 class="text-uppercase text-center" style="font-family: 'Khmer OS Moul Light';">ពាក្យស្នើសុំទូទាត់ប្រាក់បុរេប្រទាន</h5>
                <h5 class="text-uppercase text-center fw-bold"style="font-family: 'TW Cen MT';">Liquidation of Advance</h5>
              </div>
            </div>
            <div class="col-3">
              <div class="row font-monospace">
                <span class="text-sm-end" style="font-size: x-small;">Code: MJQE0166</span>
                <span class="text-sm-end" style="font-size: x-small;">Version 1.0</span>
              </div>
            </div>
          </div>

          <!-- Personal Information Section -->

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>ឈ្មោះ/Name:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ clearInvoice.user?.name}}</span>
                </div>

                <div class="col-2">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>កាលបរិច្ឆេទ/Date:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{  formatDate(clearInvoice.clear_date) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>អត្តលេខ/Card ID:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ clearInvoice.user?.card_id}}</span>
                </div>

                <div class="col-2">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>លេខសំណើរ/Adv. Ref:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ clearInvoice.ref_no }} 
                    ({{ clearInvoice.cash_request?.currency === 1 ? 'USD' : clearInvoice.cash_request?.currency === 2 ? 'KHR' : '' }})</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>តួនាទី/Position:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ clearInvoice.user?.position}}</span>
                </div>

                <div class="col-2">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>លេខទូទាត់/Liq. Ref:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ clearInvoice.cash_request?.ref_no }} </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Table Section -->
          <div class="row mb-3">
            <div class="table-responsive width-full p-0">
              <table class="table table-bordered border-dark table-sm">
                <thead style="font-size: 12px;">
                  <tr class="text-center">
                    <th style="width: 5%;">ល.រ.<br>No.</th>
                    <th style="width: 60%;">បរិយាយ<br>Description</th>
                    <th style="width: 13%;">សាខា<br>Campus</th>
                    <th>ទឹកប្រាក់<br>Total Amount</th>
                  </tr>
                </thead>
                <tbody class="table-group-divider" style="font-size: 12px;">
                  <tr v-for="(group, index) in groupedByCampus" :key="index">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="text-start">{{ clearInvoice.description }}</td>
                    <td class="text-center">{{ group.campus }}</td>
                    <td class="text-end">{{ parseFloat(group.total_paid).toFixed(4) }} {{ clearInvoice.cash_request?.currency === 1 ? 'USD' : clearInvoice.cash_request?.currency === 2 ? 'KHR' : '' }}</td>
                  </tr>
                  <tr style="height: 200px;">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="2" rowspan="3">Procurement Remark: {{ clearInvoice.remark }}</td>
                    <td class="fw-bold">Actual Expense</td>
                    <td class="fw-bold text-end">{{ actualExpense.toFixed(4) }} {{ clearInvoice.cash_request?.currency === 1 ? 'USD' : clearInvoice.cash_request?.currency === 2 ? 'KHR' : '' }}</td>
                  </tr>
                  <tr>
                    <td class="fw-bold">Cash Advance</td>
                    <td class="fw-bold text-end">{{ parseFloat(clearInvoice.cash_request?.amount || 0).toFixed(4) }} {{ clearInvoice.cash_request?.currency === 1 ? 'USD' : clearInvoice.cash_request?.currency === 2 ? 'KHR' : '' }}</td>
                  </tr>
                  <tr>
                    <td class="fw-bold">Remaining Cash</td>
                    <td class="fw-bold text-end">{{ balance.toFixed(4) }} {{ clearInvoice.cash_request?.currency === 1 ? 'USD' : clearInvoice.cash_request?.currency === 2 ? 'KHR' : '' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Footer Section -->
          <div class="row mb-3" style="height: 150px;">

            <div class="col-4 text-center px-2 mb-3">
              <div>រៀបចំដោយ</div>
              <div>Prepared By</div>
              <img
                :src="getSignatureUrl(clearInvoice.user.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ clearInvoice.user.name }}</div>
                <div>Position: {{ clearInvoice.user.position || 'N/A' }}</div>
                <div>Date: {{ formatDate(clearInvoice.clear_date) }}</div>
              </div>
            </div>
            <!-- Loop through approvals -->
            <div 
              v-for="approval in approvals" 
              :key="approval.status_type" 
              class="col-4 text-center px-2 mb-3"
            >
              <div v-if="approval.label === 'Checked By'">ពិនិត្យដោយ</div>
              <div v-else-if="approval.label === 'Approved By'">អនុម័តដោយ</div>
              <div>{{ approval.label }}</div>
              <img
                v-if="approval.status === 1"
                :src="getSignatureUrl(approval.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div v-else-if="approval.status === -1" class="text-danger mt-2">
                <i class="fas fa-times-circle fa-2x"></i> <!-- Font Awesome Reject Icon -->
                <div>Rejected</div>
              </div>
              <!-- Buttons under the image -->
              <div class="mt-2">
                <button 
                  class="btn btn-success btn-sm" 
                  @click="approveRequest(approval.status_type)"
                  v-if="approval.user_id === currentUser.id && approval.status === 0 &&
                    (
                      (approval.label === 'Checked By') ||
                      (approval.label === 'Approved By' && approvals.find(a => a.label === 'Checked By' && a.status === 1))
                    )"
                >
                  Sign
                </button>
                <button 
                  class="btn btn-danger btn-sm ms-2" 
                  @click="rejectRequest(approval.status_type)"
                  v-if="approval.user_id === currentUser.id && approval.status === 0 &&
                    (
                      (approval.label === 'Checked By') ||
                      (approval.label === 'Approved By' && approvals.find(a => a.label === 'Checked By' && a.status === 1))
                    )"
                >
                  Reject
                </button>
              </div>
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ approval.name }}</div>
                <div>Position: {{ approval.position }}</div>
                <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Main>
</template>

<style scoped>
.a4-size {
  width: 210mm;
  height: 297mm;
  margin: 10mm auto;
  padding: 10mm;
  background: white !important;
  color: black !important; /* Force text color */
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
  overflow: auto;
}

.a4-size * {
  color: black !important; /* Force all child elements to show black text */
}

.border-black {
  border: 1px solid #000 !important; /* Force black border */
}

.table th,
  .table td {
    border: 1px solid black; /* Force dark borders */
  }


@media print {
  .a4-size {
    width: 210mm;
    height: 297mm;
    margin: 10mm auto; /* Ensure margin is applied during printing */
    padding: 20mm !important; /* Enforce padding for print */
    box-shadow: none; /* Remove any shadow for clean printing */
  }

  #printable-area {
    padding: 20mm !important; /* Ensure padding is applied to the printable area */
  }
}
</style>