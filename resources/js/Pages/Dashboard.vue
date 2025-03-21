<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';

const pageTitle = 'Dashboard';
const currentRange = ref('');

onMounted(() => {
    const daterangeFilter = document.querySelector('#daterange-filter');
    const today = moment();
    const thisYearStart = moment().startOf('year');
    const lastYearStart = moment().subtract(1, 'year').startOf('year');
    const lastYearEnd = moment().subtract(1, 'year').endOf('year');

    // Set initial range to "This Year"
    currentRange.value = `${thisYearStart.format('D MMM YYYY')} - ${today.format('D MMM YYYY')}`;

    if (daterangeFilter) {
        $(daterangeFilter).daterangepicker({
            startDate: thisYearStart,
            endDate: today,
            showDropdowns: true, // Enable month and year selection
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This Year': [thisYearStart, today],
                'Last Year': [lastYearStart, lastYearEnd]
            },
            opens: 'right',
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function(start, end) {
            currentRange.value = `${start.format('D MMM YYYY')} - ${end.format('D MMM YYYY')}`;
        });
    }

    const ctx6 = document.getElementById('doughnut-chart').getContext('2d');
    const isDarkTheme = document.body.classList.contains('dark-theme'); // Check if dark theme is active

    const backgroundColors = isDarkTheme
        ? [
            'rgba(75, 192, 192, 0.7)', // Teal
            'rgba(54, 162, 235, 0.7)', // Blue
            'rgba(255, 206, 86, 0.7)', // Yellow
            'rgba(201, 203, 207, 0.7)', // Gray
            'rgba(153, 102, 255, 0.7)'  // Purple
        ]
        : [
            'rgba(75, 192, 192, 0.5)', // Teal
            'rgba(54, 162, 235, 0.5)', // Blue
            'rgba(255, 206, 86, 0.5)', // Yellow
            'rgba(201, 203, 207, 0.5)', // Gray
            'rgba(153, 102, 255, 0.5)'  // Purple
        ];

    const borderColors = isDarkTheme
        ? [
            'rgba(75, 192, 192, 1)', // Teal
            'rgba(54, 162, 235, 1)', // Blue
            'rgba(255, 206, 86, 1)', // Yellow
            'rgba(201, 203, 207, 1)', // Gray
            'rgba(153, 102, 255, 1)'  // Purple
        ]
        : [
            'rgba(75, 192, 192, 0.8)', // Teal
            'rgba(54, 162, 235, 0.8)', // Blue
            'rgba(255, 206, 86, 0.8)', // Yellow
            'rgba(201, 203, 207, 0.8)', // Gray
            'rgba(153, 102, 255, 0.8)'  // Purple
        ];

    window.myDoughnut = new Chart(ctx6, {
        type: 'doughnut',
        data: {
            labels: ['Dataset 1', 'Dataset 2', 'Dataset 3', 'Dataset 4', 'Dataset 5'],
            datasets: [{
                data: [300, 50, 100, 40, 120],
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2,
                label: 'My dataset'
            }]
        }
    });
});
</script>

