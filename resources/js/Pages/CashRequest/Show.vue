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
        <div class="row justify-content-center">
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
                <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].name }}</span>
                </div>

                <div class="col-2 text-start">
                  <div class="row mt-2">
                    <span>តួនាទី/Position:</span>
                  </div>
                </div>
                <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].position }}</span>
                </div>

                <div class="col-1 text-start p-0">
                  <div class="row mt-2">
                    <span>អត្តលេខ/ID:</span>
                  </div>
                </div>
                <div class="col-1 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].card_id }}</span>
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
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].campus }}</span>
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
                <div class="col-2 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
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
                <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].division }}</span>
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
                <div class="col-2 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
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
                <div class="col-3 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                  <span class="w-100 text-start ps-1 fw-bold">{{ approvals[2].department }}</span>
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
                <div class="col-2 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
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
            <div class="col-5 border border-dark px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.reason}}</span>
            </div>
          </div>

          <!-- Additional Information Section -->
          <div class="row mb-2 mt-3"  v-show="cashRequest.request_type === 1">
            <span class="text-start p-0">បរិយាយ/Description:</span>
            <div class="col-12 border border-dark px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.description}}</span>
            </div>
          </div>

          <div class="row mb-2 mt-3" v-show="cashRequest.request_type === 2">
            <span class="text-start p-0">មូលហេតុ/Reason:</span>
            <div class="col-12 border border-dark px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.description}}</span>
            </div>
          </div>

          <div class="row mb-5">
            <span class="text-start p-0">កំណត់សម្គាល់/Remark:</span>
            <div class="col-12 border border-dark px-1 d-flex align-items-center" style="min-height: 50px; height: auto;">
              <span class="w-100 text-start ps-1 fw-bold">{{cashRequest.remark}}</span>
            </div>
          </div>

          <!-- Footer Section -->
          <div class="row mb-3">
            <!-- Requested By -->
            <div class="col-3 text-center px-2">
              <div>ស្នើសុំដោយ</div>
              <div>Requested By</div>
              <img
                :src="'https://www.shutterstock.com/image-vector/signature-vector-hand-drawn-autograph-600nw-2387543207.jpg'"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ cashRequest.user.name }}</div>
                <div>Position: {{ cashRequest.user.position || 'N/A' }}</div>
                <div>Date: {{ formatDate(cashRequest.request_date) }}</div>
              </div>
            </div>

            <!-- Checked By -->
            <div class="col-3 text-center px-2" v-if="approvals[0] && approvals[0].label === 'Checked By'">
              <div>ពិនិត្យដោយ</div>
              <div>{{ approvals[0].label }}</div>
              <img
                :src="approvals[0].signature || 'https://www.shutterstock.com/image-vector/signature-vector-hand-drawn-autograph-600nw-2387543207.jpg'"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ approvals[0].name }}</div>
                <div>Position: {{ approvals[0].position }}</div>
                <div>Date: {{ formatDate(approvals[0].date) }}</div>
              </div>
            </div>

            <!-- Approved By -->
            <div class="col-3 text-center px-2" v-if="approvals[1] && approvals[1].label === 'Approved By'">
              <div>អនុម័តដោយ</div>
              <div>{{ approvals[1].label }}</div>
              <img
                :src="approvals[1].signature || 'https://www.shutterstock.com/image-vector/signature-vector-hand-drawn-autograph-600nw-2387543207.jpg'"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ approvals[1].name }}</div>
                <div>Position: {{ approvals[1].position }}</div>
                <div>Date: {{ formatDate(approvals[1].date) }}</div>
              </div>
            </div>

            <!-- Received By -->
            <div class="col-3 text-center px-2" v-if="approvals[2] && approvals[2].label === 'Received By'">
              <div>ទទួលដោយ</div>
              <div>{{ approvals[2].label }}</div>
              <img
                :src="approvals[2].signature || 'https://www.shutterstock.com/image-vector/signature-vector-hand-drawn-autograph-600nw-2387543207.jpg'"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ approvals[2].name }}</div>
                <div>Position: {{ approvals[2].position }}</div>
                <div>Date: {{ formatDate(approvals[2].date) }}</div>
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

// Props
const props = defineProps({
  cashRequest: Object,
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
