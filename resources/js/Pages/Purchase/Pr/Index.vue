<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

import cancellationModal from '@/Pages/Cancellations/CancellationModal.vue';

const props = defineProps({
  purchaseRequests: { type: Array, required: true },
  users: Array,
  products: Array,
  currentUser: Object,
  purchaseRequest: Object,
});

const isEdit = ref(false);
const purchaseRequestForm = reactive({
  id: null,
  pr_number: '',
  request_date: '',
  user_id: null,
  request_by: '',
  campus: '',
  division: '',
  department: '',
  purpose: '',
  is_urgent: 0,
  items: []
});

const itemForm = reactive({ product_id: '', remark: '', qty: 1, uom: '', unit_price: 0, total_price: 0, campus: '', division: '', department: '' });
const validationErrors = ref({});
let dataTableInstance;
let editingItemIndex = ref(null);

const totalAmount = computed(() => {
  return purchaseRequestForm.items.reduce((sum, item) => sum + Number(item.total_price), 0).toFixed(2);
});

const itemCount = computed(() => purchaseRequestForm.items.length);

watch(() => [itemForm.qty, itemForm.unit_price], () => {
  itemForm.total_price = (itemForm.qty * itemForm.unit_price).toFixed(2);
});

watch(() => itemForm.product_id, (newProductId) => {
  const selectedProduct = props.products.find(product => product.id == newProductId);
  if (selectedProduct) {
    Object.assign(itemForm, {
      uom: selectedProduct.uom,
      product_description: selectedProduct.product_description,
      sku: selectedProduct.sku
    });
  } else {
    Object.assign(itemForm, { uom: '', product_description: '', sku: '' });
  }
});

const openCancellationModal = (purchaseRequest) => {
  const modalElement = document.getElementById('cancellationModal');
  if (modalElement) {
    const modalInstance = new bootstrap.Modal(modalElement);

    // Emit an event with the PR ID to open the create modal
    window.dispatchEvent(new CustomEvent('open-create-modal', { detail: { prId: purchaseRequest.id, docs: "PR"} }));

    modalInstance.show(); // Show the modal
  }
};

const openCreatePage = () => {
  isEdit.value = false;
  Object.assign(purchaseRequestForm, {
    id: null, pr_number: '', request_date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', request_by: props.currentUser?.name || '',
    campus: '', division: '', department: '', purpose: '', is_urgent: false, items: [], total_amount: 0,
  });
};

const openEditPage = async (purchaseRequest) => {
  try {
    const response = await axios.get(`/purchase-requests/${purchaseRequest.id}/edit`);
    isEdit.value = true;
    const items = response.data.pr_items?.map(item => ({
      id: item.id, product_id: item.product_id, remark: item.remark, qty: item.qty, uom: item.uom,
      unit_price: item.unit_price, total_price: item.total_price, campus: item.campus, division: item.division,
      department: item.department, sku: item.product.sku, product_description: item.product.product_description,
      qty_last: item.qty_last, is_cancel: item.is_cancel
    })) || [];
    Object.assign(purchaseRequestForm, {
      id: response.data.id, pr_number: response.data.pr_number,
      request_date: response.data.request_date?.split('T')[0] || '',
      user_id: response.data.user_id, request_by: response.data.request_by?.name || '',
      campus: response.data.campus, division: response.data.division, department: response.data.department,
      purpose: response.data.purpose, is_urgent: response.data.is_urgent ? 1 : 0, items: items,
    });
    nextTick(() => {
      $('#campus').val(purchaseRequestForm.campus).trigger('change');
      $('#division').val(purchaseRequestForm.division).trigger('change');
      $('#department').val(purchaseRequestForm.department).trigger('change');
    });
  } catch (error) {
    console.error('Error fetching edit data:', error);
  }
};

if (props.purchaseRequest) openEditPage(props.purchaseRequest);

