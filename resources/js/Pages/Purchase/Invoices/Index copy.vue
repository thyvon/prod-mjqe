<template>
  <Main>
    <Head :title="'Invoices'" />
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a href="#nav-create" id="nav-create-tab" data-bs-toggle="tab" class="nav-link active">Form</a>
      </li>
      <li class="nav-item">
        <a href="#nav-list" id="nav-list-tab" data-bs-toggle="tab" class="nav-link">Invoice List</a>
      </li>
    </ul>
    <div class="tab-content panel p-3 rounded-0 rounded-bottom">
      <div class="tab-pane fade active show" id="nav-create">
        <div class="panel-body">
          <form @submit.prevent="submitForm">
            <div class="row">
              <div class="col-md-6">
                <div class="row mb-1 align-items-center">
                  <label for="transaction_type" class="col-sm-4 col-form-label">Transaction Type</label>
                  <div class="col-sm-8">
                    <select v-model="form.transaction_type" class="form-select" id="transaction_type" required>
                      <option value="1">Petty Cash</option>
                      <option value="2">Credit</option>
                      <option value="3">Advance</option>
                    </select>
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="cash_ref" class="col-sm-4 col-form-label">Cash Reference</label>
                  <div class="col-sm-8">
                    <select v-model="form.cash_ref" class="form-select" id="cash_ref" required>
                      <option v-for="cashRequest in cashRequests" :key="cashRequest.id" :value="cashRequest.id">
                        {{ cashRequest.ref_no }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="payment_type" class="col-sm-4 col-form-label">Payment Type</label>
                  <div class="col-sm-8">
                    <select v-model="form.payment_type" class="form-select" id="payment_type" required>
                      <option value="1">Final</option>
                      <option value="2">Deposit</option>
                    </select>
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="invoice_date" class="col-sm-4 col-form-label">Invoice Date</label>
                  <div class="col-sm-8">
                    <input type="date" v-model="form.invoice_date" class="form-control" id="invoice_date" required />
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="invoice_no" class="col-sm-4 col-form-label">Invoice Number</label>
                  <div class="col-sm-8">
                    <input type="text" v-model="form.invoice_no" class="form-control" id="invoice_no" required />
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="supplier" class="col-sm-4 col-form-label">Supplier</label>
                  <div class="col-sm-8 d-flex">
                    <select v-model="form.supplier" class="form-select me-2" id="supplier" required style="width: 100%;">
                      <option value="" disabled>Select Supplier</option>
                      <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.name }}
                      </option>
                    </select>
                    <button type="button" class="btn btn-primary" @click="openCreateSupplierModal">New</button>
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="supplier_vat" class="col-sm-4 col-form-label">VAT(%)</label>
                  <div class="col-sm-8">
                    <input type="number" v-model="form.supplier_vat" class="form-control" id="supplier_vat" readonly />
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="row mb-1 align-items-center">
                  <label for="currency" class="col-sm-4 col-form-label">Currency</label>
                  <div class="col-sm-8">
                    <select v-model="form.currency" class="form-select" id="currency" required>
                      <option value="1">USD</option>
                      <option value="2">KHR</option>
                    </select>
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="currency_rate" class="col-sm-4 col-form-label">Currency Rate</label>
                  <div class="col-sm-8">
                    <input type="number" v-model="form.currency_rate" class="form-control" id="currency_rate" required />
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="payment_term" class="col-sm-4 col-form-label">Payment Term</label>
                  <div class="col-sm-8">
                    <input type="number" v-model="form.payment_term" class="form-control" id="payment_term" required />
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="total_amount" class="col-sm-4 col-form-label">Total Amount</label>
                  <div class="col-sm-8">
                    <input type="number" v-model="form.total_amount" class="form-control" id="total_amount" required />
                  </div>
                </div>
                <div class="row mb-1 align-items-center">
                  <label for="paid_amount" class="col-sm-4 col-form-label">Paid Amount</label>
                  <div class="col-sm-8">
                    <input type="number" v-model="form.paid_amount" class="form-control" id="paid_amount" required />
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="clearForm">Clear</button>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
          <div class="mt-4">
            <h5>Invoice Items</h5>
            <button type="button" class="btn btn-primary mb-2" @click="openPrItemsModal" :disabled="!form.supplier">Select PR Item</button>
            <button type="button" class="btn btn-secondary mb-2" @click="openPoItemsModal" :disabled="!form.supplier">Select PO Item</button>
            <div class="table-responsive">
              <table id="invoice-items-table" class="table table-bordered">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>PR Number</th>
                    <th>PO Number</th>
                    <th>Item Code</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 10%;">Remark</th>
                    <th>Qty</th>
                    <th>UOM</th>
                    <th>Price</th>
                    <th>Discount</th>
                    <th>VAT</th>
                    <th>Return</th>
                    <th>Retention</th>
                    <th>Due Amount</th>
                    <th>Paid Amount</th>
                    <th>Campus</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th style="width: 20%;">Purpose</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Remove v-for loop to avoid duplicate rendering -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="nav-list">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="invoice-list-table" class="table table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Invoice Number</th>
                  <th>Supplier</th>
                  <th>Invoice Date</th>
                  <th>Total Amount</th>
                  <th>Paid Amount</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(invoice, index) in purchaseInvoices" :key="invoice.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ invoice.invoice_no }}</td>
                  <td>{{ invoice.supplier.name }}</td>
                  <td>{{ invoice.invoice_date }}</td>
                  <td>{{ invoice.total_amount }}</td>
                  <td>{{ invoice.paid_amount }}</td>
                  <td>
                    <button class="btn btn-sm btn-primary" @click="editInvoice(invoice.id)">Edit</button>
                    <button class="btn btn-sm btn-danger" @click="deleteInvoice(invoice.id)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="prItemsModal" tabindex="-1" aria-labelledby="prItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="prItemsModalLabel">PR Items</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table id="pr-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
              <thead>
                <tr>
                  <th style="width: 5%;">#</th>
                  <th style="width: 10%;">Item Code</th>
                  <th style="width: 10%;">PR Number</th>
                  <th style="width: 30%;">Description</th>
                  <th style="width: 10%;">Qty</th>
                  <th style="width: 10%;">UOM</th>
                  <th style="width: 10%;">Unit Price</th>
                  <th style="width: 10%;">Total Price</th>
                  <th style="width: 5%;">Action</th>
                </tr>
              </thead>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="poItemsModal" tabindex="-1" aria-labelledby="poItemsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="poItemsModalLabel">PO Items</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <table id="po-items-table" class="table table-bordered align-middle text-nowrap" width="100%">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Item Code</th>
                  <th>PO Number</th>
                  <th>Description</th>
                  <th>Qty</th>
                  <th>UOM</th>
                  <th>Unit Price</th>
                  <th>Total Price</th>
                  <th>Action</th>
                </tr>
              </thead>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="editInvoiceItemModal" tabindex="-1" aria-labelledby="editInvoiceItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editInvoiceItemModalLabel">{{ editItemForm.po_item ? 'Edit PO Item' : 'Edit PR Item' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateInvoiceItem">
              <div class="row">
                <div class="col-md-6">
                  <div v-if="editItemForm.po_item" class="row mb-3 align-items-center">
                    <label for="editPoNumber" class="col-sm-4 col-form-label">PO Number</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.po_number" class="form-control" id="editPoNumber" readonly>
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editPrNumber" class="col-sm-4 col-form-label">PR Number</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.pr_number" class="form-control" id="editPrNumber" readonly>
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editDescription" class="col-sm-4 col-form-label">Description</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.description" class="form-control" id="editDescription" required></textarea>
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editRemark" class="col-sm-4 col-form-label">Remark</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.remark" class="form-control" id="editRemark"></textarea>
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editQty" class="col-sm-4 col-form-label">Quantity</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.qty" class="form-control" id="editQty" required step="0.01">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editUnitPrice" class="col-sm-4 col-form-label">Unit Price</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.unit_price" class="form-control" id="editUnitPrice" required step="0.01">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editDiscount" class="col-sm-4 col-form-label">Discount</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.discount" class="form-control" id="editDiscount" step="0.01">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editVat" class="col-sm-4 col-form-label">VAT</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.vat" class="form-control" id="editVat" step="0.01">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editReturn" class="col-sm-4 col-form-label">Return</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.return" class="form-control" id="editReturn" step="0.01">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editRetention" class="col-sm-4 col-form-label">Retention</label>
                    <div class="col-sm-8">
                      <input type="number" v-model="editItemForm.retention" class="form-control" id="editRetention" step="0.01">
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="row mb-3 align-items-center">
                    <label for="editCampus" class="col-sm-4 col-form-label">Campus</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.campus" class="form-control" id="editCampus">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editDivision" class="col-sm-4 col-form-label">Division</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.division" class="form-control" id="editDivision">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editDepartment" class="col-sm-4 col-form-label">Department</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.department" class="form-control" id="editDepartment">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editLocation" class="col-sm-4 col-form-label">Location</label>
                    <div class="col-sm-8">
                      <input type="text" v-model="editItemForm.location" class="form-control" id="editLocation">
                    </div>
                  </div>
                  <div class="row mb-3 align-items-center">
                    <label for="editPurpose" class="col-sm-4 col-form-label">Purpose</label>
                    <div class="col-sm-8">
                      <textarea v-model="editItemForm.purpose" class="form-control" id="editPurpose"></textarea>
                    </div>
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <SupplierFormModal @supplierUpdated="addSupplier" />
  </Main>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import SupplierFormModal from '@/Components/SupplierFormModal.vue';

toastr.options = {
  progressBar: true,
  closeButton: true,
  timeOut: 5000,
};

const props = defineProps({
  poItems: Array,
  purchaseRequests: Array,
  purchaseOrders: Array,
  suppliers: Array,
  users: Array,
  currentUser: Object,
  cashRequests: Array,
  prItems: Array, // Ensure prItems is defined in props
  purchaseInvoices: Array, // Ensure purchaseInvoices is defined in props
});

const form = reactive({
  transaction_type: null,
  cash_ref: null,
  payment_type: null,
  invoice_date: '',
  invoice_no: '',
  supplier: '',
  currency: null,
  currency_rate: null,
  payment_term: null,
  total_amount: null,
  paid_amount: null,
  supplier_vat: 0,
  created_by: null,
  items: [],
});

const prItemsTableInstance = ref(null);
const invoiceItemsTableInstance = ref(null);
const poItemsTableInstance = ref(null);
const editItemForm = reactive({
  id: null,
  description: '',
  qty: 0,
  unit_price: 0,
  discount: 0,
  vat: 0,
  return: 0,
  retention: 0,
  campus: '',
  division: '',
  department: '',
  location: '',
  purpose: '',
  remark: '',
  pr_number: '',
  po_number: '',
  item_code: '',
});

const selectedSupplierVat = computed(() => {
  return form.supplier_vat || 0;
});

const calculateGrandTotal = () => {
  const { qty, unit_price, discount, return: returnAmount, retention, vat } = editItemForm;
  editItemForm.grand_total = (qty * unit_price) - discount - returnAmount - retention + vat;
};

watch(
  () => [editItemForm.qty, editItemForm.unit_price, editItemForm.discount, editItemForm.return, editItemForm.retention, editItemForm.vat],
  calculateGrandTotal
);

const submitForm = async () => {
  try {
    if (!props.currentUser || !props.currentUser.id) {
      throw new Error('Current user is not defined');
    }
    form.created_by = props.currentUser.id;
    form.transaction_type = parseInt(form.transaction_type);
    form.payment_type = parseInt(form.payment_type);
    form.currency = parseInt(form.currency);
    form.supplier_vat = parseFloat(form.supplier_vat);
    form.items.forEach(item => {
      item.pi_number = null;
      item.total_usd = item.qty * item.unit_price;
      item.total_khr = item.total_usd * form.currency_rate;
      item.requested_by = form.created_by;
      item.invoice_date = form.invoice_date;
      item.payment_type = form.payment_type;
      item.invoice_no = form.invoice_no;
      item.purchased_by = form.created_by;

      if (props.prItems) {
        const prItem = props.prItems.find(pr => pr.id === item.pr_item);
        if (prItem) {
          item.pr_number = prItem.purchase_request_id;
          item.item_code = prItem.product_id;
        }
      }

      item.transaction_type = parseInt(item.transaction_type);
      item.payment_type = parseInt(item.payment_type);
      item.currency = parseInt(item.currency);
    });
    const response = await axios.post('/invoices', form);
    toastr.success('Invoice submitted successfully.');
    clearForm();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors;
      Object.keys(errors).forEach(key => {
        toastr.error(errors[key][0]);
      });
    } else {
      toastr.error('Failed to submit invoice.');
      // Add console logging for debugging
      console.error('Error:', error);
    }
  }
};

const clearForm = () => {
  Object.assign(form, {
    transaction_type: null,
    cash_ref: null,
    payment_type: null,
    invoice_date: '',
    invoice_no: '',
    supplier: null,
    currency: null,
    currency_rate: null,
    payment_term: null,
    total_amount: null,
    paid_amount: null,
    items: [],
    supplier_vat: 0,
  });
  invoiceItemsTableInstance.value.clear().draw();
  $('#supplier').val(null).trigger('change');
};

const editItem = (index) => {
  const item = form.items[index];
  editItemForm.index = index;
  Object.assign(editItemForm, item);
  const modalElement = document.getElementById('editInvoiceItemModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const updateInvoiceItem = () => {
  const index = editItemForm.index;
  if (index !== -1) {
    Object.assign(form.items[index], editItemForm);
    invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
    toastr.success('Invoice item updated successfully.');
    const modalElement = document.getElementById('editInvoiceItemModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
  } else {
    toastr.error('Failed to update invoice item.');
  }
};

const duplicateItem = (index) => {
  const itemToDuplicate = { ...form.items[index] };
  form.items.push(itemToDuplicate);
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
};

const removeItem = (index) => {
  form.items.splice(index, 1);
  invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();
};

const fetchPrItems = async (prNumber) => {
  try {
    const response = await axios.get(`/pr-items?pr_number=${prNumber}`);
    prItemsTableInstance.value.clear().rows.add(response.data).draw();
  } catch (error) {
    console.error('Error fetching PR items:', error);
  }
};

const fetchPoItems = async (poNumber) => {
  try {
    const response = await axios.get(`/po-items?po_number=${poNumber}`);
    poItemsTableInstance.value.clear().rows.add(response.data).draw();
  } catch (error) {
    console.error('Error fetching PO items:', error);
  }
};

const openPrItemsModal = () => {
  const modalElement = document.getElementById('prItemsModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    prItemsTableInstance.value.clear().draw();

    if (!document.getElementById('pr-number-filter')) {
      const prNumberSelect = $('<select id="pr-number-filter" class="form-select form-select-sm" style="width: 200px; margin-left: 10px;"><option value="">Select PR Number</option></select>')
        .appendTo('#pr-items-table_filter')
        .on('change', function () {
          const val = $(this).val();
          if (val) {
            fetchPrItems(val);
          } else {
            prItemsTableInstance.value.clear().draw();
          }
        });

      props.purchaseRequests.forEach(purchaseRequest => {
        if (purchaseRequest.pr_number) {
          prNumberSelect.append(`<option value="${purchaseRequest.pr_number}">${purchaseRequest.pr_number}</option>`);
        }
      });

      $('#pr-number-filter').select2({
        placeholder: 'Select PR Number',
        allowClear: true,
        width: 'resolve',
        dropdownParent: $('#prItemsModal')
      });

      $('#pr-items-table_filter').css('display', 'flex').css('justify-content', 'flex-end');
    }
  });

  modalElement.addEventListener('hidden.bs.modal', () => {
    $('#pr-number-filter').val('').trigger('change');
  });
};

const openPoItemsModal = () => {
  const modalElement = document.getElementById('poItemsModal');
  if (!modalElement) {
    console.error('PO Items Modal element not found');
    return;
  }
  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  modalElement.addEventListener('shown.bs.modal', () => {
    poItemsTableInstance.value.clear().draw();

    if (!document.getElementById('po-number-filter')) {
      const poNumberSelect = $('<select id="po-number-filter" class="form-select form-select-sm" style="width: 200px; margin-left: 10px;"><option value="">Select PO Number</option></select>')
        .appendTo('#po-items-table_filter')
        .on('change', function () {
          const val = $(this).val();
          if (val) {
            fetchPoItems(val);
          } else {
            poItemsTableInstance.value.clear().draw();
          }
        });

      props.purchaseOrders.forEach(purchaseOrder => {
        if (purchaseOrder.po_number) {
          poNumberSelect.append(`<option value="${purchaseOrder.po_number}">${purchaseOrder.po_number}</option>`);
        }
      });

      $('#po-number-filter').select2({
        placeholder: 'Select PO Number',
        allowClear: true,
        width: 'resolve',
        dropdownParent: $('#poItemsModal')
      });

      $('#po-items-table_filter').css('display', 'flex').css('justify-content', 'flex-end');
    }
  });

  modalElement.addEventListener('hidden.bs.modal', () => {
    $('#po-number-filter').val('').trigger('change');
  });
};

const calculateVat = (qty, unit_price, discount, vatPercentage) => {
  return ((qty * unit_price - discount) * vatPercentage) / 100;
};

const selectPrItem = (prItem) => {
  if (prItem.purchase_request) {
    const existingItem = form.items.find(item => item.pr_item === prItem.id);
    if (!existingItem) {
      const qty = parseFloat(prItem.qty) || 0;
      const unit_price = parseFloat(prItem.unit_price) || 0;
      const discount = 0;
      const vatPercentage = selectedSupplierVat.value;
      const vat = calculateVat(qty, unit_price, discount, vatPercentage);
      const total_price = Number(((qty * unit_price - discount) + vat).toFixed(2));
      form.items.push({
        pr_item: prItem.id,
        po_item: null,
        remark: prItem.remark,
        qty: qty,
        uom: prItem.uom,
        unit_price: unit_price,
        discount: discount,
        vat: vat,
        return: 0,
        retention: 0,
        due_amount: total_price,
        paid_amount: 0,
        campus: prItem.campus,
        division: prItem.division,
        department: prItem.department,
        purpose: prItem.purchase_request.purpose,
        location: '',
        description: `${prItem.product.product_description} | ${prItem.remark}`,
        pr_number: prItem.purchase_request.pr_number,
        po_number: '',
        item_code: prItem.product_id,
        requested_by: prItem.purchase_request.requested_by,
        cash_ref: form.cash_ref,
        transaction_type: form.transaction_type,
        payment_term: form.payment_term,
        purchased_by: form.created_by,
        currency_rate: form.currency_rate,
        currency: form.currency,
        invoice_date: form.invoice_date,
        payment_type: form.payment_type,
        invoice_no: form.invoice_no,
      });

      invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();

      const rowIndex = prItemsTableInstance.value.row((idx, data) => data.id === prItem.id).index();
      prItemsTableInstance.value.row(rowIndex).remove().draw();
    } else {
      toastr.warning('Item already exists in the invoice items table.');
    }
  } else {
    console.error('Purchase request not loaded for PR item:', prItem);
  }
};

const selectPoItem = (poItem) => {
  if (poItem.purchase_order) {
    const existingItem = form.items.find(item => item.po_item === poItem.id);
    if (!existingItem) {
      const qty = parseFloat(poItem.qty) || 0;
      const unit_price = parseFloat(poItem.unit_price) || 0;
      const discount = parseFloat(poItem.discount) || 0;
      const vatPercentage = selectedSupplierVat.value;
      const vat = calculateVat(qty, unit_price, discount, vatPercentage);
      const total_price = Number(((qty * unit_price - discount) + vat).toFixed(2));
      const prNumber = poItem.purchase_request.pr_number;
      form.items.push({
        pr_item: poItem.pr_item_id,
        po_item: poItem.id,
        remark: '',
        qty: qty,
        uom: poItem.uom,
        unit_price: unit_price,
        discount: discount,
        vat: vat,
        return: 0,
        retention: 0,
        due_amount: total_price,
        paid_amount: 0,
        campus: poItem.campus,
        division: poItem.division,
        department: poItem.department,
        purpose: poItem.purchase_order.purpose,
        location: poItem.location,
        description: `${poItem.product.product_description} | ${poItem.description}`,
        pr_number: prNumber,
        po_number: poItem.purchase_order.po_number,
        item_code: poItem.product_id,
        requested_by: poItem.purchase_request.requested_by,
        cash_ref: form.cash_ref,
        transaction_type: form.transaction_type,
        payment_term: form.payment_term,
        purchased_by: form.created_by,
        currency_rate: form.currency_rate,
        currency: form.currency,
        invoice_date: form.invoice_date,
        payment_type: form.payment_type,
        invoice_no: form.invoice_no,
      });

      invoiceItemsTableInstance.value.clear().rows.add(form.items).draw();

      const rowIndex = poItemsTableInstance.value.row((idx, data) => data.id === poItem.id).index();
      poItemsTableInstance.value.row(rowIndex).remove().draw();
    } else {
      toastr.warning('Item already exists in the invoice items table.');
    }
  } else {
    console.error('Purchase order not loaded for PO item:', poItem);
  }
};

const openCreateSupplierModal = () => {
  const modalElement = document.getElementById('supplierFormModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
};

const initializeSupplierSelect = () => {
  $('#supplier').select2({
    data: props.suppliers.map(supplier => ({ id: supplier.id, text: supplier.name })),
    dropdownParent: $('#supplier').parent(),
    placeholder: 'Select Supplier',
    allowClear: true,
    width: 'resolve',
    ajax: {
      url: '/search-suppliers',
      dataType: 'json',
      delay: 250,
      data: (params) => {
        return {
          q: params.term,
        };
      },
      processResults: (data) => {
        return {
          results: data.map(supplier => ({ id: supplier.id, text: supplier.name })),
        };
      },
      cache: true,
    },
  }).on('select2:select', function (e) {
    form.supplier = e.params.data.id;
    updateSupplierVat(e.params.data.id);
  }).on('select2:unselect', function () {
    form.supplier = '';
    form.supplier_vat = 0;
  });
};

const updateSupplierVat = async (supplierId) => {
  try {
    const response = await axios.get(`/supplier-vat/${supplierId}`);
    form.supplier_vat = response.data.vat;
  } catch (error) {
    console.error('Error fetching supplier VAT:', error);
  }
};

const addSupplier = (newSupplier) => {
  props.suppliers.push(newSupplier);
  form.supplier = newSupplier.id;
  updateSupplierVat(newSupplier.id);
};

watch(() => form.supplier, (newSupplierId) => {
  if (newSupplierId) {
    updateSupplierVat(newSupplierId);
  }
});

onMounted(() => {
  initializeSupplierSelect();
  watch(() => form.supplier, (newSupplierId) => {
    if (newSupplierId) {
      updateSupplierVat(newSupplierId);
    }
  });
  const prItemsTable = $('#pr-items-table');
  if (prItemsTable.length) {
    prItemsTableInstance.value = prItemsTable.DataTable({
      responsive: true,
      autoWidth: false,
      scrollX: false,
      data: [],
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'product.sku' },
        { data: 'purchase_request.pr_number' },
        { data: null, render: (data) => `${data.product?.product_description || ''} | ${data.remark || ''}`, className: 'wrap-cell' },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: null, render: (data) => (data.qty * data.unit_price).toFixed(2) },
        {
          data: null,
          render: (data, type, row, meta) => `
            <button type="button" class="btn btn-sm btn-primary" @click="selectPrItem(data)">
              <i class="fa fa-plus"></i> Select
            </button>
          `,
          className: 'text-center'
        },
      ],
    });

    $('#pr-items-table').on('click', '.btn-primary', function () {
      const rowData = prItemsTableInstance.value.row($(this).closest('tr')).data();
      selectPrItem(rowData);
    });

    $('#pr-items-search').on('keyup', function () {
      prItemsTableInstance.value.search(this.value).draw();
    });
  }

  const invoiceItemsTable = $('#invoice-items-table');
  if (invoiceItemsTable.length) {
    invoiceItemsTableInstance.value = invoiceItemsTable.DataTable({
      responsive: false,
      autoWidth: false,
      scrollX: false,
      data: form.items,
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'pr_number' },
        { data: 'po_number' },
        { data: 'item_code' },
        { data: 'description' },
        { data: 'remark', render: (data) => `<div class="wrap-cell">${data}</div>` },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: 'discount' },
        { data: 'vat' },
        { data: 'return' },
        { data: 'retention' },
        { data: 'due_amount' },
        { data: 'paid_amount' },
        { data: 'campus' },
        { data: 'division' },
        { data: 'department' },
        { data: 'location' },
        { data: 'purpose', render: (data) => `<div class="wrap-cell">${data}</div>` },
        {
          data: null,
          render: (data, type, row, meta) => `
            <div class="dropdown">
              <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton${meta.row}" data-bs-toggle="dropdown" aria-expanded="false">
                ...
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton${meta.row}">
                <li><a class="dropdown-item" href="#" @click="editItem(${meta.row})"><i class="fa fa-edit"></i> Edit</a></li>
                <li><a class="dropdown-item" href="#" @click="removeItem(${meta.row})"><i class="fa fa-trash"></i> Delete</a></li>
                <li><a class="dropdown-item" href="#" @click="duplicateItem(${meta.row})"><i class="fa fa-copy"></i> Duplicate</a></li>
              </ul>
            </div>
          `,
          className: 'text-center'
        },
      ],
    });

    $('#invoice-items-table').on('click', '.dropdown-item', function (e) {
      e.preventDefault();
      const action = $(this).text().trim();
      const rowIndex = invoiceItemsTableInstance.value.row($(this).closest('tr')).index();
      if (action.includes('Edit')) {
        editItem(rowIndex);
      } else if (action.includes('Delete')) {
        removeItem(rowIndex);
      } else if (action.includes('Duplicate')) {
        duplicateItem(rowIndex);
      }
    });
  }

  const poItemsTable = $('#po-items-table');
  if (poItemsTable.length) {
    poItemsTableInstance.value = poItemsTable.DataTable({
      responsive: true,
      autoWidth: false,
      scrollX: false,
      data: [],
      columns: [
        { data: null, render: (data, type, row, meta) => meta.row + 1 },
        { data: 'product.sku' },
        { data: 'purchase_order.po_number' },
        { data: null, render: (data) => `${data.product?.product_description || ''} | ${data.description || ''}`, className: 'wrap-cell' },
        { data: 'qty' },
        { data: 'uom' },
        { data: 'unit_price' },
        { data: null, render: (data) => (data.qty * data.unit_price).toFixed(2) },
        {
          data: null,
          render: (data, type, row, meta) => `
            <button type="button" class="btn btn-sm btn-primary" @click="selectPoItem(data)">
              <i class="fa fa-plus"></i> Select
            </button>
          `,
          className: 'text-center'
        },
      ],
    });

    $('#po-items-table').on('click', '.btn-primary', function () {
      const rowData = poItemsTableInstance.value.row($(this).closest('tr')).data();
      selectPoItem(rowData);
    });

    $('#po-items-search').on('keyup', function () {
      poItemsTableInstance.value.search(this.value).draw();
    });
  }
});
</script>

<style scoped>
/* Add any specific styles if needed */
</style>
