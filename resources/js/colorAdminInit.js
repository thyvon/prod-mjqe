// import { Inertia } from '@inertiajs/inertia'; // Import Inertia

// export function initializeColorAdmin() {
//     console.log("Initializing Color Admin...");

//     if (typeof App !== "undefined" && typeof App.init === "function") {
//         App.init(); // Initialize the main Color Admin scripts
//         console.log("Color Admin initialized.");
//     }
// }

// Inertia.on('navigate', () => {
//     console.log("Inertia navigation detected, reinitializing Color Admin...");
//     initializeColorAdmin();
// });

import { Inertia } from '@inertiajs/inertia'; // Import Inertia

// Function to initialize Color Admin
export function initializeColorAdmin() {
    console.log("Initializing Color Admin...");

    if (typeof App !== "undefined" && typeof App.init === "function") {
        App.init(); // Initialize the main Color Admin scripts
        console.log("Color Admin initialized.");
    } else {
        console.warn("Color Admin init function is not available.");
    }
}

// Reinitialize Color Admin on every Inertia navigation finish
Inertia.on('finish', () => {
    console.log("Inertia navigation finished. Reinitializing Color Admin...");
    initializeColorAdmin();
});

// Ensure Color Admin initializes on initial page load
// document.addEventListener('DOMContentLoaded', () => {
//     console.log("DOMContentLoaded: Initializing Color Admin...");
//     initializeColorAdmin();
// });
