<script setup>
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import axios from 'axios';

// Define props
const props = defineProps({
  evaluation: {
    type: Object,
    required: true,
  },
  approvals: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Array,
    default: () => [],
  },
  users: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: () => null,
  },
});

// Format currency
const formatCurrency = (value) => {
  return value ? `$${parseFloat(value).toFixed(2)}` : '$0.00';
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return '';
  const options = { year: 'numeric', month: 'short', day: '2-digit' };
  return new Date(dateString).toLocaleDateString('en-US', options);
};

// Supplier details
const getSupplierName = (quotation) => {
  const supplier = props.suppliers.find((s) => s.id === quotation.supplier_id);
  return supplier ? supplier.name : '-';
};

const getSupplierAddress = (quotation) => {
  const supplier = props.suppliers.find((s) => s.id === quotation.supplier_id);
  return supplier ? supplier.address : '-';
};

const getSupplierPhone = (quotation) => {
  const supplier = props.suppliers.find((s) => s.id === quotation.supplier_id);
  return supplier ? supplier.phone : '-';
};

// Product details
const getProductCode = (productId) => {
  const product = props.products.find((p) => p.id === productId);
  return product ? product.code : '-';
};

const getProductName = (productId) => {
  const product = props.products.find((p) => p.id === productId);
  return product ? product.name : '-';
};

const getProductUom = (productId) => {
  const product = props.products.find((p) => p.id === productId);
  return product ? product.uom : '-';
};

// Quotation details
const getQuotationSpec = (quotation, productId) => {
  return quotation ? quotation.specifications[productId] || '' : '';
};

const getQuotationPrice = (quotation, productId) => {
  return quotation ? quotation.prices[productId] || 0 : 0;
};

const calculateTotalCost = (quotation, productId) => {
  if (!quotation) return 0;
  const price = quotation.prices[productId] || 0;
  const quantity = props.evaluation.quantities[productId] || 0;
  const discount = quotation.discounts[productId] || 0;
  return price * quantity - discount;
};

// Summary calculations
const computeSummary = (quotation) => {
  const selectedSupplier = props.suppliers.find(s => s.id === quotation.supplier_id);
  const vatRate = selectedSupplier ? Number(selectedSupplier.vat) / 100 : 0;

  let subtotal = 0;
  let discount = 0;

  props.evaluation.products.forEach(productId => {
    const qty = Number(props.evaluation.quantities[productId] || 0);
    const price = Number(quotation.prices[productId] || 0);
    const disc = Number(quotation.discounts[productId] || 0);

    subtotal += price * qty;
    discount += disc;
  });

  const vat = (subtotal - discount) * vatRate;
  const grandTotal = subtotal - discount + vat;

  return {
    subtotal: subtotal.toFixed(2),
    discount: discount.toFixed(2),
    vat: vat.toFixed(2),
    grandTotal: grandTotal.toFixed(2),
    vatRate: vatRate * 100,
  };
};

// Criteria
const getCriteria = (quotation, key) => {
  return quotation ? quotation.criteria[key] || '' : '';
};

// User details
const getUserName = (userId) => {
  const user = props.users.find((u) => u.id === userId);
  return user ? user.name : 'N/A';
};

// Signature
const getSignatureUrl = (signature) => {
  return signature ? `/storage/${signature}` : 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png';
};

// Roman numerals
const toRoman = (num) => {
  const romanMap = [
    { value: 1000, numeral: 'M' },
    { value: 900, numeral: 'CM' },
    { value: 500, numeral: 'D' },
    { value: 400, numeral: 'CD' },
    { value: 100, numeral: 'C' },
    { value: 90, numeral: 'XC' },
    { value: 50, numeral: 'L' },
    { value: 40, numeral: 'XL' },
    { value: 10, numeral: 'X' },
    { value: 9, numeral: 'IX' },
    { value: 5, numeral: 'V' },
    { value: 4, numeral: 'IV' },
    { value: 1, numeral: 'I' },
  ];
  let result = '';
  for (const { value, numeral } of romanMap) {
    while (num >= value) {
      result += numeral;
      num -= value;
    }
  }
  return result;
};

// Approval actions
const getStatusTypeString = (statusType) => {
  switch (statusType) {
    case 7:
      return 'Review';
    case 3:
      return 'Approve';
    case 2:
      return 'Acknowledge';
    default:
      return 'Unknown';
  }
};

