<script setup>
// Import usePage from Inertia to get the current route
import { usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

// Get the current page's URL and props
const { url } = usePage();
const page = usePage();
// Helper function to check if the menu item is active
const isActive = (targetRoute) => {
  // Check if the current URL starts with the target route
  return url.startsWith(targetRoute);
};

// Flatten grouped approvals into a single array
// Access approvals from the shared props
const approvals = computed(() => page.props.auth.approvals || [])

// Compute pending approvals count
const pendingApprovalsCount = computed(() => approvals.value.filter(approval => approval.status === 0).length)
</script>


<template>
    <div id="sidebar" class="app-sidebar" data-bs-theme="dark">
    <!-- BEGIN scrollbar -->
    <div class="app-content-padding flex-grow-1 overflow-hidden" data-scrollbar="true" data-height="100%">
        <!-- BEGIN menu -->
        <div class="menu">
            <div class="menu-profile">
                <a href="javascript:;" class="menu-profile-link" data-toggle="app-sidebar-profile" data-target="#appSidebarProfileMenu">
                    <div class="menu-profile-cover with-shadow"></div>
                    <div class="menu-profile-image">
                        <img 
                            :src="$page.props.auth.user.profile ? `/storage/${$page.props.auth.user.profile}` : '/images/default-user-icon.png'" 
                            alt="User Profile" 
                            class="img-fluid rounded-circle" 
                            style="width: 50px; height: 50px; object-fit: cover;" 
                        />
                    </div>
                    <div class="menu-profile-info">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <!-- <div class="menu-caret ms-auto"></div> -->
                        </div>
                        <small>{{ $page.props.auth.user.position }}</small>
                    </div>
                </a>
            </div>
            <div class="menu-header">Navigation</div>
            <div class="menu-item" :class="{'active': isActive('/approvals')}">
                <Link href="/approvals" class="menu-link">
                    <div class="menu-icon">
                        <i class="fa fa-bell"></i>
                    </div>
                    <div class="menu-text">Notifications</div>
                    <span v-if="pendingApprovalsCount > 0" class="badge bg-danger ms-auto">{{ pendingApprovalsCount }}</span>
                </Link>
            </div>
            <div class="menu-item" :class="{'active': isActive('/dashboard')}">
                <Link href="/dashboard" class="menu-link">
                    <div class="menu-icon">
                        <i class="fa fa-chart-pie"></i>
                    </div>
                    <div class="menu-text">Dashboard</div>
                </Link>
            </div>

            <div class="menu-item has-sub" :class="{'active': isActive('/suppliers')}">
                <a href="javascript:;" class="menu-link">
                    <div class="menu-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="menu-text">Suppliers</div>
                    <div class="menu-caret"></div>
                </a>
                <div class="menu-submenu">
                    <div class="menu-item" :class="{'active': isActive('/suppliers')}"><Link href="/suppliers" class="menu-link"><div class="menu-text">Suppliers List</div></Link></div>
                    <!-- <div class="menu-item"><a href="javascript:;" class="menu-link"><div class="menu-text">Menu 1.3</div></a></div> -->
                </div>
            </div>

            <div class="menu-item has-sub" :class="{'active': isActive('/products') || isActive('/categories')}">
                <a href="javascript:;" class="menu-link">
                    <div class="menu-icon">
                        <i class="fa fa-coins"></i>
                    </div>
                    <div class="menu-text">Products</div>
                    <div class="menu-caret"></div>
                </a>
                <div class="menu-submenu">
                    <div class="menu-item" :class="{'active': isActive('/products')}">
                        <Link href="/products" class="menu-link">
                            <div class="menu-text">Product List</div>
                        </Link>
                    </div>
                    <div class="menu-item" :class="{'active': isActive('/categories')}">
                        <Link href="/categories" class="menu-link">
                            <div class="menu-text">Categories</div>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="menu-item has-sub" :class="{'active': isActive('/cash-request') || isActive('/purchase-requests') || isActive('/purchase-orders') || isActive('/invoices') || isActive('/invoice-items') || isActive('/clear-invoice') || isActive('/statements')}">
                <a href="javascript:;" class="menu-link">
                    <div class="menu-icon">
                        <i class="fa fa-money-check-dollar"></i>
                    </div>
                    <div class="menu-text">Purchasing</div>
                    <div class="menu-caret"></div>
                </a>
                <div class="menu-submenu">
                    <div class="menu-item" :class="{'active': isActive('/purchase-requests')}">
                        <Link href="/purchase-requests" class="menu-link">
                            <div class="menu-text">Purcahse Requests</div>
                        </Link>
                    </div>
                    <div class="menu-item" :class="{'active': isActive('/purchase-orders')}">
                        <Link href="/purchase-orders" class="menu-link">
                            <div class="menu-text">Purcahse Orders</div>
                        </Link>
                    </div>
                    <div class="menu-item" :class="{'active': isActive('/cash-request')}">
                        <Link href="/cash-request" class="menu-link">
                            <div class="menu-text">Cash Request</div>
                        </Link>
                    </div>
                    <div class="menu-item has-sub" :class="{'active': isActive('/invoices') || isActive('/invoice-items')}">
                        <a href="javascript:;" class="menu-link">
                            <div class="menu-text">Purchase Invoices</div>
                            <div class="menu-caret"></div>
                        </a>
                        <div class="menu-submenu">
                            <div class="menu-item" :class="{'active': isActive('/invoices')}">
                                <Link href="/invoices" class="menu-link">
                                    <div class="menu-text">Invoice Entry</div>
                                </Link>
                            </div>
                            <div class="menu-item" :class="{'active': isActive('/invoice-items')}">
                                <Link href="/invoice-items" class="menu-link">
                                    <div class="menu-text">Invoice Item List</div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item has-sub" :class="{'active': isActive('/clear-invoice') || isActive('/statements')}">
                        <a href="javascript:;" class="menu-link">
                            <div class="menu-text">Clear Invoice</div>
                            <div class="menu-caret"></div>
                        </a>
                        <div class="menu-submenu">
                            <div class="menu-item" :class="{'active': isActive('/clear-invoice')}">
                                <Link href="/clear-invoice" class="menu-link">
                                    <div class="menu-text">Clear Cash</div>
                                </Link>
                            </div>
                            <div class="menu-item" :class="{'active': isActive('/statements')}">
                                <Link href="/statements" class="menu-link">
                                    <div class="menu-text">Clear Credit</div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="menu-item">
                <a href="/documents" class="menu-link" v-bind:target="'_blank'">
                    <div class="menu-icon">
                        <i class="fa fa-file-alt"></i> <!-- Updated icon -->
                    </div>
                    <div class="menu-text">Documents</div>
                </a>
            </div>

            <!-- BEGIN minify-button -->
            <div class="menu-item d-flex">
                <a href="javascript:;" class="app-sidebar-minify-btn ms-auto d-flex align-items-center text-decoration-none" data-toggle="app-sidebar-minify"><i class="fa fa-angle-double-left"></i></a>
            </div>
            <!-- END minify-button -->
        </div>
        <!-- END menu -->
    </div>
    <!-- END scrollbar -->
</div>
<div class="app-sidebar-bg" data-bs-theme="dark"></div>
<div class="app-sidebar-mobile-backdrop"><a href="#" data-dismiss="app-sidebar-mobile" class="stretched-link"></a></div>
</template>
