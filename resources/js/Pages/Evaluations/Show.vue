<template>
  <div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-3">
      <div class="col-md-5">
        <img src="/logo.png" alt="MJQ Logo" style="height: 50px;" /><br />
        <strong>MJQ</strong><br />
        Mengly J. Quach Education
      </div>
      <div class="col-md-4 text-center align-self-center">
        <h4 class="header-title">Price Evaluation Form</h4>
      </div>
      <div class="col-md-3 text-end">
        Code: MJQE0055<br />
        Version: 4.1
      </div>
    </div>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th rowspan="2">No.</th>
          <th rowspan="2">Item Code</th>
          <th rowspan="2">Item Description</th>
          <th rowspan="2">QTY</th>
          <th rowspan="2">UoM</th>
          <!-- Dynamic Quotation Headers -->
          <th
            v-for="(q, i) in quotations"
            :key="'header1-' + i"
            colspan="3"
            class="text-start"
          >
            <div><strong>Quotation {{ i + 1 }}</strong></div>
            <div style="font-weight: normal;">
              Name: {{ q.supplier_name || '__________________' }}<br />
              Add : {{ q.supplier_address || '__________________' }}<br />
              Tel : {{ q.supplier_tel || '__________________' }}
            </div>
          </th>
        </tr>
        <tr>
          <!-- Sub-headers for each quotation -->
          <th v-for="(q, i) in quotations" :key="'header2-' + i" colspan="3" class="text-start">
            <table style="width: 100%; border: none;">
              <tr>
                <th style="width:33%; border:none;">Brand</th>
                <th style="width:33%; border:none;">Unit Cost</th>
                <th style="width:33%; border:none;">Total Cost</th>
              </tr>
            </table>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in evaluation.items" :key="item.id || idx">
          <td>{{ idx + 1 }}</td>
          <td>{{ item.code || '' }}</td>
          <td class="text-start">{{ item.description || '' }}</td>
          <td>{{ item.qty || '' }}</td>
          <td>{{ item.uom || '' }}</td>

          <!-- For each quotation, show brand, unit cost, total cost -->
          <template v-for="(q, qIdx) in quotations" :key="'prod-' + idx + '-' + qIdx">
            <td>
              {{
                q.products.find(p => p.item_id === item.id)?.brand || ''
              }}
            </td>
            <td>
              {{
                q.products.find(p => p.item_id === item.id)?.unit_cost || ''
              }}
            </td>
            <td>
              {{
                (() => {
                  const product = q.products.find(p => p.item_id === item.id);
                  if (product) return (product.unit_cost * item.qty).toFixed(2);
                  return '';
                })()
              }}
            </td>
          </template>
        </tr>

        <!-- Totals Rows -->
        <tr>
          <td colspan="5">SubTotal:</td>
          <td
            v-for="(subtotal, i) in subTotals"
            :key="'subtotal-' + i"
            colspan="3"
          >
            {{ formatCurrency(subtotal) }}
          </td>
        </tr>
        <tr>
          <td colspan="5">Discount:</td>
          <td
            v-for="(discount, i) in discounts"
            :key="'discount-' + i"
            colspan="3"
          >
            {{ formatCurrency(discount) }}
          </td>
        </tr>
        <tr>
          <td colspan="5">VAT 10%:</td>
          <td
            v-for="(vat, i) in vats"
            :key="'vat-' + i"
            colspan="3"
          >
            {{ formatCurrency(vat) }}
          </td>
        </tr>
        <tr>
          <td colspan="5">Grand Total:</td>
          <td
            v-for="(grand, i) in grandTotals"
            :key="'grand-' + i"
            colspan="3"
          >
            {{ formatCurrency(grand) }}
          </td>
        </tr>

        <tr><td colspan="5" class="py-2"></td></tr>

        <!-- Criteria Scores Dynamically for all quotations -->
        <tr v-for="(criteria, idx) in criteriaKeys" :key="'criteria-row-' + idx">
          <td>{{ idx + 1 }}</td>
          <td colspan="4" class="text-start">{{ criteriaLabels[criteria] }}</td>
          <td
            v-for="(q, i) in quotations"
            :key="'criteria-score-' + idx + '-' + i"
            colspan="3"
          >
            {{ q.scores?.[criteria] ?? '' }}
          </td>
        </tr>

        <tr>
          <td colspan="5" class="text-start">
            Basis of supplier recommend: {{ evaluation.basis_of_supplier_recommend || '' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  evaluation: {
    type: Object,
    required: true
  },
  quotations: {
    type: Array,
    default: () => []
  }
})

function formatCurrency(val) {
  if (typeof val !== 'number') return ''
  return val.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

// Compute SubTotals dynamically
const subTotals = computed(() =>
  props.quotations.map((q) =>
    q.products.reduce((sum, p) => {
      const item = props.evaluation.items.find((it) => it.id === p.item_id)
      return sum + (p.unit_cost * (item?.qty || 0))
    }, 0)
  )
)

// For demo, discounts all zero (replace if you have real discount data)
const discounts = computed(() => props.quotations.map(() => 0))

const vats = computed(() =>
  subTotals.value.map((subtotal) => subtotal * 0.1)
)

const grandTotals = computed(() =>
  subTotals.value.map((subtotal, i) => subtotal - discounts.value[i] + vats.value[i])
)

// Criteria keys & labels to loop through
const criteriaKeys = ['price', 'quality', 'lead_time', 'warranty', 'term_payment']

const criteriaLabels = {
  price: 'Price',
  quality: 'Quality',
  lead_time: 'Lead time on service/production/goods',
  warranty: 'Warranty (Services/Spare Parts/Goods)',
  term_payment: 'Term Payment/Deposit'
}
</script>

<style scoped>
body {
  font-size: 13px;
}
table.table > thead > tr > th,
table.table > tbody > tr > td {
  vertical-align: middle;
}
</style>
