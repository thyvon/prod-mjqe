<template>
  <Main>
    <Head title="Create Evaluation" />
    <div class="panel panel-inverse">
      <div class="panel-heading d-flex justify-content-between align-items-center">
        <h4 class="panel-title">Create Evaluation</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>

      <div class="panel-body">
        <h1 class="text-center">Evaluation Form</h1>
        <form @submit.prevent="submit">
          <div class="form-group mb-4">
            <label class="font-weight-semibold" for="productSelect">Product</label>
            <select
              ref="productSelect"
              id="productSelect"
              class="form-control"
              :class="{ 'is-invalid': errors.products }"
            >
              <option value="">Select a product</option>
              <option
                v-for="product in products"
                :key="product.id"
                :value="product.id"
              >
                {{ product.sku }} - {{ product.product_description }}
              </option>
            </select>
            <div v-if="errors.products" class="invalid-feedback d-block">{{ errors.products }}</div>
          </div>

          <div class="mb-3 text-right">
            <button type="button" @click="addQuotation" class="btn btn-primary btn-sm">
              + Add Quotation
            </button>
          </div>

          <div style="overflow-x: auto;">
            <table class="table table-bordered mb-3">
              <thead>
                <tr>
                  <th style="width: 5%;">Item Code</th>
                  <th style="width: 15%;">Description</th>
                  <th style="width: 2%;">Qty</th>
                  <th style="width: 3%;">UoM</th>
                  <th
                    v-for="(quotation, qIndex) in form.quotations"
                    :key="'supplier-header-' + qIndex"
                    class="text-center"
                    style="min-width: 180px;"
                  >
                    <div class="font-weight-semibold">Quotation {{ toRoman(qIndex + 1) }}</div>
                    <select
                      v-model="quotation.supplier_id"
                      :ref="el => supplierSelects[qIndex] = el"
                      class="form-control supplier-select"
                      :class="{ 'is-invalid': errors[`quotations.${qIndex}.supplier_id`] }"
                    >
                      <option value="">Select supplier</option>
                      <option
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        :value="supplier.id"
                      >
                        {{ supplier.name }}
                      </option>
                    </select>
                    <div v-if="quotation.supplier_id" class="text-muted small mt-1">
                      <div>Phone: {{ suppliers.find(s => s.id === quotation.supplier_id)?.number || '-' }}</div>
                      <div>Address: {{ suppliers.find(s => s.id === quotation.supplier_id)?.address || '-' }}</div>
                    </div>
                    <button
                      v-if="form.quotations.length > 1"
                      type="button"
                      class="btn btn-link text-danger p-0 mt-1"
                      @click="removeQuotation(qIndex)"
                      title="Remove quotation"
                    >
                      Remove
                    </button>
                    <div v-if="errors[`quotations.${qIndex}.supplier_id`]" class="invalid-feedback d-block">
                      {{ errors[`quotations.${qIndex}.supplier_id`] }}
                    </div>
                  </th>
                </tr>
                <tr>
                  <th colspan="4"></th>
                  <th
                    v-for="(quotation, qIndex) in form.quotations"
                    :key="'subheader-' + qIndex"
                    class="text-center"
                  >
                    <div class="d-flex justify-content-between" style="gap: 8px;">
                      <div style="min-width: 60px;">Price</div>
                      <div style="min-width: 70px;">Discount</div>
                      <div style="min-width: 80px;">Total</div>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, pIndex) in selectedProducts" :key="'product-' + product.id">
                  <td>
                    {{ product.sku }}
                    <button
                      type="button"
                      class="btn btn-link text-danger p-0 ml-2"
                      @click="removeProduct(product.id)"
                      title="Remove product"
                    >
                      <i class="fa fa-times"></i>
                    </button>
                  </td>
                  <td>{{ product.product_description }}</td>
                  <td>
                    <input
                      v-model.number="form.quantities[product.id]"
                      type="number"
                      min="0"
                      class="form-control form-control-sm"
                      style="width: 80px"
                      placeholder="Qty"
                    />
                  </td>
                  <td>{{ product.uom ?? '-' }}</td>
                  <td
                    v-for="(quotation, qIndex) in form.quotations"
                    :key="`q-${qIndex}-p-${pIndex}`"
                    class="text-center"
                  >
                    <div class="d-flex justify-content-between" style="gap: 8px;">
                      <input
                        v-model.number="quotation.prices[product.id]"
                        type="number"
                        min="0"
                        class="form-control form-control-sm"
                        placeholder="Price"
                        style="min-width: 60px"
                      />
                      <input
                        v-model.number="quotation.discounts[product.id]"
                        type="number"
                        min="0"
                        class="form-control form-control-sm"
                        placeholder="Discount"
                        style="min-width: 70px"
                      />
                      <input
                        :value="computeTotalPrice(quotation, product.id)"
                        type="text"
                        readonly
                        class="form-control form-control-sm bg-light"
                        style="min-width: 80px"
                      />
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right fw-bold">Subtotal</td>
                  <td v-for="(summary, qIndex) in quotationSummaries" :key="'subtotal-' + qIndex" class="text-right fw-bold">
                    {{ summary.subtotal }}
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right fw-bold">Discount</td>
                  <td v-for="(summary, qIndex) in quotationSummaries" :key="'discount-' + qIndex" class="text-right text-danger fw-bold">
                    -{{ summary.discount }}
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right fw-bold">VAT</td>
                  <td
                    v-for="(quotation, qIndex) in form.quotations"
                    :key="'vat-' + qIndex"
                    class="text-right fw-bold"
                  >
                    <small class="text-muted">
                      ({{ (() => {
                        const supplier = suppliers.find(s => s.id === quotation.supplier_id);
                        return supplier ? supplier.vat + '%' : '-';
                      })() }})
                    </small>
                    <br />
                    +{{ quotationSummaries[qIndex]?.vat || '0.00' }}
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-right fw-bold">Grand Total</td>
                  <td v-for="(summary, qIndex) in quotationSummaries" :key="'grandTotal-' + qIndex" class="text-right fw-bold text-success">
                    {{ summary.grandTotal }}
                  </td>
                </tr>
                <tr>
                  <td colspan="4" class="text-center fw-bold">Criteria</td>
                  <td
                    v-for="(quotation, qIndex) in form.quotations"
                    :key="'criteria-' + qIndex"
                    class="text-center"
                  >
                    <div class="input-group mb-1">
                      <span class="input-group-text">1.</span>
                      <input v-model="quotation.criteria.price" class="form-control" placeholder="Price" />
                    </div>
                    <div class="input-group mb-1">
                      <span class="input-group-text">2.</span>
                      <input v-model="quotation.criteria.quality" class="form-control" placeholder="Quality" />
                    </div>
                    <div class="input-group mb-1">
                      <span class="input-group-text">3.</span>
                      <input v-model="quotation.criteria.lead_time" class="form-control" placeholder="Lead Time" />
                    </div>
                    <div class="input-group mb-1">
                      <span class="input-group-text">4.</span>
                      <input v-model="quotation.criteria.warranty" class="form-control" placeholder="Warranty" />
                    </div>
                    <div class="input-group">
                      <span class="input-group-text">5.</span>
                      <input v-model="quotation.criteria.term_payment" class="form-control" placeholder="Term Payment" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="form-group mb-4">
            <label for="recommendation" class="font-weight-semibold">Recommendation</label>
            <textarea
              v-model="form.recommendation"
              id="recommendation"
              class="form-control"
              :class="{ 'is-invalid': errors.recommendation }"
              placeholder="Enter your recommendation for the evaluation"
              rows="4"
            ></textarea>
            <div v-if="errors.recommendation" class="invalid-feedback">{{ errors.recommendation }}</div>
          </div>

          <div class="form-group mb-4">
            <h5 class="font-weight-semibold mb-3">Approval</h5>
            <div class="row">
              <div class="col-md-6">
                <label for="reviewedBy" class="font-weight-semibold">Reviewed By</label>
                <select
                  v-model="form.reviewed_by"
                  ref="reviewedBySelect"
                  id="reviewedBy"
                  class="form-control"
                  :class="{ 'is-invalid': errors.reviewed_by }"
                >
                  <option value="">Select reviewer</option>
                  <option
                    v-for="user in users"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User' }}
                  </option>
                </select>
                <div v-if="errors.reviewed_by" class="invalid-feedback d-block">{{ errors.reviewed_by }}</div>
              </div>
              <div class="col-md-6">
                <label for="approvedBy" class="font-weight-semibold">Approved By</label>
                <select
                  v-model="form.approved_by"
                  ref="approvedBySelect"
                  id="approvedBy"
                  class="form-control"
                  :class="{ 'is-invalid': errors.approved_by }"
                >
                  <option value="">Select approver</option>
                  <option
                    v-for="user in users"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User' }}
                  </option>
                </select>
                <div v-if="errors.approved_by" class="invalid-feedback d-block">{{ errors.approved_by }}</div>
              </div>
            </div>
          </div>

          <button
            type="submit"
            class="btn btn-success"
            :disabled="isSubmitting"
          >
            Save Evaluation
          </button>
        </form>
      </div>
    </div>
  </Main>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  suppliers: Array,
  products: Array,
  users: Array,
});

