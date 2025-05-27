<template>
  <Main>
    <Head :title="`${cashRequest.ref_no}`" />
    <div class="container a4-size">
      <!-- Add a back button -->
      <div class="row">
        <div class="col text-end">
          <button class="btn btn-secondary" @click="goBack">Back</button>
          <button class="btn btn-primary" @click="printForm">Print</button>
        </div>
      </div>
      <!-- Wrap the form content in a unique ID -->
      <div id="printable-area">
        <div class="row justify-content-center px-2">
          <!-- Header Section -->
          <div class="row mb-1">
            <div class="col-3">
              <a class="d-block text-start" href="#!">
                <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
              </a>
            </div>
            <div class="col-6 pt-5">
              <div class="row font-monospace">
                <h5 class="text-uppercase text-center" style="font-family: 'Khmer OS Moul Light';">
                  {{ cashRequest.request_type === 1 ? 'ពាក្យស្នើសុំសាច់ប្រាក់បន្ទាប់បន្សំ' : 'ពាក្យស្នើសុំប្រាក់បុរេប្រទាន' }}
                </h5>
                <h5 class="text-uppercase text-center fw-bold" style="font-family: 'TW Cen MT';">
                  {{ cashRequest.request_type === 1 ? 'Petty Cash Request Form' : 'Advance Request Form' }}
                </h5>
                <h6 class="text-uppercase text-center fw-bold text-danger" style="font-family: 'TW Cen MT';"  v-show="cashRequest.request_type === 2">Urgent</h6>
              </div>
            </div>
            <div class="col-3">
              <div class="row font-monospace">
                <span class="text-sm-end" style="font-size: x-small;">{{ cashRequest.request_type === 1 ? 'Code: MJQE0211' : 'Code: MJQE0073' }}</span>
                <span class="text-sm-end" style="font-size: x-small;">{{ cashRequest.request_type === 1 ? 'Version 1.0' : 'Version 1.3' }}</span>
              </div>
            </div>
          </div>

          <!-- Personal Information Section -->

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">

                <div class="col-8">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row">
                    <span>លេខយោង/Ref no:</span>
                  </div>
                </div>
                <div class="col-2 text-end text-danger px-0">
                  <div class="row">
                    <span>{{ cashRequest.ref_no }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">

                <div class="col-8">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row">
                    <span>កាលបរិច្ឆេទ/Date:</span>
                  </div>
                </div>
                <div class="col-2 text-end px-0">
                  <div class="row">
                    <span>{{formatDate(cashRequest.request_date)}}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>អ្នកទទួល/Receiver:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.name || 'N/A' }}</span>
                </div>

                <div class="col-2 text-start">
                  <div class="row mt-2">
                    <span>តួនាទី/Position:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.position || 'N/A' }}</span>
                </div>

                <div class="col-1 text-start p-0">
                  <div class="row mt-2">
                    <span>អត្តលេខ/ID:</span>
                  </div>
                </div>
                <div class="col-1 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.card_id || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>សាខា/Campus:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.campus || 'N/A' }}</span>
                </div>

                <div class="col-3">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>ចំនួនទឹកប្រាក់/Amount:</span>
                  </div>
                </div>
                <div class="col-2 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.amount}}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0 ">
                  <div class="row mt-2">
                    <span>អាជីវកម្ម/Division:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.division || 'N/A' }}</span>
                </div>
                <div class="col-3">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>រូបិយបណ្ណ/Currency:</span>
                  </div>
                </div>
                <div class="col-2 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.currency}}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-12">
              <div class="row">
                <div class="col-2 text-start p-0 ">
                  <div class="row mt-2">
                    <span>ផ្នែក/Department:</span>
                  </div>
                </div>
                <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals.find(approval => approval.label === 'Received By')?.department || 'N/A' }}</span>
                </div>
                <div class="col-3">
                  <div class="row">
                    <!-- <span>TK</span> -->
                  </div>
                </div>

                <div class="col-2 text-start p-0">
                  <div class="row mt-2">
                    <span>តាមរយៈ/Via:</span>
                  </div>
                </div>
                <div class="col-2 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.via}}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-2" v-show="cashRequest.request_type === 2">
            <div class="col-2 text-start p-0">
              <div class="row mt-2">
                <span>សម្រាប់/Advance for:</span>
              </div>
            </div>
            <div class="col-5 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.reason}}</span>
            </div>
          </div>

          <!-- Additional Information Section -->
          <div class="row mb-2 mt-3"  v-show="cashRequest.request_type === 1">
            <span class="text-start p-0">បរិយាយ/Description:</span>
            <div class="col-12 border-black px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.description}}</span>
            </div>
          </div>

          <div class="row mb-2 mt-3" v-show="cashRequest.request_type === 2">
            <span class="text-start p-0">មូលហេតុ/Reason:</span>
            <div class="col-12 border-black px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.description}}</span>
            </div>
          </div>

          <div class="row mb-5">
            <span class="text-start p-0">កំណត់សម្គាល់/Remark:</span>
            <div class="col-12 border-black px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.remark}}</span>
            </div>
          </div>

          <!-- Footer Section -->
          <div class="row mb-3">
            <!-- Requested By -->
            <div class="col-3 text-center px-2 mb-3">
              <div>ស្នើសុំដោយ</div>
              <div>Requested By</div>
              <img
                :src="getSignatureUrl(cashRequest.user.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ cashRequest.user.name }}</div>
                <div>Position: {{ cashRequest.user.position || 'N/A' }}</div>
                <div>Date: {{ formatDate(cashRequest.request_date) }}</div>
              </div>
            </div>

            <!-- Loop through approvals -->
            <div 
              v-for="approval in approvals" 
              :key="approval.status_type" 
              class="col-3 text-center px-2 mb-3"
            >
              <div v-if="approval.label === 'Checked By'">ពិនិត្យដោយ</div>
              <div v-else-if="approval.label === 'Acknowledged By'">ទទួលស្គាល់ដោយ</div>
              <div v-else-if="approval.label === 'Approved By'">អនុម័តដោយ</div>
              <div v-else-if="approval.label === 'Received By'">ទទួលដោយ</div>
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
                  v-if="approval.user_id === currentUser.user.id && approval.status === 0 &&
                    (
                      (approval.label === 'Checked By') ||
                      (approval.label === 'Acknowledged By' && (!approvals.find(a => a.label === 'Checked By') || approvals.find(a => a.label === 'Checked By' && a.status === 1))) ||
                      (approval.label === 'Approved By' && (!approvals.find(a => a.label === 'Checked By') || approvals.find(a => a.label === 'Checked By' && a.status === 1)) && (!approvals.find(a => a.label === 'Acknowledged By') || approvals.find(a => a.label === 'Acknowledged By' && a.status === 1))) ||
                      (approval.label === 'Received By' && approvals.find(a => a.label === 'Approved By' && a.status === 1))
                    )"
                >
                  Sign
                </button>
                <button 
                  class="btn btn-danger btn-sm ms-2" 
                  @click="rejectRequest(approval.status_type)"
                  v-if="approval.user_id === currentUser.user.id && approval.status === 0 &&
                    (
                      (approval.label === 'Checked By') ||
                      (approval.label === 'Acknowledged By' && (!approvals.find(a => a.label === 'Checked By') || approvals.find(a => a.label === 'Checked By' && a.status === 1))) ||
                      (approval.label === 'Approved By' && (!approvals.find(a => a.label === 'Checked By') || approvals.find(a => a.label === 'Checked By' && a.status === 1)) && (!approvals.find(a => a.label === 'Acknowledged By') || approvals.find(a => a.label === 'Acknowledged By' && a.status === 1))) ||
                      (approval.label === 'Received By' && approvals.find(a => a.label === 'Approved By' && a.status === 1))
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

<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import axios from 'axios';
import swal from 'sweetalert';

// Props
const props = defineProps({
  cashRequest: Object,
  currentUser: Object,
  approvals: {
    type: Array,
    default: () => [], // Ensure approvals has a default value
  },
});

// Helper function to format dates
const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};

