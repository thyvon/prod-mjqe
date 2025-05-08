<template>
    <Main>
      <Head :title="'Clear Invoice Details'" />
      <div class="container a4-size">
        <!-- Add a back button -->
        <div class="row">
          <div class="col text-end">
            <button @click="goBack" class="btn btn-secondary">Back</button>
            <button @click="printPage" class="btn btn-primary">Print</button>
          </div>
        </div>
        <!-- Wrap the content in a unique ID -->
        <div id="printable-area">
          <div class="row justify-content-center px-2">
            <!-- Header Section -->
            <div class="row mb-3">
              <div class="col-3">
                <a class="d-block text-start" href="#!">
                  <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                </a>
              </div>
              <div class="col-6 pt-5">
                <div class="row font-monospace">
                  <h5 class="text-uppercase text-center" style="font-family: 'Khmer OS Moul Light';">ពាក្យស្នើសុំថវិកា</h5>
                  <h5 class="text-uppercase text-center fw-bold"style="font-family: 'TW Cen MT';">Cash Request Form</h5>
                </div>
              </div>
              <div class="col-3">
                <div class="row font-monospace">
                  <span class="text-sm-end" style="font-size: x-small;">Code: MJQE0072</span>
                  <span class="text-sm-end" style="font-size: x-small;">Version 1.3</span>
                </div>
              </div>
            </div>
  
            <!-- Personal Information Section -->
  
            <div class="row mb-1">
              <div class="col-12">
                <div class="row">
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>អ្នកទទួល/Receiver:</span>
                    </div>
                  </div>
                  <div class="col-4 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ statement.supplier?.name || 'N/A' }}</span>
                  </div>
  
                  <div class="col-1">
                    <div class="row">
                      <!-- <span>TK</span> -->
                    </div>
                  </div>
  
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>លេខយោង/Ref:</span>
                    </div>
                  </div>
                  <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ statement.statement_number || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </div>
  
            <div class="row mb-1">
              <div class="col-12">
                <div class="row">
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>សាខា/Campus:</span>
                    </div>
                  </div>
                  <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ campuses }}</span>
                  </div>
  
                  <div class="col-2">
                    <div class="row">
                      <!-- <span>TK</span> -->
                    </div>
                  </div>
  
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>កាលបរិច្ឆេទ/Date:</span>
                    </div>
                  </div>
                  <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ formatDate(statement.clear_date) || 'N/A' }} 
                      </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-12">
                <div class="row">
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>អាជីវកម្ម/Division:</span>
                    </div>
                  </div>
                  <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ divisions }}</span>
                  </div>
  
                  <div class="col-2">
                    <div class="row">
                      <!-- <span>TK</span> -->
                    </div>
                  </div>
  
                  <div class="col-2 text-start p-0">
                    <div class="row mt-2">
                      <span>ផ្នែក/Department:</span>
                    </div>
                  </div>
                  <div class="col-3 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                    <span class="w-100 text-start ps-1 fw-bold">{{ departments }}</span>
                  </div>
                </div>
              </div>
            </div>
  


            <div class="row mb-3">
              <div class="table-responsive width-full p-0">
                <table class="table table-bordered border-dark table-sm">
                  <thead style="font-size: 12px;">
                    <tr class="text-center">
                      <th style="width: 5%;">ល.រ.<br>No.</th>
                      <th style="width: 50%;">បរិយាយ<br>Description</th>
                      <th style="width: 10%;">សាខា<br>Campus</th>
                      <th style="width: 20%;">ទឹកប្រាក់<br>Total Amount</th>
                    </tr>
                  </thead>
                  <tbody class="table-group-divider" style="font-size: 12px;">
                    <tr v-for="(group, index) in groupedByCampus" :key="group.campus">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td class="text-center">{{ statement.description || 'N/A' }}</td>
                      <td class="text-center">{{ group.campus }}</td>
                      <td class="text-end">{{ group.totalAmount.toFixed(4) }} {{ statement.supplier?.currency === 1 ? 'USD' : statement.supplier?.currency === 2 ? 'KHR' : 'N/A' }}</td>
                    </tr>
                    <tr>
                      <td colspan="3" class="text-end fw-bold">សរុប/Total:</td>
                      <td class="text-end fw-bold">
                        {{ groupedByCampus.reduce((total, group) => total + group.totalAmount, 0).toFixed(4) || '0.0000' }} {{ statement.supplier?.currency === 1 ? 'USD' : statement.supplier?.currency === 2 ? 'KHR' : 'N/A' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Remark -->
            <div class="row mb-3">
              <label class="col-12 text-start">សម្គាល់/Remark:</label>
              <div class="col-12 border-black px-1 d-flex align-items-center" style="min-height: 30px; height: auto;">
                <span class="w-100 text-start ps-1">{{ statement.remark || 'N/A' }}</span>
              </div>
            </div>
  
            <!-- Footer Section -->
            <div class="row mb-3" style="height: 150px;">
              <div class="col-4 text-center px-2 mb-3">
                <div>រៀបចំដោយ</div>
                <div>Prepared By</div>
                <img
                  :src="getSignatureUrl(statement.cleared_by?.signature || '')"
                  alt="Signature"
                  style="width: 130px; height: 80px; object-fit: contain;"
                />
                <div class="border-top mt-2 pt-1 text-start">
                  <div>Name: {{ statement.cleared_by?.name || 'N/A' }}</div>
                  <div>Position: {{ statement.cleared_by?.position || 'N/A' }}</div>
                  <div>Date: {{ statement.clear_date ? formatDate(statement.clear_date) : 'N/A' }}</div>
                </div>
              </div>
              <!-- Loop through approvals -->
              <div 
                v-for="approval in approvals" 
                :key="approval.status_type" 
                class="col-4 text-center px-2 mb-3"
              >
                <div v-if="approval.label === 'Checked By'">ពិនិត្យដោយ</div>
                <div v-else-if="approval.label === 'Approved By'">អនុម័តដោយ</div>
                <div>{{ approval.label }}</div>
                <img
                  v-if="approval.status === 1"
                  :src="getSignatureUrl(approval.signature)"
                  alt="Signature"
                  style="width: 130px; height: 80px; object-fit: contain;"
                />
                <div v-else-if="approval.status === -1" class="text-danger mt-2">
                  <i class="fas fa-times-circle fa-2x"></i> <!-- Font Awesome Reject Icon -->
                  <div>Rejected</div>
                </div>
                <!-- Buttons under the image -->
                <div class="mt-2">
                  <button 
                    class="btn btn-success btn-sm" 
                    @click="approveRequest(approval.status_type)"
                    v-if="approval.user_id === currentUser.id && approval.status === 0 &&
                      (
                        (approval.label === 'Checked By') ||
                        (approval.label === 'Approved By' && approvals.find(a => a.label === 'Checked By' && a.status === 1))
                      )"
                  >
                    Sign
                  </button>
                  <button 
                    class="btn btn-danger btn-sm ms-2" 
                    @click="rejectRequest(approval.status_type)"
                    v-if="approval.user_id === currentUser.id && approval.status === 0 &&
                      (
                        (approval.label === 'Checked By') ||
                        (approval.label === 'Approved By' && approvals.find(a => a.label === 'Checked By' && a.status === 1))
                      )"
                  >
                    Reject
                  </button>
                </div>
                <div class="border-top mt-2 pt-1 text-start">
                  <div>Name: {{ approval.name }}</div>
                  <div>Position: {{ approval.position }}</div>
                  <div>Date: {{ approval.click_date ? formatDate(approval.click_date) : '' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Main>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Main from '@/Layouts/Main.vue';
  
  // Define the props using defineProps()
  const props = defineProps({
    statement: {
      type: Object,
      required: true,
    },
    approvals: {
      type: Array,
      default: () => [],
    },
    currentUser: {
      type: Object,
      required: true,
    },
  });

  const goBack = () => {
  window.history.back(); // Navigate to the previous page
};

const printPage = () => {
  const printableArea = document.getElementById('printable-area');
  const originalContent = document.body.innerHTML;

  // Replace the body content with the printable area
  document.body.innerHTML = printableArea.innerHTML;

  // Trigger the print dialog
  window.print();

  // Restore the original content
  document.body.innerHTML = originalContent;

  // Reload the page to restore functionality
  window.location.reload();
};

  const getUniqueValues = (key) => {
    return computed(() => {
      const items = props.statement.invoices.flatMap(invoice => invoice.purchase_invoice.items);
      const uniqueValues = [...new Set(items.map(item => item[key] || 'N/A'))]; // Ensure unique values
      return uniqueValues.join(', ');
    });
  };

  const departments = getUniqueValues('department');
  const divisions = getUniqueValues('division');
  const campuses = getUniqueValues('campus');


  const groupedByCampus = computed(() => {
    const items = props.statement.invoices.flatMap(invoice => invoice.purchase_invoice.items);
    const grouped = items.reduce((acc, item) => {
      const campus = item.campus || 'N/A';
      if (!acc[campus]) {
        acc[campus] = { campus, totalAmount: 0, items: [] };
      }
      acc[campus].totalAmount += parseFloat(item.paid_amount || 0);
      acc[campus].items.push(item);
      return acc;
    }, {});
    return Object.values(grouped);
  });
  
  // Helper functions
  const getSignatureUrl = (signature) => {
    return signature ? `/storage/${signature}` : 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png'; // Fallback image
  };
  
  const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: '2-digit' };
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', options);
  };

  const getStatusTypeString = (statusType) => {
    switch (statusType) {
      case 1:
        return 'Check';
      case 3:
        return 'Approve';
      default:
        return 'Unknown';
    }
  };

  const approveRequest = async (statusType) => {
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

    if (!confirmResult) {
      return;
    }

    try {
      await axios.post(`/statements/${props.statement.id}/approve`, {
        status_type: statusType,
      });
      await swal({
        title: 'Success',
        text: `The Request is successfully ${getStatusTypeString(statusType)}.`,
        icon: 'success',
        button: {
          text: 'OK',
          className: 'btn btn-primary',
        },
      });
      window.location.reload(); // Reload the page after showing the alert
    } catch (error) {
      console.error('Approval Error:', error);
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

    if (!confirmResult) {
      return;
    }

    try {
      await axios.post(`/statements/${props.statement.id}/reject`, {
        status_type: statusType,
      });
      await swal({
        title: 'Success',
        text: `Request has been rejected for ${getStatusTypeString(statusType)} step.`,
        icon: 'success',
        button: {
          text: 'OK',
          className: 'btn btn-primary',
        },
      });
      window.location.reload(); // Reload the page after showing the alert
    } catch (error) {
      console.error('Rejection Error:', error);
      await swal({
        title: 'Error',
        text: `Failed to reject request for ${getStatusTypeString(statusType)} step.`,
        icon: 'error',
        button: {
          text: 'OK',
          className: 'btn btn-danger',
        },
      });
    }
  };
</script>
  
  <style scoped>
  .a4-size {
    width: 210mm;
    height: 297mm;
    margin: 10mm auto; /* Updated margin for A4 paper */
    padding: 10mm;
    background: white !important;
    color: black !important; /* Force text color */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    overflow: auto;
  }

  .a4-size * {
    color: black !important; /* Force all child elements to show black text */
  }

  .table th,
  .table td {
    border: 1px solid black; /* Force dark borders */
  }

  .border-black {
    border: 1px solid #000 !important; /* Force black border */
  }
  
  @media print {
    .a4-size {
      width: 210mm;
      height: 297mm;
      margin: 10mm auto; /* Ensure margin is applied during printing */
      padding: 20mm !important; /* Enforce padding for print */
      box-shadow: none; /* Remove any shadow for clean printing */
    }
  
    #printable-area {
      padding: 20mm !important; /* Ensure padding is applied to the printable area */
      font-family: 'TW Cen MT', 'Khmer OS Content', 'Khmer OS Moul Light' !important; /* Ensure font is applied */
    }
  }
  </style>