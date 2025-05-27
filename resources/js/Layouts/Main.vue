<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, useForm, Link } from '@inertiajs/vue3';
// import { router } from '@inertiajs/core';
import Sidebar from '@/Components/Sidebar.vue';
import Loading from '@/Components/Loading.vue';
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import BotChatWidget from '@/Components/BotChatWidget.vue';


dayjs.extend(relativeTime)

const loadingRef = ref(null);
const { url } = usePage();
const page = usePage();
const isDarkMode = ref(false);
const isHeaderInverse = ref(false);

const showBotChat = ref(false);

const checkDarkMode = () => {
  isDarkMode.value =
    document.body.getAttribute('data-bs-theme') === 'dark' ||
    document.documentElement.getAttribute('data-bs-theme') === 'dark';
};

const checkHeaderInverse = () => {
  const header = document.getElementById('header');
  isHeaderInverse.value = header && header.getAttribute('data-bs-theme') === 'dark';
};

// Nottification count
const approvals = computed(() => page.props.auth.approvals || [])
const pendingApprovals = computed(() => approvals.value.filter(approval => approval.status === 0))
const pendingCount = computed(() => pendingApprovals.value.length)
const friendlyDate = (date) => {
  const dateString = dayjs(date).fromNow()
  return dateString.charAt(0).toUpperCase() + dateString.slice(1)
}

const getApprovalTitle = (docs_type) => {
  const docsMap = {
    1: 'Request Petty Cash',
    2: 'Request Advance',
    3: 'Clear Petty Cash',
    4: 'Clear Advance',
    5: 'Clear Credit',
    6: 'Purchase Request Cancel',
    7: 'Purchase Order Cancel',
  }
  const docLabel = docsMap[docs_type] || 'Unknown Document'
  return `${docLabel}`
}

const getStatusInfo = (statusType) => {
  const statusMap = {
    1: { text: 'Need Check', badge: 'border border-warning text-warning bg-transparent', icon: 'fa-exclamation-triangle' },  // Icon for 'Need Check'
    2: { text: 'Need Acknowledge', badge: 'border border-info text-info bg-transparent', icon: 'fa-check-circle' }, // Icon for 'Need Acknowledge'
    3: { text: 'Need Approve', badge: 'border border-primary text-primary bg-transparent', icon: 'fa-thumbs-up' }, // Icon for 'Need Approve'
    4: { text: 'Need Receive', badge: 'border border-success text-success bg-transparent', icon: 'fa-download' }, // Icon for 'Need Receive'
    5: { text: 'Need Authorize', badge: 'border border-warning text-warning bg-transparent', icon: 'fa-check-circle' }, // Updated for Authorize
  }

  return statusMap[+statusType] || { text: 'Unknown Status', badge: 'border border-secondary text-secondary bg-transparent', icon: 'fa-question-circle' }
}

const getApprovalStatus = (status_type) => {
  const statusMap = {
	0: 'Pending',
	1: 'Need Check',
	2: 'Need Acknowledge',
	3: 'Need Approve',
	4: 'Need Receive',
  }

  return statusMap[status_type] || 'Unknown Status'
}

function viewApproval(docsType, approvalId) {
  let url = '';
  switch (docsType) {
    case 1:
    case 2:
      url = `/cash-request/${approvalId}`;
      break;
    case 3:
    case 4:
      url = `/clear-invoice/${approvalId}`;
      break;
    case 5:
      url = `/statements/${approvalId}`;
      break;
    case 6:
      url = `/cancellations/${approvalId}`;
      break;
    case 7:
      url = `/cancellations/${approvalId}`;
      break;
    default:
      console.error('Unknown docs_type:', docsType);
      return;
  }
  window.location.href = url; // Navigate to the URL in the same tab
}

//End Notitfication



// Loading handlers
const showLoader = () => {
  if (loadingRef.value) loadingRef.value.isLoading = true;
};

const hideLoader = () => {
  if (loadingRef.value) loadingRef.value.isLoading = false;
};