const errors = ref({});
const productSelect = ref(null);
const supplierSelects = ref([]);
const reviewedBySelect = ref(null);
const approvedBySelect = ref(null);
const isSubmitting = ref(false);

const form = ref({
  products: [],
  quantities: {},
  quotations: [
    {
      supplier_id: '',
      prices: {},
      discounts: {},
      criteria: {
        price: '',
        quality: '',
        lead_time: '',
        warranty: '',
        term_payment: '',
      },
    },
  ],
  recommendation: '',
  reviewed_by: '',
  approved_by: '',
});

const selectedProducts = computed(() =>
  form.value.products.map(id => props.products.find(p => p.id === id)).filter(Boolean)
);

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

watch(
  () => form.value.products,
  (newProducts) => {
    newProducts.forEach(id => {
      if (!(id in form.value.quantities)) form.value.quantities[id] = 0;
    });
    Object.keys(form.value.quantities).forEach(id => {
      if (!newProducts.includes(Number(id))) delete form.value.quantities[id];
    });
    form.value.quotations.forEach(quotation => {
      Object.keys(quotation.prices).forEach(pid => {
        if (!newProducts.includes(Number(pid))) {
          delete quotation.prices[pid];
          delete quotation.discounts[pid];
        }
      });
    });
  },
  { immediate: true }
);