const savePurchaseRequest = async () => {
  try {
    for (const item of purchaseRequestForm.items) {
      if (!item.uom) {
        validationErrors.value = { items: [{ uom: ['UOM is required for all items.'] }] };
        return;
      }
    }
    const payload = {
      ...purchaseRequestForm,
      request_by: props.currentUser?.id || purchaseRequestForm.request_by,
      total_amount: totalAmount.value,
      total_item: itemCount.value,
      is_urgent: purchaseRequestForm.is_urgent ? 1 : 0,
    };
    if (isEdit.value) {
      const response = await axios.put(`/purchase-requests/${purchaseRequestForm.id}`, payload);
      const updatedRequest = response.data;
      const rowIndex = dataTableInstance.row((idx, data) => data.id === updatedRequest.id).index();
      dataTableInstance.row(rowIndex).data(updatedRequest).draw(false);
      swal('Success!', 'Purchase request updated successfully!', 'success', { timer: 2000 });
    } else {
      const response = await axios.post('/purchase-requests', payload);
      dataTableInstance.row.add(response.data).draw(false);
      swal('Success!', 'Purchase request created successfully!', 'success', { timer: 2000 });
    }
    clearForm();
    $('#nav-index-tab').tab('show');
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors;
    } else {
      swal('Error!', 'Failed to save purchase request. Please try again.', 'error');
    }
  }
};

const deletePurchaseRequest = async (purchaseRequestId) => {
  swal({
    title: 'Are you sure?', text: 'You will not be able to recover this purchase request!', icon: 'warning',
    buttons: { cancel: { text: 'No, cancel!', visible: true, className: 'btn btn-secondary', closeModal: true },
               confirm: { text: 'Yes, delete it!', visible: true, className: 'btn btn-danger', closeModal: true } },
    dangerMode: true,
  }).then(async (result) => {
    if (result) {
      try {
        await axios.delete(`/purchase-requests/${purchaseRequestId}`);
        dataTableInstance.row((idx, data) => data.id === purchaseRequestId).remove().draw();
        swal('Deleted!', 'Purchase request has been deleted.', 'success', { timer: 2000 });
      } catch (error) {
        swal('Error!', 'Failed to delete purchase request. Please try again.', 'error');
      }
    }
  });
};


const clearForm = () => {
  Object.assign(purchaseRequestForm, {
    id: null, pr_number: '', request_date: new Date().toISOString().split('T')[0],
    user_id: props.currentUser?.id || '', request_by: props.currentUser?.name || '',
    campus: '', division: '', department: '', purpose: '', is_urgent: false, items: [], total_amount: 0,
  });
  validationErrors.value = {};
};

const openItemModal = () => {
  Object.assign(itemForm, { product_id: '', remark: '', qty: 1, uom: '', unit_price: 0, total_price: 0, campus: purchaseRequestForm.campus, division: purchaseRequestForm.division, department: purchaseRequestForm.department });
  editingItemIndex.value = null;
  const modalElement = document.getElementById('itemModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    $('#product_id').select2({
      placeholder: 'Select a product',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#itemModal')
    }).on('change', function () {
      itemForm.product_id = $(this).val();
      const selectedProduct = props.products.find(product => product.id == itemForm.product_id);
      if (selectedProduct) {
        itemForm.uom = selectedProduct.uom;
        itemForm.product_description = selectedProduct.product_description;
        itemForm.sku = selectedProduct.sku;
      } else {
        itemForm.uom = '';
        itemForm.product_description = '';
        itemForm.sku = '';
      }
    });
  });
};

const addItem = () => {
  const item = { ...itemForm, total_price: itemForm.qty * itemForm.unit_price };
  if (editingItemIndex.value !== null) {
    purchaseRequestForm.items.splice(editingItemIndex.value, 1, item);
  } else {
    purchaseRequestForm.items.push(item);
  }
  const modalElement = document.getElementById('itemModal');
  const modal = bootstrap.Modal.getInstance(modalElement);
  modal.hide();
};

