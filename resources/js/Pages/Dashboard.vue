<script setup>
import { onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import { CountUp } from 'countup.js';

const pageTitle = 'Dashboard';
const currentRange = ref('');
const prCount = ref(0);
const completedPercentage = ref(0);
const pendingPRCount = ref(0);
const pendingPercentage = ref(0);
const partialPRCount = ref(0);
const partialPercentage = ref(0);
const voidPRCount = ref(0);
const voidPercentage = ref(0);
const completedPRCount = ref(0);

const poCount = ref(0);
const completedPOPercentage = ref(0);
const pendingPOCount = ref(0);
const pendingPOPercentage = ref(0);
const partialPOCount = ref(0);
const partialPOPercentage = ref(0);
const voidPOCount = ref(0);
const voidPOPercentage = ref(0);
const completedPOCount = ref(0);

const creditSum = ref(0);
const advanceSum = ref(0);
const pettyCashSum = ref(0);
const totalPaid = ref(0);

const purchaseInvoiceData = ref([]);
const columnChart = ref(null);

function formatToK(value) {
    value = Number(value); // Convert value to a number
    if (isNaN(value)) {
        return '0.00'; // Return a default value if it's not a valid number
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K';
    }
    return value.toFixed(2); // Safely call toFixed for numbers below 1000
}

async function fetchPurchaseInvoiceItemData(startDate, endDate) {
    try {
        const response = await fetch(`/dashboard/campus-expense?start_date=${startDate}&end_date=${endDate}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        purchaseInvoiceData.value = data;

        // Update the chart
        updateColumnChart(data);
    } catch (error) {
        console.error('Error fetching purchase invoice item data:', error);
    }
}

function updateColumnChart(data) {
    const labels = data.map(item => item.campus);
    const values = data.map(item => item.total_usd);

    if (columnChart.value) {
        columnChart.value.destroy(); // Destroy the previous chart instance
    }

    const ctx = document.getElementById('column-chart').getContext('2d');
    columnChart.value = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Expense USD',
                    data: values,
                    backgroundColor: 'rgba(229, 231, 233, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'white', // Set legend label color to white
                    },
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Campus',
                        color: 'white', // White text for axis title
                    },
                    ticks: {
                        color: 'white', // White text for axis labels
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)', // Subtle grid lines
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Total Expense (USD)',
                        color: 'white', // White text for axis title
                    },
                    ticks: {
                        color: 'white', // White text for axis labels
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)', // Subtle grid lines
                    },
                    beginAtZero: true,
                },
            },
        },
    });
}

onMounted(async () => {
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
            fetchPRCount(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD')); // Fetch PR count on range change
			fetchPOCount(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            fetchExpenseData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
			fetchPurchaseInvoiceItemData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD')); // Fetch data for the new range
        });
    }

    // Fetch initial PR count
    fetchPRCount(thisYearStart.format('YYYY-MM-DD'), today.format('YYYY-MM-DD'));
	fetchPOCount(thisYearStart.format('YYYY-MM-DD'), today.format('YYYY-MM-DD'));
    fetchExpenseData(thisYearStart.format('YYYY-MM-DD'), today.format('YYYY-MM-DD'));
	fetchPurchaseInvoiceItemData(thisYearStart.format('YYYY-MM-DD'), today.format('YYYY-MM-DD'));

    async function fetchPRCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pr-count?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            prCount.value = data.count || 0;
            fetchCompletedPercentage(startDate, endDate);
            fetchPendingPRCount(startDate, endDate);
            fetchPendingPercentage(startDate, endDate);
			fetchPartialPRCount(startDate, endDate);
			fetchPartialPercentage(startDate, endDate);
			fetchVoidPRCount(startDate, endDate);
			fetchVoidPercentage(startDate, endDate);
			fetcheCompletedPRCount(startDate, endDate);
        } catch (error) {
            console.error('Error fetching PR count:', error);
            prCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPOCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/po-count?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            poCount.value = data.count || 0; // Correctly assign to poCount
            fetchCompletedPOPercentage(startDate, endDate);
            fetchPendingPOCount(startDate, endDate);
            fetchPendingPOPercentage(startDate, endDate);
			fetchPartialPOCount(startDate, endDate);
			fetchPartialPOPercentage(startDate, endDate);
			fetchVoidPOCount(startDate, endDate);
			fetchVoidPOPercentage(startDate, endDate);
			fetcheCompletedPOCount(startDate, endDate);
        } catch (error) {
            console.error('Error fetching PO count:', error);
            poCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetcheCompletedPRCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pr-completed?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            completedPRCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PR count:', error);
            completedPRCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetcheCompletedPOCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/po-completed?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            completedPOCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PO count:', error);
            completedPOCount.value = 0; // Default to 0 in case of an error
        }
    }

    async function fetchCompletedPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/completed-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            completedPercentage.value = data.completed_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching completed percentage:', error);
            completedPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchCompletedPOPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/completed-po-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            completedPOPercentage.value = data.completed_po_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching completed percentage:', error);
            completedPOPercentage.value = 0; // Default to 0 in case of an error
        }
    }

    async function fetchPendingPRCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pr-pending?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            pendingPRCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PR count:', error);
            pendingPRCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPendingPOCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/po-pending?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            pendingPOCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PO count:', error);
            pendingPOCount.value = 0; // Default to 0 in case of an error
        }
    }

    async function fetchPendingPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pending-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            pendingPercentage.value = data.pending_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            pendingPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPendingPOPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pending-po-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            pendingPOPercentage.value = data.pending_po_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            pendingPOPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPartialPRCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pr-partial?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            partialPRCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PR count:', error);
            partialPRCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPartialPOCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/po-partial?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            partialPOCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PO count:', error);
            partialPOCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPartialPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/partial-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            partialPercentage.value = data.partial_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            partialPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchPartialPOPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/partial-po-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            partialPOPercentage.value = data.partial_po_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            partialPOPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchVoidPRCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/pr-void?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            voidPRCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PR count:', error);
            voidPRCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchVoidPOCount(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/po-void?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            voidPOCount.value = data.count || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending PO count:', error);
            voidPOCount.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchVoidPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/void-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            voidPercentage.value = data.partial_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            voidPercentage.value = 0; // Default to 0 in case of an error
        }
    }

	async function fetchVoidPOPercentage(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/void-po-percentage?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            voidPOPercentage.value = data.partial_po_percentage || 0; // Default to 0 if no data is returned
        } catch (error) {
            console.error('Error fetching pending percentage:', error);
            voidPOPercentage.value = 0; // Default to 0 in case of an error
        }
    }

    async function fetchExpenseData(startDate, endDate) {
        try {
            const response = await fetch(`/dashboard/expense-data?start_date=${startDate}&end_date=${endDate}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            creditSum.value = data.credit_sum || 0;
            advanceSum.value = data.advance_sum || 0;
            pettyCashSum.value = data.petty_cash_sum || 0;
			totalPaid.value = data.total_paid || 0;

            // Update the doughnut chart data
            if (window.myDoughnut) {
                window.myDoughnut.data.datasets[0].data = [creditSum.value, advanceSum.value, pettyCashSum.value];
                window.myDoughnut.update();
            }
        } catch (error) {
            console.error('Error fetching expense data:', error);
        }
    }

    const doughnutChartElement = document.getElementById('doughnut-chart');
    if (doughnutChartElement) {
        const ctx6 = doughnutChartElement.getContext('2d');
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

        doughnutChartElement.width = 300; // Set smaller width
        doughnutChartElement.height = 330; // Set smaller height

        window.myDoughnut = new Chart(ctx6, {
            type: 'doughnut',
            data: {
                labels: ['Credit', 'Advance', 'Petty Cash'], // Labels remain the same
                datasets: [{
                    data: [creditSum.value, advanceSum.value, pettyCashSum.value],
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    label: 'Value'
                }]
            },
            options: {
                maintainAspectRatio: false, // Allow custom size
                plugins: {
                    legend: {
                        labels: {
                            color: 'white' // Set label color to white
                        }
                    }
                }
            }
        });
    } else {
        console.warn('Doughnut chart element not found.');
    }

    // Initialize the line chart for Visitors Analytics
    const lastYear = moment().subtract(1, 'year').year();
    const thisYear = moment().year();

    const fetchTotalPaidByMonth = async (year) => {
        try {
            const response = await fetch(`/dashboard/total-paid-by-month?year=${year}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`Error fetching totalPaid for year ${year}:`, error);
            return Array(12).fill(0); // Default to 0 for all months in case of an error
        }
    };

    const fetchExpenseDataByMonth = async (year) => {
        try {
            const response = await fetch(`/dashboard/expense-data-by-month?year=${year}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`Error fetching expense data for year ${year}:`, error);
            return { credit: Array(12).fill(0), advance: Array(12).fill(0), pettyCash: Array(12).fill(0) };
        }
    };

    const lastYearData = await fetchTotalPaidByMonth(lastYear);
    const thisYearData = await fetchTotalPaidByMonth(thisYear);

    const lastYearExpenseData = await fetchExpenseDataByMonth(lastYear);
    const thisYearExpenseData = await fetchExpenseDataByMonth(thisYear);

    // Initialize the chart with fetched data
    const lineChartElement = document.getElementById('line-chart');
    if (lineChartElement) {
        const ctx = lineChartElement.getContext('2d');
        const isDarkTheme = document.body.classList.contains('dark-theme');

        const lineChartColors = isDarkTheme
            ? {
                borderColor1: 'rgba(75, 192, 192, 1)',
                backgroundColor1: 'rgba(75, 192, 192, 0.3)',
                borderColor2: 'rgba(255, 99, 132, 1)',
                backgroundColor2: 'rgba(255, 99, 132, 0.3)',
                borderColor3: 'rgba(54, 162, 235, 1)', // Blue for credit
                backgroundColor3: 'rgba(54, 162, 235, 0.3)',
                borderColor4: 'rgba(255, 206, 86, 1)', // Yellow for advance
                backgroundColor4: 'rgba(255, 206, 86, 0.3)',
                borderColor5: 'rgba(201, 203, 207, 1)', // Gray for petty cash
                backgroundColor5: 'rgba(201, 203, 207, 0.3)',
                gridColor: 'rgba(255, 255, 255, 0.1)',
                fontColor: 'rgba(255, 255, 255, 0.8)',
            }
            : {
                borderColor1: 'rgba(54, 162, 235, 1)',
                backgroundColor1: 'rgba(54, 162, 235, 0.3)',
                borderColor2: 'rgba(255, 206, 86, 1)',
                backgroundColor2: 'rgba(255, 206, 86, 0.3)',
                borderColor3: 'rgba(75, 192, 192, 1)', // Teal for credit
                backgroundColor3: 'rgba(75, 192, 192, 0.3)',
                borderColor4: 'rgba(153, 102, 255, 1)', // Purple for advance
                backgroundColor4: 'rgba(153, 102, 255, 0.3)',
                borderColor5: 'rgba(201, 203, 207, 1)', // Gray for petty cash
                backgroundColor5: 'rgba(201, 203, 207, 0.3)',
                gridColor: 'rgba(128, 128, 128, 1)',
                fontColor: 'rgba(255, 255, 255, 1)',
            };

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                ],
                datasets: [
                    {
                        label: `Total (${lastYear})`,
                        borderColor: lineChartColors.borderColor1,
                        pointBackgroundColor: lineChartColors.borderColor1,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor1,
                        data: lastYearData,
                        hidden: false, // Default to visible
                    },
                    {
                        label: `Total (${thisYear})`,
                        borderColor: lineChartColors.borderColor2,
                        pointBackgroundColor: lineChartColors.borderColor2,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor2,
                        data: thisYearData,
                        hidden: false, // Default to visible
                    },
                    {
                        label: `Credit (${lastYear})`,
                        borderColor: lineChartColors.borderColor3,
                        pointBackgroundColor: lineChartColors.borderColor3,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor3,
                        data: lastYearExpenseData.credit,
                        hidden: true, // Default to hidden
                    },
                    {
                        label: `Credit (${thisYear})`,
                        borderColor: lineChartColors.borderColor4,
                        pointBackgroundColor: lineChartColors.borderColor4,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor4,
                        data: thisYearExpenseData.credit,
                        hidden: true, // Default to hidden
                    },
                    {
                        label: `Advance (${lastYear})`,
                        borderColor: lineChartColors.borderColor5,
                        pointBackgroundColor: lineChartColors.borderColor5,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor5,
                        data: lastYearExpenseData.advance,
                        hidden: true, // Default to hidden
                    },
                    {
                        label: `Advance (${thisYear})`,
                        borderColor: lineChartColors.borderColor2,
                        pointBackgroundColor: lineChartColors.borderColor2,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor2,
                        data: thisYearExpenseData.advance,
                        hidden: true, // Default to hidden
                    },
                    {
                        label: `Petty Cash (${lastYear})`,
                        borderColor: lineChartColors.borderColor3,
                        pointBackgroundColor: lineChartColors.borderColor3,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor3,
                        data: lastYearExpenseData.pettyCash,
                        hidden: true, // Default to hidden
                    },
                    {
                        label: `Petty Cash (${thisYear})`,
                        borderColor: lineChartColors.borderColor4,
                        pointBackgroundColor: lineChartColors.borderColor4,
                        pointRadius: 4,
                        borderWidth: 2,
                        backgroundColor: lineChartColors.backgroundColor4,
                        data: thisYearExpenseData.pettyCash,
                        hidden: true, // Default to hidden
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `Total Expense (${lastYear} Vs ${thisYear})`, // Dynamic title
                        color: lineChartColors.fontColor,
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        labels: {
                            color: lineChartColors.fontColor,
                            boxWidth: 3, // Remove the border by setting box width to 0
                            usePointStyle: true, // Use point style for the legend
                            generateLabels: (chart) => {
                                const originalLabels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                return originalLabels.map((label, index) => ({
                                    ...label,
                                    pointStyle: 'circle', // Use a circle as the legend marker
                                    fillStyle: index % 2 === 0 
                                        ? lineChartColors.borderColor1 // Last year color
                                        : lineChartColors.borderColor2, // This year color
                                }));
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            color: lineChartColors.gridColor,
                        },
                        ticks: {
                            color: lineChartColors.fontColor,
                        },
                    },
                    y: {
                        grid: {
                            color: lineChartColors.gridColor,
                        },
                        ticks: {
                            color: lineChartColors.fontColor,
                        },
                    },
                },
            },
        });
    }
});

