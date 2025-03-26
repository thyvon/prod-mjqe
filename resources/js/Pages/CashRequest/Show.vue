<template>
  <Main>
    <Head :title="'View Cash Request'" />
    <div class="container a4-size">
      <!-- Add a print button -->
      <div class="row mb-3">
        <div class="col text-end">
          <button class="btn btn-primary" @click="printForm">Print</button>
        </div>
      </div>
      <!-- Wrap the form content in a unique ID -->
      <div id="printable-area">
        <div class="row justify-content-center">
          <!-- Header Section -->
          <div class="row mb-3">
            <div class="col-3">
              <a class="d-block text-start" href="#!">
                <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="Logo" width="135" height="44">
              </a>
            </div>
            <div class="col-6 text-center">
              <h5 class="text-uppercase" style="font-family: 'Khmer OS Moul Light';">ពាក្យស្នើសុំសាច់ប្រាក់បន្ទាប់បន្សំ</h5>
              <h5 class="text-uppercase fw-bold" style="font-family: 'TW Cen MT';">Petty Cash Request Form</h5>
            </div>
            <div class="col-3 text-end">
              <span class="d-block" style="font-size: x-small;">Code: MJQE0211</span>
              <span class="d-block" style="font-size: x-small;">Version 1.0</span>
            </div>
          </div>

          <!-- Personal Information Section -->
          <div class="row mb-1">
            <div class="col-md-12">
              <div class="row">
                <div class="col-sm-10 text-end">លេខយោង/Ref No.:</div>
                <div class="col-sm-2 text-danger text-center p-0">{{ cashRequest.ref_no }}</div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <div class="col-md-12">
              <div class="row">
                <div class="col-sm-10 text-end">កាលបរិច្ឆេទ/Date:</div>
                <div class="col-sm-2 text-danger text-center p-0">{{ formatDate(cashRequest.request_date) }}</div>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <div class="row">
                <div class="col-sm-4"><div>អ្នកទទួល/</div>Receiver</div>
                <div class="col-sm-8 border border-dark pt-2">{{ cashRequest.request_by }}</div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="row">
                <div class="col-sm-3"><div>តួនាទី/</div>Position</div>
                <div class="col-sm-9 border border-dark pt-2">Procurement Officer</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="row">
                <div class="col-sm-4"><div>អត្តលេខ/</div>ID Card</div>
                <div class="col-sm-8 border border-dark pt-2 text-center">3665</div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-3">
              <div class="row">
                <div class="col-sm-5"><div>សាខា/</div>Campus</div>
                <div class="col-sm-7 border border-dark pt-2">{{ cashRequest.campus }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="row">
                <div class="col-sm-5"><div>អាជីវកម្ម/</div>Division</div>
                <div class="col-sm-7 border border-dark pt-2">{{ cashRequest.division }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="row">
                <div class="col-sm-6"><div>ផ្នែក/</div>Department</div>
                <div class="col-sm-6 border border-dark pt-2">{{ cashRequest.department }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="row">
                <div class="col-sm-5"><div>ស្ថានភាព/</div>Status</div>
                <div class="col-sm-7 border border-dark pt-2 fw-bold text-danger">Urgent</div>
              </div>
            </div>
          </div>

          <!-- Financial Information Section -->

          <!-- <div class="row mb-3 mt-3">
            <div class="col-md-4">
              <div class="row">
                <div class="col-sm-7">ចំនួនទឹកប្រាក់/Amount</div>
                <div class="col-sm-5 border border-dark">{{ cashRequest.amount }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="row">
                <div class="col-sm-7">ចំនួនទឹកប្រាក់/Amount</div>
                <div class="col-sm-5 border border-dark">{{ cashRequest.amount }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="row">
                <div class="col-sm-7">ចំនួនទឹកប្រាក់/Amount</div>
                <div class="col-sm-5 border border-dark">{{ cashRequest.amount }}</div>
              </div>
            </div>
          </div> -->

          <div class="row mb-4">
            <div class="col-md-2">
              <label>ទឹកប្រាក់/Amount</label>
              <div class="row">
                <div class="col-sm-10 border border-dark mx-2 p-1">{{ cashRequest.amount }}</div>
              </div>
            </div>
            <div class="col-md-2">
              <label>រូបិយប័ណ្ណ/Currency</label>
              <div class="row">
                <div class="col-sm-10 border border-dark mx-2 p-1">{{ cashRequest.currency }}</div>
              </div>
            </div>
            <div class="col-md-2">
              <label>តាមរយៈ/Via</label>
              <div class="row">
                <div class="col-sm-10 border border-dark mx-2 p-1">{{ cashRequest.via }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <label>ស្នើសុំសម្រាប់/Request for:</label>
              <div class="row">
                <div class="col-sm-12 border border-dark p-1">{{ cashRequest.via }}</div>
              </div>
            </div>
          </div>

          <!-- Additional Information Section -->
          <label class="ml-4">បរិយាយ/Description</label>
          <div class="row mb-3 col-12">
            <span class="border border-dark ml-1" style="height: 60px;">{{ cashRequest.amount }}</span>
          </div>
          <label class="ml-4">បរិយាយ/Description</label>
          <div class="row mb-5 col-12" style="padding-bottom: 80px;">
            <span class="border border-dark ml-1" style="height: 60px;">{{ cashRequest.amount }}</span>
          </div>

          <!-- Footer Section -->
          <div class="row mb-3">
            <div class="col-md-3 text-center">
              <div class="fw-bold">ស្នើសុំដោយ</div>
              <div>Requested By</div>
              <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="Signature" style="width: 130px; height: 80px; object-fit: contain;">
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: Vun Thy</div>
                <div>Position: Procurement Officer</div>
                <div>Date: Mar 20, 2025</div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="fw-bold">ត្រួតពីនិត្យដោយ</div>
              <div>Checked By</div>
              <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="Signature" style="width: 130px; height: 80px; object-fit: contain;">
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: Vun Thy</div>
                <div>Position: Procurement Officer</div>
                <div>Date: Mar 20, 2025</div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="fw-bold">អនុម័តដោយ</div>
              <div>Approved By</div>
              <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="Signature" style="width: 130px; height: 80px; object-fit: contain;">
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: Vun Thy</div>
                <div>Position: Procurement Officer</div>
                <div>Date: Mar 20, 2025</div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="fw-bold">ទទួលដោយ</div>
              <div>Received By</div>
              <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="Signature" style="width: 130px; height: 80px; object-fit: contain;">
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: Vun Thy</div>
                <div>Position: Procurement Officer</div>
                <div>Date: Mar 20, 2025</div>
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
};
</script>

<style scoped>
.a4-size {
  width: 210mm;
  height: 297mm;
  margin: 0 auto;
  padding: 10mm;
  background: white;
}
</style>
