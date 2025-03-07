<template>
    <Main>
      <Head :title="'View Cash Request'" />

      <div class="invoice" ref="invoiceContent">
        <!-- Invoice Company -->
        <div class="invoice-company">
          <span class="float-end hidden-print">
            <a href="javascript:;" class="btn btn-sm btn-white mb-10px" @click="exportAsPDF">
              <i class="fa fa-file-pdf t-plus-1 text-danger fa-fw fa-lg"></i> Export as PDF
            </a>
            <a href="javascript:;" class="btn btn-sm btn-white mb-10px" @click="printInvoice">
              <i class="fa fa-print t-plus-1 fa-fw fa-lg"></i> Print
            </a>
            <a href="javascript:;" class="btn btn-sm btn-white mb-10px" @click="openEditModal">
              <i class="fa fa-edit t-plus-1 fa-fw fa-lg"></i> Edit
            </a>
          </span>
            {{ requestTypeLabel }} Request
        </div>
        <!-- Invoice Header -->
        <div class="invoice-header">
          <div class="invoice-from">
            <address class="mt-5px mb-5px">
              <strong class="text-dark">Requester: {{ form.request_by }}</strong><br />
              Position: {{ form.position }}<br />
              Campus: {{ form.campus }}<br />
              Division: {{ form.division }}<br />
              Department: {{ form.department }}<br />
            </address>
          </div>

          <div class="invoice-date">
            <div class="date text-dark mt-5px">{{ formatDate(form.request_date) }}</div>
            <div class="invoice-detail">
              #{{ form.id_card }}<br />
              {{ form.request_type }}
            </div>
          </div>
        </div>

        <!-- Invoice Content -->
        <div class="invoice-content">
          <!-- Financial Information -->
          <div class="table-responsive">
            <table class="table table-invoice">
              <thead>
                <tr>
                  <th>DESCRIPTION</th>
                  <th class="text-center" width="10%">CURRENCY</th>
                  <th class="text-center" width="10%">RATE</th>
                  <th class="text-center" width="20%">TOTAL AMOUNT</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    {{ form.description }}
                  </td>
                  <td class="text-center">{{ form.currency }}</td>
                  <td class="text-center">{{ form.exchange_rate}}</td>
                  <td class="text-center">{{ form.amount }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Invoice Price -->
          <div class="invoice-price">
            <div class="invoice-price-left">
              <div class="invoice-price-row">
                <div class="sub-price">
                  <small>Transfer via: </small>
                  <span class="text-dark">{{ form.via }}</span>
                </div>
                <!-- <div class="sub-price">
                  <i class="fa fa-plus text-muted"></i>
                </div>
                <div class="sub-price">
                  <small>PAYPAL FEE (5.4%)</small>
                  <span class="text-dark">{{ form.amount * 0.054 }}</span>
                </div> -->
              </div>
            </div>
            <!-- <div class="invoice-price-right">
              <small>TOTAL</small> <span class="fw-bold">{{ form.amount}} {{ form.currency }}</span>
            </div> -->
          </div>
        </div>

        <!-- Invoice Note -->
        <div class="invoice-note">
          * {{ form.reason }}<br />
          * {{ form.remark }}
        </div>

        <!-- Invoice Footer -->
        <div class="invoice-footer">
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

      <!-- Modal for Edit Cash Request -->
      <div class="modal fade" id="cashRequestModal" tabindex="-1" aria-labelledby="cashRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="cashRequestModalLabel">Edit Cash Request</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveCashRequest">
                <div class="row">
                  <!-- Left side for requester information -->
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="user_id" class="form-label">Request By</label>
                      <select v-model="form.user_id" class="form-select select2" id="user_id" required>
                        <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                      </select>
                      <div v-if="validationErrors.user_id" class="text-danger">{{ validationErrors.user_id[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="position" class="form-label">Position</label>
                      <input v-model="form.position" type="text" class="form-control" id="position" required />
                      <div v-if="validationErrors.position" class="text-danger">{{ validationErrors.position[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="id_card" class="form-label">ID Card</label>
                      <input v-model="form.id_card" type="text" class="form-control" id="id_card" required />
                      <div v-if="validationErrors.id_card" class="text-danger">{{ validationErrors.id_card[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="campus" class="form-label">Campus</label>
                      <select v-model="form.campus" class="form-select select2" id="campus" required>
                        <option value="CEN">CEN</option>
                        <option value="TK">TK</option>
                      </select>
                      <div v-if="validationErrors.campus" class="text-danger">{{ validationErrors.campus[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="division" class="form-label">Division</label>
                      <input v-model="form.division" type="text" class="form-control" id="division" required />
                      <div v-if="validationErrors.division" class="text-danger">{{ validationErrors.division[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="department" class="form-label">Department</label>
                      <input v-model="form.department" type="text" class="form-control" id="department" required />
                      <div v-if="validationErrors.department" class="text-danger">{{ validationErrors.department[0] }}</div>
                    </div>
                  </div>
                  <!-- Right side for other information -->
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="request_type" class="form-label">Request Type</label>
                      <select v-model="form.request_type" class="form-select select2" id="request_type" required>
                        <option value="Petty Cash">Petty Cash</option>
                        <option value="Cash Advance">Cash Advance</option>
                      </select>
                      <div v-if="validationErrors.request_type" class="text-danger">{{ validationErrors.request_type[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="request_date" class="form-label">Request Date</label>
                      <input v-model="form.request_date" type="date" class="form-control" id="request_date" required />
                      <div v-if="validationErrors.request_date" class="text-danger">{{ validationErrors.request_date[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="currency" class="form-label">Currency</label>
                      <select v-model="form.currency" class="form-select select2" id="currency" required>
                        <option value="USD">USD</option>
                        <option value="KHR">KHR</option>
                      </select>
                      <div v-if="validationErrors.currency" class="text-danger">{{ validationErrors.currency[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="exchange_rate" class="form-label">Exchange Rate</label>
                      <input v-model="form.exchange_rate" type="number" step="0.01" class="form-control" id="exchange_rate" required />
                      <div v-if="validationErrors.exchange_rate" class="text-danger">{{ validationErrors.exchange_rate[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="amount" class="form-label">Amount</label>
                      <input v-model="form.amount" type="number" step="0.01" class="form-control" id="amount" required />
                      <div v-if="validationErrors.amount" class="text-danger">{{ validationErrors.amount[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="via" class="form-label">Payment Method</label>
                      <select v-model="form.via" class="form-select select2" id="via" required>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                      </select>
                      <div v-if="validationErrors.via" class="text-danger">{{ validationErrors.via[0] }}</div>
                    </div>
                  </div>
                  <!-- Middle section for description, reason, and remark -->
                  <div class="col-12">
                    <div class="mb-3">
                      <label for="description" class="form-label">Description</label>
                      <textarea v-model="form.description" class="form-control" id="description"></textarea>
                      <div v-if="validationErrors.description" class="text-danger">{{ validationErrors.description[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="reason" class="form-label">Reason</label>
                      <textarea v-model="form.reason" class="form-control" id="reason"></textarea>
                      <div v-if="validationErrors.reason" class="text-danger">{{ validationErrors.reason[0] }}</div>
                    </div>
                    <div class="mb-3">
                      <label for="remark" class="form-label">Remark</label>
                      <textarea v-model="form.remark" class="form-control" id="remark"></textarea>
                      <div v-if="validationErrors.remark" class="text-danger">{{ validationErrors.remark[0] }}</div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary">Update</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Main>
  </template>

  <script setup>
  import { ref, onMounted, nextTick, computed } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';
  import { usePage } from '@inertiajs/vue3';
  import axios from 'axios';

  // Props
  const props = defineProps({
    cashRequest: Object,
    users: Array,
  });

  // Reactive state for form data and user
  const form = ref({
    id: props.cashRequest.id || '', // Ensure the id is set
    request_type: props.cashRequest.request_type || '',
    request_date: props.cashRequest.request_date ? props.cashRequest.request_date.split('T')[0] : '', // Ensure date format is "yyyy-MM-dd"
    user_id: props.cashRequest.user_id || '',
    request_by: props.cashRequest.request_by || '',
    position: props.cashRequest.position || '',
    id_card: props.cashRequest.id_card || '',
    campus: props.cashRequest.campus || '',
    division: props.cashRequest.division || '',
    department: props.cashRequest.department || '',
    description: props.cashRequest.description || '',
    currency: props.cashRequest.currency || '',
    exchange_rate: props.cashRequest.exchange_rate || '',
    amount: props.cashRequest.amount || '',
    via: props.cashRequest.via || '',
    reason: props.cashRequest.reason || '',
    remark: props.cashRequest.remark || '',
  });

  const validationErrors = ref({});
  const user = ref({ name: '' });

  // Fetch user details based on user_id
  onMounted(() => {
    const userId = form.value.user_id;
    if (userId && props.users) {
      user.value = props.users.find(u => u.id === userId) || { name: 'Unknown' };
      form.value.request_by = user.value.name;
    }
  });

  const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};

  const requestTypeLabel = computed(() => {
    return form.value.request_type == '1' ? 'Petty Cash' : form.value.request_type == '2' ? 'Advance' : form.value.request_type;
  });

  // Handlers
  const goBack = () => {
    window.history.back();
  };

  const printInvoice = () => {
    const printContents = document.querySelector('.invoice').innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
  };

  const exportAsPDF = () => {
    // Implement PDF export functionality here
    alert('PDF export functionality is not implemented yet.');
  };

  const openEditModal = () => {
    const modalElement = document.getElementById('cashRequestModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    nextTick(() => {
      initializeSelect2();
    });
  };

  const saveCashRequest = async () => {
    try {
      const response = await axios.put(`/cash-request/${form.value.id}`, form.value);
      const updatedRequest = response.data;
      swal('Success!', 'Cash request updated successfully!', 'success', { timer: 2000 });
      const modalElement = document.getElementById('cashRequestModal');
      const modal = bootstrap.Modal.getInstance(modalElement);
      modal.hide();
      // Reload the page after saving
      window.location.reload();
    } catch (error) {
      if (error.response && error.response.status === 422) {
        validationErrors.value = error.response.data.errors;
      } else {
        swal('Error!', 'Failed to update cash request. Please try again.', 'error');
      }
    }
  };

  const initializeSelect2 = () => {
    $('#cashRequestModal .select2').select2({
      dropdownParent: $('#cashRequestModal')
    }).on('change', function () {
      const field = $(this).attr('id');
      form.value[field] = $(this).val();
      if (field === 'user_id') {
        const selectedUser = props.users.find(user => user.id === form.value.user_id);
        form.value.request_by = selectedUser ? selectedUser.name : '';
        user.value = selectedUser ? selectedUser : { name: 'Unknown' };
      }
    });
  };
  </script>

  <style scoped>
  @media print {
    .hidden-print {
      display: none !important;
    }

    .invoice-company {
      color: #333;
      font-size: 1.5rem;
      font-weight: bold;
    }

    .invoice-header {
      border-bottom: 2px solid #333;
      margin-bottom: 20px;
    }

    .invoice-header .invoice-from address {
      font-size: 1rem;
      color: #555;
    }

    .invoice-header .invoice-date {
      text-align: right;
    }

    .invoice-header .invoice-date .date {
      font-size: 1.2rem;
      font-weight: bold;
    }

    .invoice-header .invoice-date .invoice-detail {
      font-size: 1rem;
      color: #555;
    }

    .invoice-content {
      margin-bottom: 20px;
    }

    .invoice-content .table-invoice {
      width: 100%;
      border-collapse: collapse;
    }

    .invoice-content .table-invoice th,
    .invoice-content .table-invoice td {
      border: 1px solid #ddd;
      padding: 8px;
    }

    .invoice-content .table-invoice th {
      background-color: #f2f2f2;
      color: #333;
    }

    .invoice-price {
      border-top: 2px solid #333;
      padding-top: 20px;
      margin-top: 20px;
    }

    .invoice-price .invoice-price-left {
      float: left;
    }

    .invoice-price .invoice-price-right {
      float: right;
      font-size: 1.5rem;
      font-weight: bold;
    }

    .invoice-note {
      margin-top: 20px;
      font-size: 1rem;
      color: #555;
    }

    .invoice-footer {
      margin-top: 20px;
      text-align: center;
      font-size: 1rem;
      color: #555;
    }
  }
  </style>