// Function to apply CountUp animation
function applyCountUpAnimation(fieldId, value) {
    const element = document.getElementById(fieldId);
    if (element) {
        const countUp = new CountUp(element, value);
        if (!countUp.error) {
            countUp.start();
        } else {
            console.error(countUp.error);
        }
    }
}

// Watch for changes in all fields and apply animation
watch(prCount, (newValue) => applyCountUpAnimation('pr-count', newValue));
watch(completedPercentage, (newValue) => applyCountUpAnimation('completed-percentage', newValue));
watch(pendingPRCount, (newValue) => applyCountUpAnimation('pending-pr-count', newValue));
watch(pendingPercentage, (newValue) => applyCountUpAnimation('pending-percentage', newValue));
watch(partialPRCount, (newValue) => applyCountUpAnimation('partial-pr-count', newValue));
watch(partialPercentage, (newValue) => applyCountUpAnimation('partial-percentage', newValue));
watch(voidPRCount, (newValue) => applyCountUpAnimation('void-pr-count', newValue));
watch(voidPercentage, (newValue) => applyCountUpAnimation('void-percentage', newValue));
watch(completedPRCount, (newValue) => applyCountUpAnimation('completed-pr-count', newValue));

watch(poCount, (newValue) => applyCountUpAnimation('po-count', newValue));
watch(completedPOPercentage, (newValue) => applyCountUpAnimation('completed-po-percentage', newValue));
watch(pendingPOCount, (newValue) => applyCountUpAnimation('pending-po-count', newValue));
watch(pendingPOPercentage, (newValue) => applyCountUpAnimation('pending-po-percentage', newValue));
watch(partialPOCount, (newValue) => applyCountUpAnimation('partial-po-count', newValue));
watch(partialPOPercentage, (newValue) => applyCountUpAnimation('partial-po-percentage', newValue));
watch(voidPOCount, (newValue) => applyCountUpAnimation('void-po-count', newValue));
watch(voidPOPercentage, (newValue) => applyCountUpAnimation('void-po-percentage', newValue));
watch(completedPOCount, (newValue) => applyCountUpAnimation('completed-po-count', newValue));
</script>