const editItem = (index) => {
  Object.assign(itemForm, purchaseRequestForm.items[index]);
  editingItemIndex.value = index;
  const modalElement = document.getElementById('itemModal');
  const modal = new bootstrap.Modal(modalElement);
  modal.show();
  nextTick(() => {
    $('#product_id').select2({
      placeholder: 'Select a product',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#itemModal')
    }).val(itemForm.product_id).trigger('change');
  });
};

const removeItem = (index) => {
  purchaseRequestForm.items.splice(index, 1);
};

const format = (value, type) => {
  if (type === 'date') {
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
  }
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Pending': return 'badge bg-warning';
    case 'Approved': return 'badge bg-success';
    case 'Rejected': return 'badge bg-danger';
    case 'Void': return 'badge bg-secondary';
    default: return 'badge bg-info';
  }
};

const initializeSelect2 = () => {
  nextTick(() => {
    setTimeout(() => {
      if ($('#campus').length) {
        $('#campus').select2({
          placeholder: 'Select a campus',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.campus = $(this).val();
        });
      }

      if ($('#division').length) {
        $('#division').select2({
          placeholder: 'Select a division',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.division = $(this).val();
        });
      }

      if ($('#department').length) {
        $('#department').select2({
          placeholder: 'Select a department',
          allowClear: true,
          width: '100%',
        }).on('change', function () {
          purchaseRequestForm.department = $(this).val();
        });
      }
    }, 300);
  });
};

