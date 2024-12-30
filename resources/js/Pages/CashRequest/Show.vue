<template>
    <Main>
      <Head :title="'View Cash Request'" />

      <div class="invoice">
        <!-- Invoice Company -->
        <div class="invoice-company">
          <span class="float-end hidden-print">
            <a href="javascript:;" class="btn btn-sm btn-white mb-10px">
              <i class="fa fa-file-pdf t-plus-1 text-danger fa-fw fa-lg"></i> Export as PDF
            </a>
            <a href="javascript:;" onclick="if (!window.__cfRLUnblockHandlers) return false; window.print()" class="btn btn-sm btn-white mb-10px">
              <i class="fa fa-print t-plus-1 fa-fw fa-lg"></i> Print
            </a>
          </span>
            {{ form.request_type }} Request
        </div>
        <!-- Invoice Header -->
        <div class="invoice-header">
          <div class="invoice-from">
            <address class="mt-5px mb-5px">
              <strong class="text-dark">Requester: {{ user.name }}</strong><br />
              Position: {{ form.position }}<br />
              Campus: {{ form.campus }}<br />
              Division: {{ form.division }}<br />
              Department: {{ form.department }}<br />
            </address>
          </div>

          <!-- <div class="invoice-to">
            <address class="mt-5px mb-5px">
              <strong class="text-dark">Company Name</strong><br />
              Street Address<br />
              City, Zip Code<br />
              Phone: (123) 456-7890<br />
              Fax: (123) 456-7890
            </address>
          </div> -->

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
                  <!-- <th class="text-end" width="20%">TOTAL AMOUNT {{ form.currency}}</th> -->
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    {{ form.description }}
                  </td>
                  <td class="text-center">{{ form.currency }}</td>
                  <td class="text-center">{{ form.exchange_rate}}</td>
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
            <div class="invoice-price-right">
              <small>TOTAL</small> <span class="fw-bold">{{ form.amount}} {{ form.currency }}</span>
            </div>
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
    </Main>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';
  import { usePage } from '@inertiajs/vue3';

  // Props
  const props = defineProps({
    cashRequest: Object,
  });

  // Reactive state for form data and user
  const form = ref({
    request_type: props.cashRequest.request_type || '',
    request_date: props.cashRequest.request_date || '',
    user_id: props.cashRequest.user_id || '',
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

  const user = ref({ name: '' });

  // Fetch user details based on user_id
  onMounted(() => {
    const userId = form.value.user_id;
    if (userId) {
      // Fetch user data from your backend or page props
      const { users } = usePage().props;
      user.value = users.find(u => u.id === userId) || { name: 'Unknown' };
    }
  });

  const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', options);
};

  // Handlers
  const goBack = () => {
    window.history.back();
  };
  </script>