<template>
    <Head :title="pageTitle" />
    <Main>

		<!-- BEGIN #content -->
			<!-- BEGIN page-header -->
			<!-- <h1 class="page-header mb-3">Procurement Dashboard</h1> -->
			<!-- END page-header -->
			<!-- BEGIN daterange-filter -->
			<div class="d-sm-flex align-items-center mb-3">
				<a href="#" class="btn btn-dark me-2 text-truncate" id="daterange-filter">
					<i class="fa fa-calendar fa-fw text-white text-opacity-50 ms-n1"></i> 
					 <span>{{ currentRange }}</span>
					<b class="caret ms-1 opacity-5"></b>
				</a>
                <a href="/dashboard" class="btn btn-dark me-2 text-truncate" id="daterange-filter">
					<i class="fa fa-dashboard fa-fw text-white text-opacity-50 ms-n1"></i> 
					 <span> DASHBOARD</span>
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
										<h2 class="mb-0">$<span>{{ formatToK(totalPaid) }}</span></h2>
										<div class="ms-auto mt-n1 mb-n1"><div id="total-sales-sparkline"></div></div>
									</div>
									<!-- END total-sales -->
									<!-- BEGIN percentage -->
									<div class="mb-3 text-gray-500">
										<i class="fa fa-caret-up"></i> <span data-animation="number" data-value="25.5">0.00</span>% from previous 7 days
									</div>
									<hr class="border border-primary" />
									<!-- BEGIN row -->
									<div class="row text-truncate">
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">CREDIT</div>
											<div class="fs-18px mb-5px fw-bold">$<span>{{ formatToK(creditSum) }}</span></div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												 <div 
													class="progress-bar progress-bar-striped rounded-right bg-primary" 
													:style="{ width: `${(creditSum / totalPaid * 100).toFixed(2)}%` }">
												</div>
											</div>
										</div>
										<!-- END col-6 -->
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">ADVANCE</div>
											<div class="fs-18px mb-5px fw-bold">$<span>{{ formatToK(advanceSum) }}</span></div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												 <div 
													class="progress-bar progress-bar-striped rounded-right bg-success" 
													:style="{ width: `${(advanceSum / totalPaid * 100).toFixed(2)}%` }">
												</div>
											</div>
										</div>
										<!-- END col-6 -->
										<!-- BEGIN col-6 -->
										<div class="col-4">
											<div class=" text-gray-500">PETTY CASH</div>
											<div class="fs-18px mb-5px fw-bold">$<span>{{ formatToK(pettyCashSum) }}</span></div>
											<div class="progress h-5px rounded-3 bg-gray-900 mb-5px">
												 <div 
													class="progress-bar progress-bar-striped rounded-right bg-warning" 
													:style="{ width: `${(pettyCashSum / totalPaid * 100).toFixed(2)}%` }">
												</div>
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
										<span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="PR Completion Rate" data-bs-placement="top" data-bs-content="PR completion summary by the date range." data-original-title="" title=""></i></span>
									</div>
									<!-- END title -->
									<!-- BEGIN conversion-rate -->
									<div class="col-12 border-bottom border-premary pb-4">
										<div class="row">
										<div class="col-6">
											<div class="d-flex align-items-center mb-1">
												<h2 class="text-white mb-0">
													 <span id="completed-percentage">0</span>%
												</h2>
											</div>
											<div class="text-gray-500 text-start">
												<i class="fa fa-circle-check text-success fs-15px me-2"></i>
												Completed ( {{ completedPRCount }} )</div>
										</div>
										<div class="col-6">
											<div class="d-flex align-items-center mb-1">
												<h2 class="text-white mb-0">
													 <span id="pr-count">{{ prCount }}</span>
												</h2>
											</div>
											<div class="text-gray-500 text-start">Total PR</div> <!-- Ensure proper placement -->
										</div>
										</div>
									</div>
									<!-- END conversion-rate -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-red fs-8px me-2"></i>
											Pending
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small">
												 <span id="pending-pr-count">{{ pendingPRCount }}</span>
											</div>
											<div class="w-50px text-end ps-2 fw-bold">
												 <span id="pending-percentage">{{ pendingPercentage }}</span>%
											</div>
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
											<div class="text-gray-500 small"><span id="void-pr-count">{{ voidPRCount }}</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span id="void-percentage">{{ voidPercentage }}</span>%</div>
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
											<div class="text-gray-500 small"><span id="partial-pr-count">{{partialPRCount}}</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span id="partial-percentage">{{partialPercentage}}</span>%</div>
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
										<b class="mb-3">PO COPLETION RATE</b> 
										<span class="ms-2"><i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="PO Completion Rate" data-bs-placement="top" data-bs-content="PO completion summary by the date range." data-original-title="" title=""></i></span>
									</div>
									<!-- END title -->
									<!-- BEGIN conversion-rate -->
									<div class="col-12 border-bottom border-premary pb-4">
										<div class="row">
										<div class="col-6">
											<div class="d-flex align-items-center mb-1">
												<h2 class="text-white mb-0">
													 <span id="completed-po-percentage">0</span>%
												</h2>
											</div>
											<div class="text-gray-500 text-start">
												<i class="fa fa-circle-check text-success fs-15px me-2"></i>
												Completed ( {{ completedPOCount }} )</div>
										</div>
										<div class="col-6">
											<div class="d-flex align-items-center mb-1">
												<h2 class="text-white mb-0">
													 <span id="po-count">{{ poCount }}</span>
												</h2>
											</div>
											<div class="text-gray-500 text-start">Total PO</div> <!-- Ensure proper placement -->
										</div>
										</div>
									</div>
									<!-- END conversion-rate -->
									<!-- BEGIN info-row -->
									<div class="d-flex mb-2">
										<div class="d-flex align-items-center">
											<i class="fa fa-circle text-red fs-8px me-2"></i>
											Pending
										</div>
										<div class="d-flex align-items-center ms-auto">
											<div class="text-gray-500 small">
												 <span id="pending-po-count">{{ pendingPOCount }}</span>
											</div>
											<div class="w-50px text-end ps-2 fw-bold">
												 <span id="pending-po-percentage">{{ pendingPOPercentage }}</span>%
											</div>
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
											<div class="text-gray-500 small"><span id="void-po-count">{{ voidPOCount }}</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span id="void-po-percentage">{{ voidPOPercentage }}</span>%</div>
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
											<div class="text-gray-500 small"><span id="partial-po-count">{{partialPOCount}}</span></div>
											<div class="w-50px text-end ps-2 fw-bold"><span id="partial-po-percentage">{{partialPOPercentage}}</span>%</div>
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
				<div class="col-xl-8 col-lg-8">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<div class="card-body">
                            <div class="mb-2 text-gray-500">
								<b>COMPARATION</b>
								<span class="ms-2"><i class="fa fa-hand-holding-dollar" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Expense Comparation" data-bs-placement="top"></i></span>
							</div>
							<div> <!-- Match height with EXPENSE BY TYPE -->
								<canvas id="line-chart" style="height: 337px"></canvas>
							</div>
						</div>
					</div>
					<!-- END card -->
				</div>
				<!-- END col-8 -->
				<!-- BEGIN col-4 -->
				<div class="col-xl-4 col-lg-4">
					<!-- BEGIN card -->
					<div class="card border-0 mb-3 bg-gray-800 text-white">
						<div class="card-body">
							<div class="mb-2 text-gray-500">
								<b>EXPENSE BY TYPE</b>
								<span class="ms-2"><i class="fa fa-hand-holding-dollar" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Expense By Type" data-bs-placement="top"></i></span>
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
				<div class="col-md-8">
						<div class="card border-0 mb-3 bg-gray-800 text-white">
							<div class="card-body">
								<div class="mb-3 text-gray-500">
									<b>PURCHASE EXPENSE BY CAMPUS</b>
									<span class="ms-2">
										<i class="fa fa-info-circle" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-title="Purchase Invoice Data" data-bs-placement="top"></i>
									</span>
								</div>
								<canvas id="column-chart" style="height: 400px; max-height: 400px;"></canvas>
							</div>
						</div>
					</div>
				<!-- END col-12 -->
			</div>
			<!-- END row -->
		<!-- END #content -->

            <!-- BEGIN scroll-top-btn -->

            <!-- END scroll-top-btn -->

        <!-- END #app -->
    </Main>
</template>