onMounted(() => {
  initializeSelect2();
  nextTick(() => {
    const table = $('#purchase-request');
    if (table.length) {
      dataTableInstance = table.DataTable({
        responsive: true, 
        autoWidth: true, 
        scrollX: false,
        data: props.purchaseRequests,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 },
          { data: 'pr_number' },
          { data: 'request_date', render: (data) => format(data, 'date') },
          { data: 'request_by.name' },
          { data: 'campus' },
          { data: 'division' },
          { data: 'department' },
          { data: 'purpose' },
          {
            data: 'is_urgent',
            render: (data) => `<span class="badge ${data ? 'bg-primary' : 'bg-danger'}">${data ? 'Yes' : 'No'}</span>`,
            className: 'text-center'
          },
          { data: 'total_amount', render: (data) => (data ? parseFloat(data).toFixed(2) : '0.00') },
          {
            data: 'status',
            render: (data) => `<span class="${getStatusBadgeClass(data)}">${data}</span>`,
            className: 'text-center'
          },
          {
            data: null,
            render: (data) => `
              <div class="btn-group">
                <a href="#" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  ${data.status !== 'Void' ? '<li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>' : ''}
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> Detail</a></li>
                  <li><a class="dropdown-item btn-cancel text-warning"><i class="fas fa-ban"></i> Cancel</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      $('#purchase-request').on('click', '.btn-cancel', function () {
      const rowData = dataTableInstance.row($(this).closest('tr')).data();
      if (rowData) {
        openCancellationModal(rowData); // Call the function to open the modal
      }
    });

      $('#purchase-request')
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) openEditPage(rowData);
          $('#nav-create-tab').tab('show');
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) deletePurchaseRequest(rowData.id);
        })
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) window.location.href = `/purchase-requests/${rowData.id}`;
        });

      $('#purchase-request').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) openEditPage(rowData);
        $('#nav-create-tab').tab('show');
      });

      $('#purchase-request').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) deletePurchaseRequest(rowData.id);
      });

      $('#purchase-request').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev();
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) window.location.href = `/purchase-requests/${rowData.id}`;
      });
    }
  });
});
</script>

<template>
  <Main>
    <Head :title="'Purchase Request'" />
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <a href="#nav-index" id="nav-index-tab" data-bs-toggle="tab" class="nav-link active" @click="clearForm">PR List</a>
      </li>
      <li class="nav-item">
        <a href="#nav-create" id="nav-create-tab" data-bs-toggle="tab" class="nav-link" @click="openCreatePage">Form</a>
      </li>
    </ul>
    <div class="tab-content panel p-3 rounded-0 rounded-bottom">
      <div class="tab-pane fade active show" id="nav-index">
        <div class="panel-body">
          <table id="purchase-request" class="table table-bordered align-middle text-nowrap" width="100%">
            <thead>
              <tr>
                <th>#</th>
                <th>PR Number</th>
                <th>Request Date</th>
                <th>Request By</th>
                <th>Campus</th>
                <th>Division</th>
                <th>Department</th>
                <th>Purpose</th>
                <th>Urgent?</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div class="tab-pane fade" id="nav-create">
        <div class="panel-body">
          <form @submit.prevent="savePurchaseRequest">
            <div class="row">
              <div class="col-md-6">
                <div class="row mb-3">
                  <label for="request_by" class="col-sm-4 col-form-label">Request By</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseRequestForm.request_by" type="text" class="form-control" id="request_by" readonly />
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="campus" class="col-sm-4 col-form-label">Campus</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.campus" class="form-select select2" id="campus" required>
                      <option value="CEN">CEN</option>
                      <option value="TK">TK</option>
                    </select>
                    <div v-if="validationErrors.campus" class="text-danger">{{ validationErrors.campus[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="division" class="col-sm-4 col-form-label">Division</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.division" class="form-select select2" id="division" required>
                      <option value="Aii">Aii</option>
                      <option value="AIS">AIS</option>
                      <option value="OTHER">OTHER</option>
                      <option value="MJQES">MJQES</option>
                    </select>
                    <div v-if="validationErrors.division" class="text-danger">{{ validationErrors.division[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="department" class="col-sm-4 col-form-label">Department</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.department" class="form-select select2" id="department" required>
                      <option value="ESLP">ESLP</option>
                      <option value="AISAD">AISAD</option>
                      <option value="PROD">PROD</option>
                      <option value="IB">IB</option>
                    </select>
                    <div v-if="validationErrors.department" class="text-danger">{{ validationErrors.department[0] }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="row mb-3">
                  <label for="request_date" class="col-sm-4 col-form-label">Request Date</label>
                  <div class="col-sm-8">
                    <input v-model="purchaseRequestForm.request_date" type="date" class="form-control" id="request_date" required />
                    <div v-if="validationErrors.request_date" class="text-danger">{{ validationErrors.request_date[0] }}</div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="is_urgent" class="col-sm-4 col-form-label">Urgent?</label>
                  <div class="col-sm-8">
                    <select v-model="purchaseRequestForm.is_urgent" class="form-select" id="is_urgent" required>
                      <option :value="1">Yes</option>
                      <option :value="0">No</option>
                    </select>
                    <div v-if="validationErrors.is_urgent" class="text-danger">
                      {{ validationErrors.is_urgent[0] }}
                    </div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
                  <div class="col-sm-8">
                    <textarea v-model="purchaseRequestForm.purpose" class="form-control" id="purpose"></textarea>
                    <div v-if="validationErrors.purpose" class="text-danger">{{ validationErrors.purpose[0] }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <!-- <label for="items" class="form-label">Items</label> -->
                  <button type="button" class="btn btn-sm btn-primary mb-2" @click="openItemModal"><i class="fa-solid fa-circle-plus"></i> Add Item</button>
                  <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered">
                      <thead class="table-primary">
                        <tr>
                          <th>#</th>
                          <th>Product Code</th>
                          <th>Product</th>
                          <th>Remark</th>
                          <th>Campus</th>
                          <th>Division</th>
                          <th>Department</th>
                          <th>Qty</th>
                          <th>UOM</th>
                          <th>Price</th>
                          <th>Total Price</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in purchaseRequestForm.items" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.sku }}</td>
                          <td>{{ item.product_description }}</td>
                          <td>{{ item.remark }}</td>
                          <td>{{ item.campus }}</td>
                          <td>{{ item.division }}</td>
                          <td>{{ item.department }}</td>
                          <td>{{ item.qty }}</td>
                          <td>{{ item.uom }}</td>
                          <td>{{ item.unit_price }}</td>
                          <td>{{ item.total_price }}</td>
                          <td>
                            <button type="button" class="btn btn-sm btn-primary mr-1" @click="editItem(index)" :disabled="item.is_cancel === 1">
                              <i class="fa fa-edit t-plus-1 fa-fw fa-lg"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)" :disabled="item.is_cancel === 1">
                              <i class="fa fa-trash t-plus-1 fa-fw fa-lg"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div v-if="validationErrors.items" class="text-danger">{{ validationErrors.items[0] }}</div>
                </div>
              </div>
            </div>
            <div class="row justify-content-end">
              <div class="col-2">
                <div class="mb-3">
                  <label for="total_amount" class="form-label">Total Amount</label>
                  <input v-model="totalAmount" type="text" class="form-control" id="total_amount" readonly />
                </div>
              </div>
              <div class="col-2">
                <div class="mb-3">
                  <label for="item_count" class="form-label">Item Count</label>
                  <input v-model="itemCount" type="text" class="form-control" id="item_count" readonly />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="clearForm">Clear</button>
              <button type="submit" class="btn btn-primary">{{ isEdit ? 'Update' : 'Create' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addItem">
              <div class="row mb-3">
                <label for="product_id" class="col-md-3 col-form-label">Product</label>
                <div class="col-md-9">
                  <select v-model="itemForm.product_id" class="form-select select2" id="product_id" required>
                    <option v-for="product in props.products" :key="product.id" :value="product.id">
                      {{ product.product_description }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="remark" class="col-md-3 col-form-label">Remark</label>
                <div class="col-md-9">
                  <textarea v-model="itemForm.remark" class="form-control" id="remark" rows="3"></textarea>
                </div>
              </div>
              <div class="row mb-3">
                <label for="qty" class="col-md-3 col-form-label">Qty</label>
                <div class="col-md-9">
                  <input v-model="itemForm.qty" type="number" step="0.0001" class="form-control" id="qty" required min="0.0001" />
                </div>
              </div>
              <div class="row mb-3">
                <label for="uom" class="col-md-3 col-form-label">UOM</label>
                <div class="col-md-9">
                  <input v-model="itemForm.uom" type="text" class="form-control" id="uom" required readonly />
                </div>
              </div>
              <div class="row mb-3">
                <label for="unit_price" class="col-md-3 col-form-label">Price</label>
                <div class="col-md-9">
                  <input v-model="itemForm.unit_price" type="number" step="0.0001" class="form-control" id="unit_price" required min="0.0001" />
                </div>
              </div>
              <div class="row mb-3">
                <label for="total_price" class="col-md-3 col-form-label">Total Price</label>
                <div class="col-md-9">
                  <input v-model="itemForm.total_price" type="text" class="form-control" id="total_price" readonly />
                </div>
              </div>
              <div class="row mb-3">
                <label for="campus" class="col-md-3 col-form-label">Campus</label>
                <div class="col-md-9">
                  <select v-model="itemForm.campus" class="form-select" id="campus" required>
                    <option value="CEN">CEN</option>
                    <option value="TK">TK</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="division" class="col-md-3 col-form-label">Division</label>
                <div class="col-md-9">
                  <select v-model="itemForm.division" class="form-select" id="division" required>
                    <option value="Aii">Aii</option>
                    <option value="AIS">AIS</option>
                    <option value="OTHER">OTHER</option>
                    <option value="MJQES">MJQES</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <label for="department" class="col-md-3 col-form-label">Department</label>
                <div class="col-md-9">
                  <select v-model="itemForm.department" class="form-select" id="department" required>
                    <option value="ESLP">ESLP</option>
                    <option value="AISAD">AISAD</option>
                    <option value="PROD">PROD</option>
                    <option value="IB">IB</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <cancellationModal/>
  </Main>
</template>