<template>
    <Head :title="pageTitle" />
    <Main>

		<!-- BEGIN #content -->
			<!-- BEGIN page-header -->
			<h1 class="page-header mb-3">Procurement Dashboard</h1>
			<!-- END page-header -->
			<!-- BEGIN daterange-filter -->
			<div class="d-sm-flex align-items-center mb-3">
				<a href="#" class="btn btn-dark me-2 text-truncate" id="daterange-filter">
					<i class="fa fa-calendar fa-fw text-white text-opacity-50 ms-n1"></i> 
					 <span>{{ currentRange }}</span>
					<b class="caret ms-1 opacity-5"></b>
				</a>
			</div>
			<!-- END daterange-filter -->
			<!-- BEGIN row -->
			<div class="row">
				<!-- BEGIN col-6 -->
				<div class="col-xl-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 overflow-hidden bg-gray-800 text-white">
						<!-- BEGIN card-body -->
						<div class="card-body">
							<!-- BEGIN row -->
							<div class="row">
								<!-- BEGIN col-7 -->
								<div class="col-xl-7 col-lg-8">
									<!-- BEGIN title -->
									<div class="mb-3 text-gray-500">
										<b>TOTAL EXPENSE</b>
										<span class="ms-2">
											<i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Total sales" data-bs-placement="top" data-bs-content="Net sales (gross sales minus discounts and returns) plus taxes and shipping. Includes orders from all sales channels."></i>
										</span>
									</div>
									<!-- END title -->
									<!-- BEGIN total-sales -->
									<div class="d-flex mb-1">
										<h2 class="mb-0">$<span data-animation="number" data-value="64559.25">0.00</span></h2>
										<div class="ms-auto mt-n1 mb-n1"><div id="total-sales-sparkline"></div></div>
									</div>
									<!-- END total-sales -->
									<!-- BEGIN percentage -->
									<div class="mb-3 text-gray-500">
										<i class="fa fa-caret-up"></i> <span data-animation="number" data-value="25.5">0.00</span>% from previous 7 days
									</div>
									<hr class="bg-white bg-opacity-50" />
									<!-- BEGIN row -->
									<div class="row text-truncate">
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">CREDIT</div>
											<div class="fs-18px mb-5px fw-bold" data-animation="number" data-value="1568">0</div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												<div class="progress-bar progress-bar-striped rounded-right bg-teal" data-animation="width" data-value="55%" style="width: 0%"></div>
											</div>
										</div>
										<!-- END col-6 -->
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">ADVANCE</div>
											<div class="fs-18px mb-5px fw-bold">$<span data-animation="number" data-value="41.20">0.00</span></div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												<div class="progress-bar progress-bar-striped rounded-right" data-animation="width" data-value="55%" style="width: 0%"></div>
											</div>
										</div>
										<!-- END col-6 -->
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">PETTY CASH</div>
											<div class="fs-18px mb-5px fw-bold">$<span data-animation="number" data-value="41.20">0.00</span></div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												<div class="progress-bar progress-bar-striped rounded-right" data-animation="width" data-value="55%" style="width: 0%"></div>
											</div>
										</div>
										<!-- END col-6 -->
									</div>
									<!-- END row -->
								</div>
								<!-- END col-7 -->
								<!-- BEGIN col-5 -->
								<div class="col-xl-5 col-lg-4 align-items-center d-flex justify-content-center">
									<img src="/coloradmin/img/svg/img-1.svg" height="150px" class="d-none d-lg-block" />
								</div>
								<!-- END col-5 -->
							</div>
							<!-- END row -->
						</div>
						<!-- END card-body -->
					</div>
					<!-- END card -->
				</div>
				<!-- END col-6 -->
				<!-- BEGIN col-6 -->
				<div class="col-xl-6">
					<!-- BEGIN row -->
					<div class="row">
						<!-- BEGIN col-6 -->
						<div class="col-sm-6">
							<!-- BEGIN card -->
							<div class="card border-0 text-truncate mb-3 bg-gray-800 text-white">
								<!-- BEGIN card-body -->
								<div class="card-body">
									<!-- BEGIN title -->
									<div class="mb-3 text-gray-500">
										<b class="mb-3">PR COPLETION RATE</b> 
										<span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Conversion Rate" data-bs-placement="top" data-bs-content="Percentage of sessions that resulted in orders from total number of sessions." data-original-title="" title=""></i></span>
									</div>
									<!-- END title -->
									<!-- BEGIN conversion-rate -->
									<div class="d-flex align-items-center mb-1">
										<h2 class="text-white mb-0"><span data-animation="number" data-value="2.19">0.00</span>%</h2>
										<div class="ms-auto">
											<div id="conversion-rate-sparkline"></div>
										</div>
									</div>
									<!-- END conversion-rate -->
									<!-- BEGIN percentage -->
									<div class="mb-4 text-gray-500 ">
										COMPLETED
									</div>
									<!-- END percentage -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-red fs-8px me-2"></i>
											Pending
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="262">0</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="3.79">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-warning fs-8px me-2"></i>
											Void
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="11">0</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="3.85">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
									<!-- BEGIN info-row -->
									<div class="d-flex">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-lime fs-8px me-2"></i>
											Partial
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="57">0</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="2.19">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
								</div>
								<!-- END card-body -->
							</div>
							<!-- END card -->
						</div>
						<!-- END col-6 -->
						<!-- BEGIN col-6 -->
						<div class="col-sm-6">
							<!-- BEGIN card -->
							<div class="card border-0 text-truncate mb-3 bg-gray-800 text-white">
								<!-- BEGIN card-body -->
								<div class="card-body">
									<!-- BEGIN title -->
									<div class="mb-3 text-gray-500">
										<b class="mb-3">PO COMPLETION RATE</b> 
										<span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Store Sessions" data-bs-placement="top" data-bs-content="Number of sessions on your online store. A session is a period of continuous activity from a visitor." data-original-title="" title=""></i></span>
									</div>
									<!-- END title -->
									<!-- BEGIN store-session -->
									<div class="d-flex align-items-center mb-1">
										<h2 class="text-white mb-0"><span data-animation="number" data-value="50.19">0.00</span>%</h2>
										<div class="ms-auto">
											<div id="store-session-sparkline"></div>
										</div>
									</div>
									<!-- END store-session -->
									<!-- BEGIN percentage -->
									<div class="mb-4 text-gray-500 ">
										COMPLETED
									</div>
									<!-- END percentage -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-teal fs-8px me-2"></i>
											Pending
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="25">0.00</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="2.19">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-blue fs-8px me-2"></i>
											Void
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="16">0.00</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="2.19">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
									<!-- BEGIN info-row -->
									<div class="d-flex">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-cyan fs-8px me-2"></i>
											Partial
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small"><span data-animation="number" data-value="7.9">0.00</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span data-animation="number" data-value="2.19">0.00</span>%</div>
										</div>
									</div>
									<!-- END info-row -->
								</div>
								<!-- END card-body -->
							</div>
							<!-- END card -->
						</div>
						<!-- END col-6 -->
					</div>
					<!-- END row -->
				</div>
				<!-- END col-6 -->
			</div>
			<!-- END row -->
			<!-- BEGIN row -->
			<div class="row">
				<!-- BEGIN col-8 -->
				<div class="col-xl-8 col-lg-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<div class="card-body">
							<div class="mb-3 text-gray-500 "><b>VISITORS ANALYTICS</b> <span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Top products with units sold" data-bs-placement="top" data-bs-content="Products with the most individual units sold. Includes orders from all sales channels." data-original-title="" title=""></i></span></div>
							<div class="row">
								<div class="col-xl-3 col-4">
									<h3 class="mb-1"><span data-animation="number" data-value="127.1">0</span>K</h3>
									<div>New Visitors</div>
									<div class="text-gray-500 small text-truncate"><i class="fa fa-caret-up"></i> <span data-animation="number" data-value="25.5">0.00</span>% from previous 7 days</div>
								</div>
								<div class="col-xl-3 col-4">
									<h3 class="mb-1"><span data-animation="number" data-value="179.9">0</span>K</h3>
									<div>Returning Visitors</div>
									<div class="text-gray-500 small text-truncate"><i class="fa fa-caret-up"></i> <span data-animation="number" data-value="5.33">0.00</span>% from previous 7 days</div>
								</div>
								<div class="col-xl-3 col-4">
									<h3 class="mb-1"><span data-animation="number" data-value="766.8">0</span>K</h3>
									<div>Total Page Views</div>
									<div class="text-gray-500 small text-truncate"><i class="fa fa-caret-up"></i> <span data-animation="number" data-value="0.323">0.00</span>% from previous 7 days</div>
								</div>
							</div>
						</div>
						<div class="card-body p-0">
							<div style="height: 269px">
								<div id="visitors-line-chart" class="widget-chart-full-width" data-bs-theme="dark" style="height: 254px"></div>
							</div>
						</div>
					</div>
					<!-- END card -->
				</div>
				<!-- END col-8 -->
				<!-- BEGIN col-4 -->
				<div class="col-xl-4 col-lg-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<div class="card-body">
							<div class="mb-2 text-gray-500">
								<b>EXPENSE BY TYPE</b>
								<span class="ms-2"><i class="fa fa-hand-holding-dollar" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="SAVING COST" data-bs-placement="top"></i></span>
							</div>
							<div id="expense-chart-container" class="mb-2">
								<canvas id="doughnut-chart"></canvas>
							</div>
						</div>
					</div>
					<!-- END card -->
				</div>
				<!-- END col-4 -->
			</div>
			<!-- END row -->
			<!-- BEGIN row -->
			<div class="row">
				<!-- BEGIN col-4 -->
				<div class="col-xl-4 col-lg-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-900 text-white">
						<!-- BEGIN card-body -->
						<div class="card-body" style="background: no-repeat bottom right; background-image: url(/coloradmin/img/svg/img-4.svg); background-size: auto 60%;">
							<!-- BEGIN title -->
							<div class="mb-3 text-gray-500 ">
								<b>SALES BY SOCIAL SOURCE</b>
								<span class="text-gray-500 ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Sales by social source" data-bs-placement="top" data-bs-content="Total online store sales that came from a social referrer source."></i></span>
							</div>
							<!-- END title -->
							<!-- BEGIN sales -->
							<h3 class="mb-10px">$<span data-animation="number" data-value="55547.89">0.00</span></h3>
							<!-- END sales -->
							<!-- BEGIN percentage -->
							<div class="text-gray-500 mb-1px"><i class="fa fa-caret-up"></i> <span data-animation="number" data-value="45.76">0.00</span>% increased</div>
							<!-- END percentage -->
						</div>
						<!-- END card-body -->
						<!-- BEGIN widget-list -->
						<div class="widget-list rounded-bottom" data-bs-theme="dark">
							<!-- BEGIN widget-list-item -->
							<a href="#" class="widget-list-item rounded-0 pt-3px">
								<div class="widget-list-media icon">
									<i class="fab fa-apple bg-indigo text-white"></i>
								</div>
								<div class="widget-list-content">
									<div class="widget-list-title">Apple Store</div>
								</div>
								<div class="widget-list-action text-nowrap text-gray-500">
									$<span data-animation="number" data-value="34840.17">0.00</span>
								</div>
							</a>
							<!-- END widget-list-item -->
							<!-- BEGIN widget-list-item -->
							<a href="#" class="widget-list-item">
								<div class="widget-list-media icon">
									<i class="fab fa-facebook-f bg-blue text-white"></i>
								</div>
								<div class="widget-list-content">
									<div class="widget-list-title">Facebook</div>
								</div>
								<div class="widget-list-action text-nowrap text-gray-500">
									$<span data-animation="number" data-value="12502.67">0.00</span>
								</div>
							</a>
							<!-- END widget-list-item -->
							<!-- BEGIN widget-list-item -->
							<a href="#" class="widget-list-item">
								<div class="widget-list-media icon">
									<i class="fab fa-twitter bg-info text-white"></i>
								</div>
								<div class="widget-list-content">
									<div class="widget-list-title">Twitter</div>
								</div>
								<div class="widget-list-action text-nowrap text-gray-500">
									$<span data-animation="number" data-value="4799.20">0.00</span>
								</div>
							</a>
							<!-- END widget-list-item -->
							<!-- BEGIN widget-list-item -->
							<a href="#" class="widget-list-item">
								<div class="widget-list-media icon">
									<i class="fab fa-google bg-red text-white"></i>
								</div>
								<div class="widget-list-content">
									<div class="widget-list-title">Google Adwords</div>
								</div>
								<div class="widget-list-action text-nowrap text-gray-500">
									$<span data-animation="number" data-value="3405.85">0.00</span>
								</div>
							</a>
							<!-- END widget-list-item -->
							<!-- BEGIN widget-list-item -->
							<a href="#" class="widget-list-item pb-3px rounded-bottom">
								<div class="widget-list-media icon">
									<i class="fab fa-instagram bg-pink text-white"></i>
								</div>
								<div class="widget-list-content">
									<div class="widget-list-title">Instagram</div>
								</div>
								<div class="widget-list-action text-nowrap text-gray-500">
									$<span data-animation="number" data-value="0.00">0.00</span>
								</div>
							</a>
							<!-- END widget-list-item -->
						</div>
						<!-- END widget-list -->
					</div>
					<!-- END card -->
				</div>
				<!-- END col-4 -->
				<!-- END col-4 -->
				<!-- BEGIN col-4 -->
				<div class="col-xl-4 col-lg-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<!-- BEGIN card-body -->
						<div class="card-body">
							<!-- BEGIN title -->
							<div class="mb-3 text-gray-500">
								<b>TOP PRODUCTS</b>
								<span class="ms-2 "><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Top products with units sold" data-bs-placement="top" data-bs-content="Products with the most individual units sold. Includes orders from all sales channels."></i></span>
							</div>
							<!-- END title -->
							<!-- BEGIN product -->
							<div class="d-flex align-items-center mb-15px">
								<div class="widget-img rounded-3 me-10px bg-white p-3px w-30px">
									<div class="h-100 w-100" style="background: url(/coloradmin/img/product/product-8.jpg) center no-repeat; background-size: auto 100%;"></div>
								</div>
								<div class="text-truncate">
									<div >Apple iPhone XR (2023)</div>
									<div class="text-gray-500">$799.00</div>
								</div>
								<div class="ms-auto text-center">
									<div class="fs-13px"><span data-animation="number" data-value="195">0</span></div>
									<div class="text-gray-500 fs-10px">sold</div>
								</div>
							</div>
							<!-- END product -->
							<!-- BEGIN product -->
							<div class="d-flex align-items-center mb-15px">
								<div class="widget-img rounded-3 me-10px bg-white p-3px w-30px">
									<div class="h-100 w-100" style="background: url(/coloradmin/img/product/product-9.jpg) center no-repeat; background-size: auto 100%;"></div>
								</div>
								<div class="text-truncate">
									<div >Apple iPhone XS (2023)</div>
									<div class="text-gray-500">$1,199.00</div>
								</div>
								<div class="ms-auto text-center">
									<div class="fs-13px"><span data-animation="number" data-value="185">0</span></div>
									<div class="text-gray-500 fs-10px">sold</div>
								</div>
							</div>
							<!-- END product -->
							<!-- BEGIN product -->
							<div class="d-flex align-items-center mb-15px">
								<div class="widget-img rounded-3 me-10px bg-white p-3px w-30px">
									<div class="h-100 w-100" style="background: url(/coloradmin/img/product/product-10.jpg) center no-repeat; background-size: auto 100%;"></div>
								</div>
								<div class="text-truncate">
									<div >Apple iPhone XS Max (2023)</div>
									<div class="text-gray-500">$3,399</div>
								</div>
								<div class="ms-auto text-center">
									<div class="fs-13px"><span data-animation="number" data-value="129">0</span></div>
									<div class="text-gray-500 fs-10px">sold</div>
								</div>
							</div>
							<!-- END product -->
							<!-- BEGIN product -->
							<div class="d-flex align-items-center mb-15px">
								<div class="widget-img rounded-3 me-10px bg-white p-3px w-30px">
									<div class="h-100 w-100" style="background: url(/coloradmin/img/product/product-11.jpg) center no-repeat; background-size: auto 100%;"></div>
								</div>
								<div class="text-truncate">
									<div >Huawei Y5 (2023)</div>
									<div class="text-gray-500">$99.00</div>
								</div>
								<div class="ms-auto text-center">
									<div class="fs-13px"><span data-animation="number" data-value="96">0</span></div>
									<div class="text-gray-500 fs-10px">sold</div>
								</div>
							</div>
							<!-- END product -->
							<!-- BEGIN product -->
							<div class="d-flex align-items-center">
								<div class="widget-img rounded-3 me-10px bg-white p-3px w-30px">
									<div class="h-100 w-100" style="background: url(/coloradmin/img/product/product-12.jpg) center no-repeat; background-size: auto 100%;"></div>
								</div>
								<div class="text-truncate">
									<div >Huawei Nova 4 (2023)</div>
									<div class="text-gray-500">$499.00</div>
								</div>
								<div class="ms-auto text-center">
									<div class="fs-13px"><span data-animation="number" data-value="55">0</span></div>
									<div class="text-gray-500 fs-10px">sold</div>
								</div>
							</div>
							<!-- END product -->
						</div>
						<!-- END card-body -->
					</div>
					<!-- END card -->
				</div>
				<!-- END col-4 -->
				<!-- BEGIN col-4 -->
				<div class="col-xl-4 col-lg-6">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<!-- BEGIN card-body -->
						<div class="card-body">
							<!-- BEGIN title -->
							<div class="mb-3 text-gray-500 ">
								<b>MARKETING CAMPAIGN</b>
								<span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Marketing Campaign" data-bs-placement="top" data-bs-content="Campaign that run for getting more returning customers."></i></span>
							</div>
							<!-- END title -->
							<!-- BEGIN row -->
							<div class="row align-items-center pb-1px">
								<!-- BEGIN col-4 -->
								<div class="col-4">
									<div class="h-100px d-flex align-items-center justify-content-center">
										<img src="/coloradmin/img/svg/img-2.svg" class="mw-100 mh-100" />
									</div>
								</div>
								<!-- END col-4 -->
								<!-- BEGIN col-8 -->
								<div class="col-8">
									<div class="mb-2px text-truncate">Email Marketing Campaign</div>
									<div class="mb-2px  text-gray-500  small">Mon 12/6 - Sun 18/6</div>
									<div class="d-flex align-items-center mb-2px">
										<div class="flex-grow-1">
											<div class="progress h-5px rounded-pill bg-white bg-opacity-10">
												<div class="progress-bar progress-bar-striped bg-indigo" data-animation="width" data-value="80%" style="width: 0%"></div>
											</div>
										</div>
										<div class="ms-2 small w-30px text-center"><span data-animation="number" data-value="80">0</span>%</div>
									</div>
									<div class="text-gray-500 small mb-15px text-truncate">
										57.5% people click the email
									</div>
									<a href="#" class="btn btn-xs btn-indigo fs-10px ps-2 pe-2">View campaign</a>
								</div>
								<!-- END col-8 -->
							</div>
							<!-- END row -->
							<hr class=" bg-white bg-opacity-20 mt-20px mb-20px" />
							<!-- BEGIN row -->
							<div class="row align-items-center">
								<!-- BEGIN col-4 -->
								<div class="col-4">
									<div class="h-100px d-flex align-items-center justify-content-center">
										<img src="/coloradmin/img/svg/img-3.svg" class="mw-100 mh-100" />
									</div>
								</div>
								<!-- END col-4 -->
								<!-- BEGIN col-8 -->
								<div class="col-8">
									<div class="mb-2px text-truncate">Facebook Marketing Campaign</div>
									<div class="mb-2px  text-gray-500  small">Sat 10/6 - Sun 18/6</div>
									<div class="d-flex align-items-center mb-2px">
										<div class="flex-grow-1">
											<div class="progress h-5px rounded-pill bg-white bg-opacity-10">
												<div class="progress-bar progress-bar-striped bg-warning" data-animation="width" data-value="60%" style="width: 0%"></div>
											</div>
										</div>
										<div class="ms-2 small w-30px text-center"><span data-animation="number" data-value="60">0</span>%</div>
									</div>
									<div class="text-gray-500 small mb-15px text-truncate">
										+124k visitors from facebook
									</div>
									<a href="#" class="btn btn-xs btn-warning fs-10px ps-2 pe-2">View campaign</a>
								</div>
								<!-- END col-8 -->
							</div>
							<!-- END row -->
						</div>
						<!-- END card-body -->
					</div>
					<!-- END card -->
				</div>
				<!-- END col-4 -->
			</div>
			<!-- END row -->
		<!-- END #content -->

            <!-- BEGIN scroll-top-btn -->

            <!-- END scroll-top-btn -->

        <!-- END #app -->
    </Main>
</template>