let observer;

let headerObserver;

onMounted(() => {
  checkDarkMode();
  observer = new MutationObserver(checkDarkMode);
  observer.observe(document.body, { attributes: true, attributeFilter: ['data-bs-theme'] });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

  // Observe header for data-bs-theme changes
  checkHeaderInverse();
  const header = document.getElementById('header');
  if (header) {
    headerObserver = new MutationObserver(checkHeaderInverse);
    headerObserver.observe(header, { attributes: true, attributeFilter: ['data-bs-theme'] });
  }

  document.addEventListener('inertia:start', showLoader);
  document.addEventListener('inertia:finish', hideLoader);
});

onUnmounted(() => {
  if (observer) observer.disconnect();
  if (headerObserver) headerObserver.disconnect();
  document.removeEventListener('inertia:start', showLoader);
  document.removeEventListener('inertia:finish', hideLoader);
});

// Form and logout handling
const form = useForm({});
const logout = () => {
  form.post(route('logout'));
};
</script>

<template>
  <!-- BEGIN #app -->
  <div id="app" class="app app-header-fixed app-sidebar-fixed app-content-full-height">
    <Loading ref="loadingRef" />
    <!-- BEGIN #header -->
    <div id="header" class="app-header" data-bs-theme="dark">
      <div class="navbar-header">
		<a href="/dashboard" class="navbar-brand d-flex align-items-center">
		<img
			:src="isDarkMode || isHeaderInverse
			? 'https://mjqeducation.edu.kh//storage/photos/MJQEGroupLogo/mjqe-white-logo.png'
			: 'https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png'"
			alt="Logo"
			class="main-logo-img"
		>
		<span
			class="brand-text"
			:style="{ color: (isDarkMode || isHeaderInverse) ? '#fff' : '#222' }"
		><b>| E-Purchasing</b></span>
		</a>

        <button type="button" class="navbar-mobile-toggler" data-toggle="app-sidebar-mobile">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
      </div>
      <div class="navbar-nav">
        <div class="navbar-item navbar-form">
          <form action="" method="POST" name="search">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Enter keyword" />
              <button type="submit" class="btn btn-search"><i class="fa fa-search"></i></button>
            </div>
          </form>
        </div>
        <div class="navbar-item dropdown">
          <a href="#" data-bs-toggle="dropdown" class="navbar-link dropdown-toggle fs-14px">
            <i class="fa fa-bell"></i>
            <span class="badge">{{ pendingCount }}</span>
          </a>
		  <div class="dropdown-menu media-list dropdown-menu-end w-300px">
			<div class="dropdown-header">
			NOTIFICATIONS ({{ pendingCount }})
			</div>
			<template v-if="pendingCount > 0">
			<div
				v-for="approval in pendingApprovals"
				:key="approval.approval_id"
				class="d-flex align-items-center px-3 py-2 border-bottom notification-item"
				@click="viewApproval(approval.docs_type, approval.approval_id)"
			>
				<div class="flex-grow-1">
				<div class="fw-bold">
					<!-- Badge with status type -->
					{{ getApprovalTitle(approval.docs_type, approval.status_type) }}
          <span :class="['badge', getStatusInfo(approval.status_type).badge]">
            <i :class="['fa', getStatusInfo(approval.status_type).icon, 'me-2']"></i>  <!-- Icon with margin-right -->
            {{ getStatusInfo(approval.status_type).text }}
          </span>

				</div>
				<div class="small text-primary">
					{{ friendlyDate(approval.created_at) }}
				</div>
				</div>
			</div>
			</template>
			<div v-else class="text-center w-300px py-3">
			No notification found
			</div>
			<div class="dropdown-footer">
			<a href="/approvals" class="dropdown-item text-center">View All Approvals</a>
			</div>
		</div> <!-- Closing the dropdown-menu -->

        </div>
        <div class="navbar-item navbar-user dropdown">
          <a href="#" class="navbar-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
			<div class="menu-profile-image">
				<img 
					:src="$page.props.auth.user.profile ? `/storage/${$page.props.auth.user.profile}` : '/images/default-user-icon.png'" 
					alt="User Profile" 
					class="img-fluid rounded-circle" 
					style="width: 30px; height: 30px; object-fit: cover;" 
				/>
			</div>
            <span class="d-none d-md-inline">{{ $page.props.auth.user.name }}</span> <b class="caret ms-10px"></b>
          </a>
          <div class="dropdown-menu dropdown-menu-end me-1">
            <Link href="/profile" class="dropdown-item">Edit Profile</Link>
            <!-- <a href="javascript:;" class="dropdown-item d-flex align-items-center">
              Inbox
              <span class="badge bg-danger rounded-pill ms-auto pb-4px">2</span>
            </a>
            <a href="javascript:;" class="dropdown-item">Calendar</a>
            <a href="javascript:;" class="dropdown-item">Setting</a> -->
            <div class="dropdown-divider"></div>
            <!-- Log out option -->
            <a href="javascript:;" @click="logout" class="dropdown-item">Log Out</a>
          </div>
        </div>
      </div>
    </div>
    <!-- END header-nav -->

    <!-- Sidebar -->
    <Sidebar/>
    <!-- END Sidebar -->

    <!-- BEGIN #content -->
    <div id="content" class="app-content d-flex flex-column p- flex-grow-1">
      <slot />
      <div id="footer" class="app-footer mt-auto">
        &copy; 2024 PROD MJQE All Right Reserved
      </div>
    <div>
      <button
        class="btn btn-primary btn-lg rounded-circle shadow"
        style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;"
        @click="showBotChat = true"
        v-if="!showBotChat"
      >
        <i class="fa fa-robot"></i>
      </button>
      <transition name="fade">
        <div
          v-if="showBotChat"
          class="bot-chat-popup"
        >
          <div class="card shadow-lg bot-chat-card">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
              <span><i class="fa fa-robot mr-2"></i>Telegram Bot Chat</span>
              <button class="btn btn-sm btn-danger" @click="showBotChat = false">
                <i class="fa fa-times"></i>
              </button>
            </div>
            <BotChatWidget />
          </div>
        </div>
      </transition>
    </div>
    </div>
	<a href="javascript:;" class="btn btn-icon btn-circle btn-theme btn-scroll-to-top" data-toggle="scroll-to-top">
		<i class="fa fa-angle-up"></i>
	</a>
    <!-- END #content -->

		<!-- BEGIN theme-panel -->
		<div class="theme-panel">
			<a href="javascript:;" data-toggle="theme-panel-expand" class="theme-collapse-btn"><i class="fa fa-cog"></i></a>
			<div class="theme-panel-content" data-scrollbar="true" data-height="100%">
				<h5>App Settings</h5>

				<!-- BEGIN theme-list -->
				<div class="theme-list">
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-red" data-theme-class="theme-red" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Red">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-pink" data-theme-class="theme-pink" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Pink">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-orange" data-theme-class="theme-orange" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Orange">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-yellow" data-theme-class="theme-yellow" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Yellow">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-lime" data-theme-class="theme-lime" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Lime">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-green" data-theme-class="theme-green" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Green">&nbsp;</a></div>
					<div class="theme-list-item active"><a href="javascript:;" class="theme-list-link bg-teal" data-theme-class="" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Default">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-cyan" data-theme-class="theme-cyan" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Cyan">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-blue" data-theme-class="theme-blue" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Blue">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-purple" data-theme-class="theme-purple" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Purple">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-indigo" data-theme-class="theme-indigo" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Indigo">&nbsp;</a></div>
					<div class="theme-list-item"><a href="javascript:;" class="theme-list-link bg-black" data-theme-class="theme-gray-600" data-toggle="theme-selector" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-container="body" data-bs-title="Black">&nbsp;</a></div>
				</div>
				<!-- END theme-list -->

				<div class="theme-panel-divider"></div>

				<div class="row mt-10px">
					<div class="col-8 control-label text-body fw-bold">
						<div>Dark Mode <span class="badge bg-primary ms-1 py-2px position-relative" style="top: -1px;">NEW</span></div>
						<div class="lh-14">
							<small class="text-body opacity-50">
								Adjust the appearance to reduce glare and give your eyes a break.
							</small>
						</div>
					</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-theme-dark-mode" id="appThemeDarkMode" value="1" />
							<label class="form-check-label" for="appThemeDarkMode">&nbsp;</label>
						</div>
					</div>
				</div>

				<div class="theme-panel-divider"></div>

				<!-- BEGIN theme-switch -->
				<div class="row mt-10px align-items-center">
					<div class="col-8 control-label text-body fw-bold">Header Fixed</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-header-fixed" id="appHeaderFixed" value="1" checked />
							<label class="form-check-label" for="appHeaderFixed">&nbsp;</label>
						</div>
					</div>
				</div>
				<div class="row mt-10px align-items-center">
					<div class="col-8 control-label text-body fw-bold">Header Inverse</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-header-inverse" id="appHeaderInverse" value="1" />
							<label class="form-check-label" for="appHeaderInverse">&nbsp;</label>
						</div>
					</div>
				</div>
				<div class="row mt-10px align-items-center">
					<div class="col-8 control-label text-body fw-bold">Sidebar Fixed</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-sidebar-fixed" id="appSidebarFixed" value="1" checked />
							<label class="form-check-label" for="appSidebarFixed">&nbsp;</label>
						</div>
					</div>
				</div>
				<div class="row mt-10px align-items-center">
					<div class="col-8 control-label text-body fw-bold">Sidebar Grid</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-sidebar-grid" id="appSidebarGrid" value="1" />
							<label class="form-check-label" for="appSidebarGrid">&nbsp;</label>
						</div>
					</div>
				</div>
				<div class="row mt-10px align-items-center">
					<div class="col-8 control-label text-body fw-bold">Gradient Enabled</div>
					<div class="col-4 d-flex">
						<div class="form-check form-switch ms-auto mb-0">
							<input type="checkbox" class="form-check-input" name="app-gradient-enabled" id="appGradientEnabled" value="1" />
							<label class="form-check-label" for="appGradientEnabled">&nbsp;</label>
						</div>
					</div>
				</div>
				<!-- END theme-switch -->

				<div class="theme-panel-divider"></div>
				<a href="javascript:;" class="btn btn-default d-block w-100 rounded-pill" data-toggle="reset-local-storage"><b>Reset Local Storage</b></a>
			</div>
		</div>
		<!-- END theme-panel -->
  </div>
