<template>
  <Main>
    <Head title="Edit Evaluation" />

    <div class="container mx-auto p-4 max-w-4xl">
      <h1 class="text-2xl font-bold mb-6">Edit Evaluation</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Title -->
        <div>
          <label for="title" class="block font-medium mb-1">Title</label>
          <input
            v-model="form.title"
            type="text"
            id="title"
            class="w-full border border-gray-300 rounded px-3 py-2"
            :class="{ 'border-red-500': form.errors.title }"
          />
          <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">
            {{ form.errors.title }}
          </p>
        </div>

        <!-- Quotations -->
        <div>
          <h2 class="text-lg font-semibold mb-2">Quotations</h2>
          <div
            v-for="(quotation, index) in form.quotations"
            :key="index"
            class="mb-6 p-4 border rounded"
          >
            <div class="flex justify-between items-center mb-3">
              <h3 class="font-semibold">Quotation {{ index + 1 }}</h3>
              <button
                type="button"
                @click="removeQuotation(index)"
                class="text-red-600 hover:underline"
                v-if="form.quotations.length > 1"
              >
                Remove
              </button>
            </div>

            <!-- Supplier Select -->
            <div class="mb-3">
              <label class="block font-medium mb-1">Supplier</label>
              <select
                v-model="quotation.supplier_id"
                class="w-full border border-gray-300 rounded px-3 py-2"
                :class="{ 'border-red-500': form.errors[`quotations.${index}.supplier_id`] }"
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
              <p v-if="form.errors[`quotations.${index}.supplier_id`]" class="text-red-600 text-sm mt-1">
                {{ form.errors[`quotations.${index}.supplier_id`] }}
              </p>
            </div>

            <!-- Products (multi-select) -->
            <div class="mb-3">
              <label class="block font-medium mb-1">Products</label>
              <select
                v-model="quotation.products"
                multiple
                class="w-full border border-gray-300 rounded px-3 py-2 h-24"
                :class="{ 'border-red-500': form.errors[`quotations.${index}.products`] }"
              >
                <option
                  v-for="product in products"
                  :key="product.id"
                  :value="product.id"
                >
                  {{ product.name }}
                </option>
              </select>
              <p v-if="form.errors[`quotations.${index}.products`]" class="text-red-600 text-sm mt-1">
                {{ form.errors[`quotations.${index}.products`] }}
              </p>
            </div>

            <!-- Criteria inputs -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-medium mb-1">Price</label>
                <input
                  v-model="quotation.price"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2"
                  :class="{ 'border-red-500': form.errors[`quotations.${index}.price`] }"
                />
                <p v-if="form.errors[`quotations.${index}.price`]" class="text-red-600 text-sm mt-1">
                  {{ form.errors[`quotations.${index}.price`] }}
                </p>
              </div>

              <div>
                <label class="block font-medium mb-1">Quality</label>
                <input
                  v-model="quotation.quality"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2"
                  :class="{ 'border-red-500': form.errors[`quotations.${index}.quality`] }"
                />
                <p v-if="form.errors[`quotations.${index}.quality`]" class="text-red-600 text-sm mt-1">
                  {{ form.errors[`quotations.${index}.quality`] }}
                </p>
              </div>

              <div>
                <label class="block font-medium mb-1">Lead Time</label>
                <input
                  v-model="quotation.lead_time"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2"
                  :class="{ 'border-red-500': form.errors[`quotations.${index}.lead_time`] }"
                />
                <p v-if="form.errors[`quotations.${index}.lead_time`]" class="text-red-600 text-sm mt-1">
                  {{ form.errors[`quotations.${index}.lead_time`] }}
                </p>
              </div>

              <div>
                <label class="block font-medium mb-1">Warranty</label>
                <input
                  v-model="quotation.warranty"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2"
                  :class="{ 'border-red-500': form.errors[`quotations.${index}.warranty`] }"
                />
                <p v-if="form.errors[`quotations.${index}.warranty`]" class="text-red-600 text-sm mt-1">
                  {{ form.errors[`quotations.${index}.warranty`] }}
                </p>
              </div>

              <div>
                <label class="block font-medium mb-1">Term Payment</label>
                <input
                  v-model="quotation.term_payment"
                  type="text"
                  class="w-full border border-gray-300 rounded px-3 py-2"
                  :class="{ 'border-red-500': form.errors[`quotations.${index}.term_payment`] }"
                />
                <p v-if="form.errors[`quotations.${index}.term_payment`]" class="text-red-600 text-sm mt-1">
                  {{ form.errors[`quotations.${index}.term_payment`] }}
                </p>
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="addQuotation"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
          >
            + Add Quotation
          </button>
        </div>

        <div class="pt-6">
          <button
            type="submit"
            class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            :disabled="form.processing"
          >
            Save Changes
          </button>
          <inertia-link
            href="/evaluations"
            class="ml-4 text-gray-600 hover:underline"
          >
            Cancel
          </inertia-link>
        </div>
      </form>
    </div>
  </Main>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const props = defineProps({
  evaluation: Object,
  suppliers: Array,
  products: Array,
});

const form = useForm({
  title: props.evaluation.title || '',
  quotations: props.evaluation.quotations.map(q => ({
    id: q.id, // needed for update
    supplier_id: q.supplier_id || '',
    products: q.products.map(p => p.id) || [],
    price: q.price || '',
    quality: q.quality || '',
    lead_time: q.lead_time || '',
    warranty: q.warranty || '',
    term_payment: q.term_payment || '',
  })) || [],
});

function addQuotation() {
  form.quotations.push({
    supplier_id: '',
    products: [],
    price: '',
    quality: '',
    lead_time: '',
    warranty: '',
    term_payment: '',
  });
}

function removeQuotation(index) {
  form.quotations.splice(index, 1);
}

function submit() {
  form.put(`/evaluations/${props.evaluation.id}`, {
    preserveScroll: true,
  });
}
</script>
