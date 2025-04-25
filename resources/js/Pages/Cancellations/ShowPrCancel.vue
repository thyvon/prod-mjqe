<script setup>
import { ref, reactive, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

// Define the props using defineProps()
const props = defineProps({
  cancellation: {
    type: Object,
    required: true,
  },
  users: Array,
  approvals: {
    type: Array,
    default: () => [], // Ensure approvals has a default value
  },
  currentUser: {
    type: Object,
    required: true,
  },
});

console.log('Current User:', props.currentUser);
console.log('Approvals:', props.approvals);
console.log('Cancellation:', props.cancellation);

// Local state for clear invoice
const cancellation = reactive(props.cancellation);
const cancellationItems = ref(props.cancellationitems);

const getClearTypeHeading = (clearType) => {
  return clearType === 1 ? 'Clear Petty Cash' : 'Clear Advance';
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};


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
    case -1:
      return 'Reject';
    case 3:
      return 'Approve';
    case 4:
      return 'Authorize';
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
    await axios.post(`/cancellations/${props.cancellation.id}/approve`, {
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
    await axios.post(`/cancellations/${props.cancellation.id}/reject`, {
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
    <Head :title="'Cancellations'" />
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
                <h5 class="text-uppercase text-center" style="font-family: 'Khmer OS Moul Light';">ពាក្យស្នើសុំលុបសំណើរ</h5>
                <h5 class="text-uppercase text-center fw-bold"style="font-family: 'TW Cen MT';">Cancellation of Purchase Request</h5>
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
                  <span class="w-100 text-start ps-1 fw-bold">{{ cancellation.user?.name}}</span>
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
                  <span class="w-100 text-start ps-1 fw-bold">{{  formatDate(cancellation.cancellation_date) }}</span>
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
                  <span class="w-100 text-start ps-1 fw-bold">{{ cancellation.user?.card_id}}</span>
                </div>

                <div class="col-2">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>លេខយោង/Ref.</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ cancellation.cancellation_no }}</span>
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
                  <span class="w-100 text-start ps-1 fw-bold">{{ cancellation.user?.position}}</span>
                </div>

                <div class="col-2">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>លេខ PR/PR Number:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ cancellation.purchase_request?.pr_number }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Table Section -->
          <div class="row">
            <div class="table-responsive width-full p-0">
              <table class="table table-bordered border-dark table-sm">
                <thead style="font-size: 12px;">
                  <tr class="text-center">
                    <th style="width: 5%;">ល.រ.<br>No.</th>
                    <th style="width: 13%;">លេខកូដ<br>Item Code</th>
                    <th style="width: 40%;">បរិយាយ<br>Description</th>
                    <th style="width: 5%;">ឯកតា<br>UOM</th>
                    <th style="width: 5%;">ចំនួន<br>Qty</th>
                    <th style="width: 5%;">តម្លៃ<br>Price</th>
                    <th style="width: 10%;">តម្លៃសរុប<br>Total Price</th>
                    <th style="width: 17%;">កំណត់សម្គាល់<br>Remark</th>
                  </tr>
                </thead>
                <tbody class="table-group-divider" style="font-size: 12px;">
                  <tr v-for="(item, index) in cancellation.items" :key="item.id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <!-- <td class="text-center">{{ item.purchase_request?.pr_number }}</td> -->
                    <td class="text-center">{{ item.purchase_request_item?.product?.sku }}</td>
                    <td class="text-start">{{ item.purchase_request_item?.product?.product_description }} {{ item.purchase_request_item?.remark }}</td>
                    <td class="text-center">{{ item.purchase_request_item?.uom }}</td>
                    <td class="text-center">{{ item.qty }}</td>
                    <td class="text-end">{{ parseFloat(item.purchase_request_item?.unit_price).toFixed(2) }}</td>
                    <td class="text-end">
                      {{ (parseFloat(item.qty) * parseFloat(item.purchase_request_item?.unit_price)).toFixed(2) }}
                    </td>
                    <td class="text-start">{{ item.cancellation_reason || 'N/A' }}</td>
                  </tr>

                  <!-- Total row -->
                  <tr>
                    <td colspan="6" class="text-end fw-bold">សរុប/Total:</td>
                    <td colspan="2" class="text-center fw-bold">
                      {{
                        cancellation.items.reduce((total, item) => {
                          const qty = parseFloat(item.qty);
                          const unitPrice = parseFloat(item.purchase_request_item?.unit_price || 0);
                          return total + qty * unitPrice;
                        }, 0).toFixed(2)
                      }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-12">
              <div class="row">
                <div class="col-12 text-start p-0">
                  <div class="row mt-2">
                    <span class="fw-bold">Reason of amendment where the PR have been approved.</span>
                  </div>
                </div>
                <div class="col-12 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <!-- Display Summernote content with v-html -->
                  <span class="w-100 text-start ps-1" v-html="cancellation.cancellation_reason"></span>
                </div>
                <div class="col-12 border-start border-end border-bottom border-dark px-1 d-flex align-items-center" style="min-height: 35px; height: auto;">
                  <div class="row mt-2">
                    <span class="w-100 text-start fw-bold">=>Approver of this form is referred to Financial Matter in Part 2 in latest TOCA</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Footer Section -->
          <div class="row mb-3" style="height: 150px;">

            <div class="col-4 text-center px-2 mb-3">
              <div>រៀបចំដោយ</div>
              <div>Prepared By</div>
              <img
                :src="getSignatureUrl(cancellation.user.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ cancellation.user.name }}</div>
                <div>Position: {{ cancellation.user.position || 'N/A' }}</div>
                <div>Date: {{ formatDate(cancellation.cancellation_date) }}</div>
              </div>
            </div>

            <div class="col-4 text-center px-2 mb-3"></div>
            <!-- Loop through approvals -->
            <!-- Approved By & others -->
            <div 
              v-for="approval in approvals.filter(a => a.label !== 'Authorized By')" 
              :key="approval.status_type" 
              class="col-4 text-center px-2 mb-3"
            >
              <div v-if="approval.label === 'Approved By'">អនុម័តដោយ</div>
              <div>{{ approval.label }}</div>

              <!-- Signature or rejected icon -->
              <img
                v-if="approval.status === 1"
                :src="getSignatureUrl(approval.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div v-else-if="approval.status === -1" class="text-danger mt-2">
                <i class="fas fa-times-circle fa-2x"></i>
                <div>Rejected</div>
              </div>

              <!-- Action buttons -->
              <div class="mt-2">
                <button 
                  class="btn btn-success btn-sm" 
                  @click="approveRequest(approval.status_type)"
                  v-if="approval.user_id === currentUser.id && approval.status === 0"
                >
                  Sign
                </button>
                <button 
                  class="btn btn-danger btn-sm ms-2" 
                  @click="rejectRequest(approval.status_type)"
                  v-if="approval.user_id === currentUser.id && approval.status === 0"
                >
                  Reject
                </button>
              </div>

              <!-- Metadata -->
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ approval.name }}</div>
                <div>Position: {{ approval.position }}</div>
                <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
              </div>
            </div>


            <!-- Authorized By only -->
            <div 
              v-for="approval in approvals.filter(a => a.label === 'Authorized By')" 
              :key="approval.status_type" 
              class="col-4 text-center px-2 mb-3"
            >
            <!-- <div class="fw-bold text-primary">
              អនុញ្ញាតដោយ  
              <div class="text-primary" style="font-size: 0.85rem;">Authorized By</div>
            </div> -->


              <!-- ✅ Signed View -->
              <template v-if="approval.status === 1">
                <!-- <img
                  :src="getSignatureUrl(approval.signature)"
                  alt="Signature"
                  style="width: 130px; height: 80px; object-fit: contain;"
                /> -->
                <div class="mt-2 pt-1 text-start text-primary">
                  <i class="fas fa-check-circle fa-2x"></i>
                  <div>Authorized By: {{ approval.name }}</div>
                  <div>Position: {{ approval.position }}</div>
                  <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
                </div>
              </template>

              <!-- ❌ Rejected View -->
              <template v-else-if="approval.status === -1">
                <div class="text-danger mt-2">
                  <i class="fas fa-times-circle fa-2x"></i>
                  <div>Unauthorized</div>
                </div>
                <div class="border-top mt-2 pt-1 text-start">
                  <div>Rejected By: {{ approval.name }}</div>
                  <div>Position: {{ approval.position }}</div>
                  <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
                </div>
              </template>

              <!-- 🕒 Pending View with Buttons -->
              <template v-else>
                <div class="badge bg-warning text-dark mt-2">
                  <i class="fas fa-clock"></i> Pending
                </div>
              <div class="fw-bold text-primary">
                  អនុញ្ញាតដោយ  
              <div class="text-primary" style="font-size: 0.85rem;">Authorized By</div>
            </div>
                <div class="mt-2">
                  <button 
                    class="btn btn-success btn-sm" 
                    @click="approveRequest(approval.status_type)"
                    v-if="approval.user_id === currentUser.id &&
                          approvals.find(a => a.label === 'Approved By' && a.status === 1)"
                  >
                    Sign
                  </button>
                  <button 
                    class="btn btn-danger btn-sm ms-2" 
                    @click="rejectRequest(approval.status_type)"
                    v-if="approval.user_id === currentUser.id &&
                          approvals.find(a => a.label === 'Approved By' && a.status === 1)"
                  >
                    Reject
                  </button>
                </div>
                <!-- <div class="border-top mt-2 pt-1 text-start">
                  <div>Name: {{ approval.name }}</div>
                  <div>Position: {{ approval.position }}</div>
                  <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
                </div> -->
              </template>
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