</template>

<style scoped>
/* Optional styling specific to this component */
.brand-text {
    font-size: 18px; /* Set specific font size */
    margin-left: 5px;
	margin-top: 13px;
}

.dropdown-menu {
  max-height: 400px; /* Set the max height to limit the size */
  overflow-y: auto; /* Enable vertical scrolling */
  overflow-x: hidden; /* Prevent horizontal scrolling */
  -webkit-overflow-scrolling: touch; /* Enable smooth scrolling on touch devices */
}

.notification-item {
  cursor: pointer;
  transition: background-color 0.3s, transform 0.2s ease;
}

.notification-item:hover {
  background-color: #f1f1f1; /* Light background color on hover */
  transform: scale(1.02); /* Slight pop effect */
}

.notification-item:active {
  background-color: #e0e0e0; /* Darker background when clicked */
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.bot-chat-popup {
  position: fixed;
  bottom: 90px;
  right: 30px;
  z-index: 1060;
  width: 420px;
  max-width: 100vw;
}
.bot-chat-card {
  height: 600px;
  display: flex;
  flex-direction: column;
}
@media (max-width: 600px) {
  .bot-chat-popup {
    bottom: 0;
    right: 0;
    left: 0;
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
    z-index: 1060;
  }
  .bot-chat-card {
    height: 100vh;
    border-radius: 0;
  }
}
</style>