// Helper function to convert statusType to string
const getStatusTypeString = (statusType) => {
  switch (statusType) {
    case 1:
      return 'Check';
    case 2:
      return 'Acknowledge';
    case 3:
      return 'Approve';
    case 4:
      return 'Receive';
    default:
      return 'Unknown';
  }
};

// Add a method to log and return the signature URL
const getSignatureUrl = (signature) => {
  const url = signature ? `/storage/${signature}` : 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png';
  console.log('Signature URL:', url); // Log the URL to the console
  return url;
};

// Function to print only the form
const printForm = () => {
  const printableContent = document.getElementById('printable-area').innerHTML;
  const originalContent = document.body.innerHTML;

  document.body.innerHTML = printableContent;
  window.print();
  document.body.innerHTML = originalContent;

  // Reload the page to restore functionality
  window.location.reload();
};

// Function to navigate back to the previous page
const goBack = () => {
  window.history.back();
};

// Function to handle approval action
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
    const response = await axios.post(`/cash-request/${props.cashRequest.id}/approve`, {
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
    console.error('Approval Error:', error); // Log the full error object
    await swal({
      title: 'Error',
      text: `The request is failed to ${getStatusTypeString(statusType)}.`,
      icon: 'error',
      button: {
        text: 'OK',
        className: 'btn btn-danger',
      },
    });
  }
};

// Function to handle rejection action
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
    const response = await axios.post(`/cash-request/${props.cashRequest.id}/reject`, {
      status_type: statusType,
    });
    await swal({
      title: 'Success',
      text: `Request have been rejected for ${getStatusTypeString(statusType)} step.`,
      icon: 'success',
      button: {
        text: 'OK',
        className: 'btn btn-primary',
      },
    });
    window.location.reload(); // Reload the page after showing the alert
  } catch (error) {
    console.error('Rejection Error:', error); // Log the full error object
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

<style scoped>
.a4-size {
  width: 210mm;
  height: 297mm;
  margin: 10mm auto; /* Updated margin for A4 paper */
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
