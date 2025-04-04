<template>
    <Main>
      <Head :title="'Clear Invoice Details'" />
      <div class="container a4-size">
        <!-- Add a back button -->
        <div class="row">
          <div class="col text-end">
            <button class="btn btn-secondary">Back</button>
            <button class="btn btn-primary">Print</button>
          </div>
        </div>
        <!-- Wrap the content in a unique ID -->
        <div id="printable-area">
          <div class="row justify-content-center">
            <!-- Header Section -->
            <div class="row mb-3">
              <div class="col-3">
                <a class="d-block text-start" href="#!">
                  <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                </a>
              </div>
              <div class="col-6 pt-5">
                <div class="row font-monospace">
                  <h5 class="text-uppercase text-center" style="font-family: 'Khmer OS Moul Light';">ពាក្យស្នើសុំថវិកា</h5>
                  <h5 class="text-uppercase text-center fw-bold"style="font-family: 'TW Cen MT';">Cash Request Form</h5>
                </div>
              </div>
              <div class="col-3">
                <div class="row font-monospace">
                  <span class="text-sm-end" style="font-size: x-small;">Code: MJQE0072</span>
                  <span class="text-sm-end" style="font-size: x-small;">Version 1.3</span>
                </div>
              </div>
            </div>
  
            <!-- Personal Information Section -->
  
            <div class="row mb-1">
              <div class="col-12">
                <div class="row">
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>អ្នកទទួល/Receiver:</span>
                    </div>
                  </div>
                  <div class="col-4 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">DDDDDDDDDDDDDDD</span>
                  </div>
  
                  <div class="col-1">
                    <div class="row">
                      <!-- <span>TK</span> -->
                    </div>
                  </div>
  
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>លេខយោង/Ref:</span>
                    </div>
                  </div>
                  <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold"></span>
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
                  <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold"></span>
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
                  <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold"> 
                      </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12">
                <div class="row">
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>អាជីវកម្ម/Division:</span>
                    </div>
                  </div>
                  <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold"></span>
                  </div>
  
                  <div class="col-2">
                    <div class="row">
                      <!-- <span>TK</span> -->
                    </div>
                  </div>
  
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>ផ្នែក/Department:</span>
                    </div>
                  </div>
                  <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold"></span>
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
                      <th>ល.រ.<br>No.</th>
                      <th>បរិយាយ<br>Description</th>
                      <th>សាខា<br>Campus</th>
                      <th>ទឹកប្រាក់<br>Total Amount</th>
                    </tr>
                  </thead>
                  <tbody class="table-group-divider" style="font-size: 12px;">
                    <tr>
                      <td class="text-center"></td>
                      <td class="text-start"></td>
                      <td class="text-center"></td>
                      <td class="text-end"></td>
                    </tr>
                    <tr style="height: 200px;">
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                    </tr>
                    <tr>
                      <td colspan="3" rowspan="3" class="text-center fw-bold">សរុប/Total</td>
                      <td class="fw-bold"></td>
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
                  :src="getSignatureUrl(statement.cleared_by?.signature || '')"
                  alt="Signature"
                  style="width: 130px; height: 80px; object-fit: contain;"
                />
                <div class="border-top mt-2 pt-1 text-start">
                  <div>Name: {{ statement.cleared_by?.name || 'N/A' }}</div>
                  <div>Position: {{ statement.cleared_by?.position || 'N/A' }}</div>
                  <div>Date: {{ statement.clear_date ? formatDate(statement.clear_date) : 'N/A' }}</div>
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
  
  <script setup>
  import { ref, computed } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';
  
  // Define the props using defineProps()
  const props = defineProps({
    statement: {
      type: Object,
      required: true,
    },
    approvals: {
      type: Array,
      default: () => [],
    },
    currentUser: {
      type: Object,
      required: true,
    },
  });
  
  // Helper functions
  const getSignatureUrl = (signature) => {
    return signature ? `/storage/${signature}` : 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png'; // Fallback image
  };
  
  const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: '2-digit' };
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', options);
  };
  
  const approveRequest = async (statusType) => {
    // ...existing code for approval logic...
  };
  
  const rejectRequest = async (statusType) => {
    // ...existing code for rejection logic...
  };
  </script>
  
  <style scoped>
  .a4-size {
    width: 210mm;
    height: 297mm;
    margin: 10mm auto; /* Updated margin for A4 paper */
    padding: 10mm;
    background: white;
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