const addQuotation = async () => {
  form.value.quotations.push({
    supplier_id: '',
    prices: {},
    discounts: {},
    criteria: {
      price: '',
      quality: '',
      lead_time: '',
      warranty: '',
      term_payment: '',
    },
  });
  await nextTick();
  initializeSupplierSelect(form.value.quotations.length - 1);
};

const removeQuotation = (index) => {
  if (form.value.quotations.length > 1) {
    if (supplierSelects.value[index]) {
      $(supplierSelects.value[index]).select2('destroy');
    }
    form.value.quotations.splice(index, 1);
    supplierSelects.value.splice(index, 1);
  }
};

const removeProduct = (productId) => {
  form.value.products = form.value.products.filter(id => id !== productId);
  $(productSelect.value).val('').trigger('change');
};

const computeTotalPrice = (quotation, productId) => {
  const price = Number(quotation.prices[productId] || 0);
  const qty = Number(form.value.quantities[productId] || 0);
  const discount = Number(quotation.discounts[productId] || 0);
  return Math.max(price * qty - discount, 0).toFixed(2);
};

const quotationSummaries = computed(() => {
  return form.value.quotations.map((quotation) => {
    const selectedSupplier = props.suppliers.find(s => s.id === quotation.supplier_id);
    const vatRate = selectedSupplier ? Number(selectedSupplier.vat) / 100 : 0;

    let subtotal = 0;
    let discount = 0;

    selectedProducts.value.forEach(product => {
      const pid = product.id;
      const qty = Number(form.value.quantities[pid] || 0);
      const price = Number(quotation.prices[pid] || 0);
      const disc = Number(quotation.discounts[pid] || 0);

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
    };
  });
});

onMounted(() => {
  if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
    console.warn('jQuery or Select2 is not loaded. Please ensure both are included in your project.');
    return;
  }

  const $productSelect = $(productSelect.value).select2({
    placeholder: 'Search and select product',
    width: '100%',
    allowClear: true,
    matcher: (params, data) => {
      if (!params.term) return data;
      const product = props.products.find(p => p.id === parseInt(data.id));
      if (!product) return null;
      const term = params.term.toLowerCase();
      return product.sku.toLowerCase().includes(term) || product.product_description.toLowerCase().includes(term) ? data : null;
    },
    templateResult: (data) => {
      if (!data.id) return data.text;
      const product = props.products.find(p => p.id === parseInt(data.id));
      return product ? `${product.sku} - ${product.product_description}` : data.text;
    },
    templateSelection: (data) => {
      if (!data.id) return data.text;
      const product = props.products.find(p => p.id === parseInt(data.id));
      return product ? `${product.sku} - ${product.product_description}` : data.text;
    },
  });

  $productSelect.on('select2:select', function (e) {
    const selectedId = parseInt(e.params.data.id);
    if (selectedId && !form.value.products.includes(selectedId)) {
      form.value.products.push(selectedId);
      $(this).val('').trigger('change');
    }
  });

  $productSelect.on('select2:unselect', function () {
    $(this).val('').trigger('change');
  });

  form.value.quotations.forEach((_, qIndex) => {
    initializeSupplierSelect(qIndex);
  });

  const $reviewedBySelect = $(reviewedBySelect.value).select2({
    placeholder: 'Select reviewer',
    width: '100%',
    allowClear: true,
    matcher: (params, data) => {
      if (!params.term) return data;
      const user = props.users.find(u => u.id === parseInt(data.id));
      if (!user) return null;
      const term = params.term.toLowerCase();
      return user.name.toLowerCase().includes(term) || (user.card_id && user.position && user.card_id.toLowerCase().includes(term)) ? data : null;
    },
    templateResult: (data) => {
      if (!data.id) return data.text;
      const user = props.users.find(u => u.id === parseInt(data.id));
      return user ? `${user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User'}` : data.text;
    },
    templateSelection: (data) => {
      if (!data.id) return data.text;
      const user = props.users.find(u => u.id === parseInt(data.id));
      return user ? `${user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User'}` : data.text;
    },
  });

  $reviewedBySelect.val(form.value.reviewed_by || '').trigger('change');

  $reviewedBySelect.on('select2:select select2:unselect', () => {
    const selectedId = $reviewedBySelect.val() ? parseInt($reviewedBySelect.val()) : '';
    form.value.reviewed_by = selectedId;
  });

  const $approvedBySelect = $(approvedBySelect.value).select2({
    placeholder: 'Select approver',
    width: '100%',
    allowClear: true,
    matcher: (params, data) => {
      if (!params.term) return data;
      const user = props.users.find(u => u.id === parseInt(data.id));
      if (!user) return null;
      const term = params.term.toLowerCase();
      return user.name.toLowerCase().includes(term) || (user.card_id && user.position && user.card_id.toLowerCase().includes(term)) ? data : null;
    },
    templateResult: (data) => {
      if (!data.id) return data.text;
      const user = props.users.find(u => u.id === parseInt(data.id));
      return user ? `${user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User'}` : data.text;
    },
    templateSelection: (data) => {
      if (!data.id) return data.text;
      const user = props.users.find(u => u.id === parseInt(data.id));
      return user ? `${user.card_id && user.name && user.position ? `${user.card_id} - ${user.name} | ${user.position}` : user.name || 'Unknown User'}` : data.text;
    },
  });

  $approvedBySelect.val(form.value.approved_by || '').trigger('change');

  $approvedBySelect.on('select2:select select2:unselect', () => {
    const selectedId = $approvedBySelect.val() ? parseInt($approvedBySelect.val()) : '';
    form.value.approved_by = selectedId;
  });
});