const approveRequest = async (statusType) => {
  try {
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

    // Check if the user confirmed the action
    if (confirmResult !== true) return;

    // Send approval request
    await axios.post(`/evaluations/${props.evaluation.id}/approve`, {
      status_type: statusType,
    });

    // Show success message
    await swal({
      title: 'Success',
      text: `The Evaluation is successfully ${getStatusTypeString(statusType)}.`,
      icon: 'success',
      button: {
        text: 'OK',
        className: 'btn btn-primary',
      },
    });

    // Reload the page
    window.location.reload();
  } catch (error) {
    console.error('Approval Error:', error);

    // Show error message
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
  try {
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

    // Check if the user confirmed the action
    if (confirmResult !== true) return;

    // Send rejection request
    await axios.post(`/evaluations/${props.evaluation.id}/reject`, {
      status_type: statusType,
    });

    // Show success message
    await swal({
      title: 'Success',
      text: `Evaluation has been rejected for ${getStatusTypeString(statusType)} step.`,
      icon: 'success',
      button: {
        text: 'OK',
        className: 'btn btn-primary',
      },
    });

    // Reload the page
    window.location.reload();
  } catch (error) {
    console.error('Rejection Error:', error);

    // Show error message
    await swal({
      title: 'Error',
      text: `Failed to reject evaluation for ${getStatusTypeString(statusType)} step.`,
      icon: 'error',
      button: {
        text: 'OK',
        className: 'btn btn-danger',
      },
    });
  }
};

// Print functionality
const printForm = () => {
  const printableContent = document.getElementById('printable-area').innerHTML;
  const originalContent = document.body.innerHTML;

  document.body.innerHTML = printableContent;
  window.print();
  document.body.innerHTML = originalContent;
  window.location.reload();
};

// Back navigation
const goBack = () => {
  window.history.back();
};
</script>

<template>
  <Main>
    <Head title="Evaluation Form" />
    <div class="container a4-size">
      <div class="row">
        <div class="col text-end">
          <button class="btn btn-secondary" @click="goBack">Back</button>
          <button class="btn btn-primary" @click="printForm">Print</button>
        </div>
      </div>
      <div id="printable-area">
        <div class="row justify-content-center px-2">
          <!-- Header Section -->
          <div class="row mb-0">
            <div class="col-3">
              <a class="d-block text-start" href="#!">
                <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="MJQ Logo" width="135" height="44">
              </a>
            </div>
            <div class="col-6 pt-5">
              <div class="row font-monospace">
                <h5 class="text-uppercase text-center fw-bold" style="font-family: 'TW Cen MT';">Evaluation Form</h5>
              </div>
            </div>
            <div class="col-3">
              <div class="row font-monospace">
                <span class="text-sm-end" style="font-size: x-small;">Code: MJQE0055</span>
                <span class="text-sm-end" style="font-size: x-small;">Version: 4.1</span>
                <span class="text-sm-end" style="font-size: x-small;">Ref: {{ props.evaluation.reference }}</span>
              </div>
            </div>
          </div>
          <!-- Table Section -->
          <div class="row mb-2">
            <div class="table-responsive width-full p-0">
              <table class="table table-bordered border-dark table-sm">
                <thead style="font-size: 11px; font-family: 'TW Cen MT';">
                  <tr class="text-center">
                    <th rowspan="2" style="width: 3%;">No.</th>
                    <th rowspan="2" style="width: 8%;">Item Code</th>
                    <th rowspan="2" style="width: 15%;">Description</th>
                    <th rowspan="2" style="width: 5%;">Qty</th>
                    <th rowspan="2" style="width: 5%;">UoM</th>
                    <th v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'supplier-header-' + qIndex" colspan="4" class="bg-light">
                      <div class="fw-bold border-bottom pb-1">Quotation {{ toRoman(qIndex + 1) }}</div>
                      <div class="text-start small">Name: {{ getSupplierName(quotation) }}</div>
                      <div class="text-start small">Phone: {{ getSupplierPhone(quotation) }}</div>
                      <div class="text-start small">Address: {{ getSupplierAddress(quotation) }}</div>
                    </th>
                  </tr>
                  <tr class="text-center">
                    <template v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'subheader-' + qIndex">
                      <th style="width: 5.5%;">Brand/Spec</th>
                      <th style="width: 5.5%;">Price</th>
                      <th style="width: 5.5%;">Discount</th>
                      <th style="width: 5.5%;">Total</th>
                    </template>
                  </tr>
                </thead>
                <tbody class="table-group-divider" style="font-size: 10px;">
                  <tr v-for="(productId, pIndex) in props.evaluation.products" :key="'product-' + productId">
                    <td>{{ pIndex + 1 }}</td>
                    <td>{{ getProductCode(productId) }}</td>
                    <td>{{ getProductName(productId) }}</td>
                    <td>{{ props.evaluation.quantities[productId] || 0 }}</td>
                    <td>{{ getProductUom(productId) }}</td>
                    <template v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'quotation-' + qIndex + '-product-' + productId">
                      <td class="text-center">{{ getQuotationSpec(quotation, productId) }}</td>
                      <td class="text-center">{{ formatCurrency(getQuotationPrice(quotation, productId)) }}</td>
                      <td class="text-center">{{ formatCurrency(quotation.discounts[productId] || 0) }}</td>
                      <td class="text-center">{{ formatCurrency(calculateTotalCost(quotation, productId)) }}</td>
                    </template>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">Subtotal</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'subtotal-' + qIndex" colspan="4" class="text-right fw-bold">
                      {{ formatCurrency(computeSummary(quotation).subtotal) }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">Discount</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'discount-' + qIndex" colspan="4" class="text-right text-danger fw-bold">
                      -{{ formatCurrency(computeSummary(quotation).discount) }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">VAT</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'vat-' + qIndex" colspan="4" class="text-right fw-bold">
                      +{{ formatCurrency(computeSummary(quotation).vat) }}
                      <small class="text-muted">({{ props.suppliers.find(s => s.id === quotation.supplier_id)?.vat || 0 }}%)</small>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">Grand Total</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'grandTotal-' + qIndex" colspan="4" class="text-right fw-bold text-success">
                      {{ formatCurrency(computeSummary(quotation).grandTotal) }}
                    </td>
                  </tr>
                  <tr><td colspan="5" class="border-0"></td><td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'spacer-' + qIndex" colspan="4" class="border-0"></td></tr>
                  <tr>
                    <td colspan="5" class="fw-bold">1 - Price</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'price-' + qIndex" colspan="4" class="text-center">
                      {{ getCriteria(quotation, 'price') }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">2 - Quality</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'quality-' + qIndex" colspan="4" class="text-center">
                      {{ getCriteria(quotation, 'quality') }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">3 - Lead time on service/production/goods</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'lead_time-' + qIndex" colspan="4" class="text-center">
                      {{ getCriteria(quotation, 'lead_time') }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">4 - Warranty, (Services/Spare Parts/Goods)</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'warranty-' + qIndex" colspan="4" class="text-center">
                      {{ getCriteria(quotation, 'warranty') }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="5" class="fw-bold">5 - Term Payment/Deposit</td>
                    <td v-for="(quotation, qIndex) in props.evaluation.quotations" :key="'term_payment-' + qIndex" colspan="4" class="text-center">
                      {{ getCriteria(quotation, 'term_payment') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Recommendation -->
          <div class="row mb-2">
            <div class="table-responsive width-full p-0">
              <label class="form-label fw-bold">Recommendation</label>
              <p class="border p-2">{{ props.evaluation.recommendation || 'No recommendation provided' }}</p>
            </div>
          </div>
          <!-- Footer Section (Approvals) -->
          <div class="row mb-3" style="height: 150px;">
            <!-- Prepared By -->
            <div class="col-3 text-center px-2 mb-3">
              <div>រៀបចំដោយ</div>
              <div>Prepared By</div>
              <img
                :src="getSignatureUrl(props.users.find(u => u.id === props.evaluation.created_by)?.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ getUserName(props.evaluation.created_by) }}</div>
                <div>Position: {{ props.users.find(u => u.id === props.evaluation.created_by)?.position || 'N/A' }}</div>
                <div>Date: {{ formatDate(props.evaluation.created_at) }}</div>
              </div>
            </div>
            <!-- Acknowledged By -->
            <div class="col-3 text-center px-2 mb-3">
              <div>ទទួលស្គាល់ដោយ</div>
              <div>Acknowledged By</div>
              <img
                v-if="props.approvals.find(a => a.status_type === 2)?.status === 1"
                :src="getSignatureUrl(props.approvals.find(a => a.status_type === 2)?.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div v-else-if="props.approvals.find(a => a.status_type === 2)?.status === -1" class="text-danger mt-2">
                <i class="fas fa-times-circle fa-2x"></i>
                <div>Rejected</div>
              </div>
              <div v-if="props.approvals.find(a => a.status_type === 2)?.user_id === props.currentUser?.id && props.approvals.find(a => a.status_type === 2)?.status === 0" class="mt-2">
                <button class="btn btn-success btn-sm" @click="approveRequest(2)">
                  Sign
                </button>
                <button class="btn btn-danger btn-sm ms-2" @click="rejectRequest(2)">
                  Reject
                </button>
              </div>
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ props.approvals.find(a => a.status_type === 2)?.name || 'N/A' }}</div>
                <div>Position: {{ props.approvals.find(a => a.status_type === 2)?.position || 'N/A' }}</div>
                <div>Date: {{ props.approvals.find(a => a.status_type === 2)?.click_date ? formatDate(props.approvals.find(a => a.status_type === 2).click_date) : '' }}</div>
              </div>
            </div>
            <!-- Reviewed By -->
            <div class="col-3 text-center px-2 mb-3">
              <div>ពិនិត្យដោយ</div>
              <div>Reviewed By</div>
              <img
                v-if="props.approvals.find(a => a.status_type === 7)?.status === 1"
                :src="getSignatureUrl(props.approvals.find(a => a.status_type === 7)?.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div v-else-if="props.approvals.find(a => a.status_type === 7)?.status === -1" class="text-danger mt-2">
                <i class="fas fa-times-circle fa-2x"></i>
                <div>Rejected</div>
              </div>
              <div v-if="props.approvals.find(a => a.status_type === 7)?.user_id === props.currentUser?.id && props.approvals.find(a => a.status_type === 7)?.status === 0" class="mt-2">
                <button class="btn btn-success btn-sm" @click="approveRequest(7)">
                  Sign
                </button>
                <button class="btn btn-danger btn-sm ms-2" @click="rejectRequest(7)">
                  Reject
                </button>
              </div>
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ props.approvals.find(a => a.status_type === 7)?.name || 'N/A' }}</div>
                <div>Position: {{ props.approvals.find(a => a.status_type === 7)?.position || 'N/A' }}</div>
                <div>Date: {{ props.approvals.find(a => a.status_type === 7)?.click_date ? formatDate(props.approvals.find(a => a.status_type === 7).click_date) : '' }}</div>
              </div>
            </div>
            <!-- Approved By -->
            <div class="col-3 text-center px-2 mb-3">
              <div>អនុម័តដោយ</div>
              <div>Approved By</div>
              <img
                v-if="props.approvals.find(a => a.status_type === 3)?.status === 1"
                :src="getSignatureUrl(props.approvals.find(a => a.status_type === 3)?.signature)"
                alt="Signature"
                style="width: 130px; height: 80px; object-fit: contain;"
              />
              <div v-else-if="props.approvals.find(a => a.status_type === 3)?.status === -1" class="text-danger mt-2">
                <i class="fas fa-times-circle fa-2x"></i>
                <div>Rejected</div>
              </div>
              <div v-if="props.approvals.find(a => a.status_type === 3)?.user_id === props.currentUser?.id && props.approvals.find(a => a.status_type === 3)?.status === 0" class="mt-2">
                <button class="btn btn-success btn-sm" @click="approveRequest(3)">
                  Sign
                </button>
                <button class="btn btn-danger btn-sm ms-2" @click="rejectRequest(3)">
                  Reject
                </button>
              </div>
              <div class="border-top mt-2 pt-1 text-start">
                <div>Name: {{ props.approvals.find(a => a.status_type === 3)?.name || 'N/A' }}</div>
                <div>Position: {{ props.approvals.find(a => a.status_type === 3)?.position || 'N/A' }}</div>
                <div>Date: {{ props.approvals.find(a => a.status_type === 3)?.click_date ? formatDate(props.approvals.find(a => a.status_type === 3).click_date) : '' }}</div>
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
  width: 297mm; /* A4 landscape width */
  height: 210mm; /* A4 landscape height */
  margin: 10mm auto;
  padding: 10mm;
  background: white !important;
  color: black !important;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
  overflow: auto;
}

.a4-size * {
  color: black !important;
}

.table th,
.table td {
  border: 1px solid black;
}

@media print {
  .a4-size {
    width: 297mm;
    height: 210mm;
    margin: 10mm auto;
    padding: 20mm !important;
    box-shadow: none;
    background: white !important;
    color: black !important;
    overflow: visible;
  }

  #printable-area {
    padding: 20mm !important;
  }

  .btn {
    display: none !important;
  }

  .form-control[readonly],
  .form-control,
  select,
  textarea[readonly] {
    border: none;
    background: none;
    padding: 0;
    height: auto;
    line-height: normal;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
  }

  .input-group-text {
    border: none;
    background: none;
    padding-left: 0;
  }

  @page {
    size: A4 landscape;
    margin: 0;
  }
}
</style>