<script setup>
// import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const pageTitle = 'Dashboard';
</script>

<template>
     <Head :title="pageTitle" />
    <Main>
	<!-- BEGIN #loader -->
	<div id="loader" class="app-loader">
		<span class="spinner"></span>
	</div>
	<!-- END #loader -->
	<!-- BEGIN #app -->
	<div id="app" class="app">
		<!-- BEGIN login -->
		<div class="login login-v1">
			<!-- BEGIN login-container -->
			<div class="login-container">
				<!-- BEGIN login-body -->
				<div class="login-body">
					<!-- BEGIN login-content -->
                    <h1>Hello all</h1>
					<!-- END login-content -->
				</div>
				<!-- END login-body -->
			</div>
			<!-- END login-container -->
		</div>
		<!-- END login -->
		<!-- BEGIN scroll-top-btn -->
		<a href="javascript:;" class="btn btn-icon btn-circle btn-theme btn-scroll-to-top" data-toggle="scroll-to-top"><i class="fa fa-angle-up"></i></a>
		<!-- END scroll-top-btn -->
	</div>
	<!-- END #app -->
    </Main>
</template>

<!-- <script>
import { initializeColorAdmin } from '../colorAdminInit';
export default {
  mounted() {
    // Call the function to initialize ColorAdmin after the page has loaded
    initializeColorAdmin();
  },
};
</script> -->