const initializeSupplierSelect = (qIndex) => {
  const $supplierSelect = $(supplierSelects.value[qIndex]);
  if (!$supplierSelect.length) {
    console.warn(`Supplier select element at index ${qIndex} not found.`);
    return;
  }

  $supplierSelect.select2({
    placeholder: 'Select supplier',
    width: '100%',
    allowClear: true,
    templateResult: (data) => {
      if (!data.id) return data.text;
      const supplier = props.suppliers.find(s => s.id === parseInt(data.id));
      return supplier ? `${supplier.name}` : data.text;
    },
    templateSelection: (data) => {
      if (!data.id) return data.text;
      const supplier = props.suppliers.find(s => s.id === parseInt(data.id));
      return supplier ? `${supplier.name}` : data.text;
    },
  });

  $supplierSelect.val(form.value.quotations[qIndex].supplier_id || '').trigger('change');

  $supplierSelect.off('select2:select select2:unselect').on('select2:select select2:unselect', () => {
    const selectedId = $supplierSelect.val() ? parseInt($supplierSelect.val()) : '';
    form.value.quotations[qIndex].supplier_id = selectedId;
  });
};

const submit = async () => {
  console.log('Submitting form:', form.value);
  isSubmitting.value = true;
  errors.value = {};

  try {
    const response = await axios.post('/evaluations', form.value);
    console.log('Form submitted successfully:', response.data);

    supplierSelects.value.forEach((el, index) => {
      if (el) $(el).select2('destroy');
    });

    if (reviewedBySelect.value) $(reviewedBySelect.value).select2('destroy');
    if (approvedBySelect.value) $(approvedBySelect.value).select2('destroy');

    form.value = {
      products: [],
      quantities: {},
      quotations: [
        {
          supplier_id: '',
          prices: {},
          discounts: {},
          criteria: {
            price: '',
            quality: '',
            lead_time: '',
            warranty: '',
            term_payment: '',
          },
        },
      ],
      recommendation: '',
      reviewed_by: '',
      approved_by: '',
    };
    supplierSelects.value = [];
    $(productSelect.value).val('').trigger('change');

    await nextTick();
    initializeSupplierSelect(0);
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    } else {
      console.error('Submission error:', error);
      errors.value = { general: 'An error occurred while submitting the form.' };
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>