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

</script>

<template>
  <Main>
    <Head :title="'Clear Invoice Details'" />
    <section class="py-3 py-md-5 bg-white"> <!-- Add bg-white class for white background -->
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-lg-9 col-xl-8 col-xxl-7">
            <div class="row gy-3 mb-0 "> <!-- Add border class -->
              <div class="col-3 "> <!-- Add border class -->
                <a class="d-block" href="#!">
                  <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                </a>
              </div>
            </div>
            <div class="row mb-3 "> <!-- Add border class -->
                <div class="col-3 "></div> <!-- Empty column to balance the layout and add border class -->
              <div class="col-6 d-flex align-items-center justify-content-center mb-0"> <!-- Add border class -->
                <h2 class="text-uppercase m-0">{{ getClearTypeHeading(clearInvoice.clear_type) }}</h2>
              </div>
              <div class="col-12 col-sm-6 col-md-5 "> <!-- Add border class -->
                <h5 class="text-center text-decoration-underline text-primary">Request Info</h5>
                <div class="row">
                  <span class="col-6">Requested By:</span>
                  <span class="col-6 text-start">{{ clearInvoice.cash_request?.user?.name }}</span> <!-- Align text to the left -->
                  <span class="col-6">Cash Ref:</span>
                  <span class="col-6 text-danger text-start">{{ clearInvoice.cash_request?.ref_no }}</span> 
                  <span class="col-6">Status</span>
                  <span class="col-6 text-start">{{ clearInvoice.cash_request?.status === 1 ? 'Cleared' : 'Pending' }}</span>
                  <span class="col-6">Requested Date</span>
                  <span class="col-6 text-start">{{ formatDate(clearInvoice.cash_request?.request_date) }}</span> <!-- Format the date -->
                  <span class="col-6">Currency</span>
                  <span class="col-6 text-start">{{ clearInvoice.cash_request?.currency}}</span>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-md-2 "></div>  <!-- Empty column to balance the layout and add border class -->
              <div class="col-12 col-sm-6 col-md-5 "> <!-- Add border class -->
                <h5 class="text-center text-decoration-underline text-primary">Clear Info</h5>
                <div class="row">
                  <span class="col-6">Cleared By</span>
                  <span class="col-6 text-start">{{ clearInvoice.user?.name}}</span>
                  <span class="col-6">Clear Ref</span>
                  <span class="col-6 text-danger text-start">{{ clearInvoice.ref_no }}</span>
                  <span class="col-6">Status</span>
                  <span class="col-6 text-start">{{ clearInvoice.status === 1 ? 'Approved' : 'Pending' }}</span>
                  <span class="col-6">Clear Date</span>
                  <span class="col-6 text-start">{{  formatDate(clearInvoice.clear_date) }}</span>
                </div>
              </div>
            </div>
            <div class="row mb-3 "> <!-- Add border class -->
              <div class="col-12 "> <!-- Add border class -->
                <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <th scope="col" class="text-uppercase">Item Code</th>
                        <th scope="col" class="text-uppercase">Description</th>
                        <th scope="col" class="text-uppercase text-end">Quantity</th>
                        <th scope="col" class="text-uppercase text-end">Unit Price</th>
                        <th scope="col" class="text-uppercase text-end">Grand Total</th>
                      </tr>
                    </thead>
                    <tbody class="table-group-divider">
                      <tr v-for="item in purchaseInvoiceItems" :key="item.id">
                        <td>{{ item.product?.sku }}</td>
                        <td>{{ item.description }}</td>
                        <td class="text-end">{{ item.qty }}</td>
                        <td class="text-end">{{ item.unit_price }}</td>
                        <td class="text-end">{{ parseFloat(item.paid_amount || 0).toFixed(4) }}</td>
                      </tr>
                      <tr >
                        <td colspan="4" class="text-end">Request Amount</td>
                        <td class="text-end">{{ clearInvoice.cash_request?.amount }}</td>
                      </tr>
                      <tr>
                        <td colspan="4" class="text-end">Actual Expense</td>
                        <td class="text-end">{{ actualExpense }}</td>
                      </tr>
                      <tr>
                        <th scope="row" colspan="4" class="text-uppercase text-end">Balance</th>
                        <td class="text-end">{{ balance }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="row "> <!-- Add border class -->
              <div class="col-12 text-end "> <!-- Add border class -->
                <!-- <button type="button" class="btn btn-primary mb-3">Download Invoice</button>
                <button type="button" class="btn btn-danger mb-3">Submit Payment</button> -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </Main>
</template>
