<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// Inertia form
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Submit login form
const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Redirect to Microsoft Auth route
const loginWithMicrosoft = () => {
    window.location.href = route('auth.microsoft.redirect');
};

// Load external JS script (theme)
onMounted(() => {
    const script = document.createElement('script');
    script.src = '/coloradmin/js/demo/login-v2.demo.js';
    script.onload = () => {
        console.log('login-v2.demo.js initialized from public');
    };
    script.onerror = () => {
        console.error('Failed to load login-v2.demo.js from public');
    };
    document.body.appendChild(script);
});
</script>

<template>
    <Head title="Log in" />
    <link href="/coloradmin/css/vendor.min.css" rel="stylesheet" defer />
    <link href="/coloradmin/css/default/app.min.css" rel="stylesheet" defer />
    <!-- BEGIN login -->
    <div class="login login-v2 fw-bold" data-bs-theme="dark">
        <!-- Cover -->
        <div class="login-cover">
            <div class="login-cover-img" :style="{ backgroundImage: 'url(/coloradmin/img/login-bg/login-bg-17.jpg)' }" data-id="login-cover-image"></div>
            <div class="login-cover-bg"></div>
        </div>

        <!-- Container -->
        <div class="login-container">
            <!-- Header -->
        <div class="login-header justify-content-center d-flex pb-2">
            <div class="brand">
                <div class="d-flex align-items-center justify-content-center flex-column">
                    <img src="https://mjqeducation.edu.kh//storage/photos/MJQEGroupLogo/mjqe-white-logo.png" alt="MJQ Logo" class="logo-size" />
                    <span class="e-purchasing-text mt-2">E-PURCHASING system</span>
                </div>
            </div>
        </div>

            <!-- Content -->
            <div class="login-content">
                <!-- Errors -->
                <div v-if="form.errors.email" class="alert alert-danger">
                    <strong>{{ form.errors.email }}</strong>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit">
                    <!-- Email -->
                    <div class="form-floating mb-20px">
                        <input
                            id="email"
                            type="email"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Email Address"
                        />
                        <label for="email" class="d-flex align-items-center text-gray-600 fs-13px">Email Address</label>
                    </div>

                    <!-- Password -->
                    <div class="form-floating mb-20px">
                        <input
                            id="password"
                            type="password"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />
                        <label for="password" class="d-flex align-items-center text-gray-600 fs-13px">Password</label>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-20px">
                        <input class="form-check-input border-0" type="checkbox" v-model="form.remember" id="rememberMe" />
                        <label class="form-check-label fs-13px text-gray-500" for="rememberMe">Remember Me</label>
                    </div>

                    <!-- Sign in -->
                    <div class="mb-20px">
                        <button type="submit" class="btn btn-theme d-block w-100 h-45px btn-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Sign me in
                        </button>
                    </div>

                    <!-- Microsoft Sign-in Button -->
                    <div class="mb-20px">
                        <button
                            type="button"
                            class="btn btn-outline-primary d-block w-100 h-45px btn-lg"
                            @click="loginWithMicrosoft"
                        >
                            <i class="fab fa-microsoft"></i> Sign in with Microsoft
                        </button>
                    </div>

                    <!-- Register -->
                    <div class="text-gray-500">
                        Not a member yet? Click <Link href="/register" class="text-white">here</Link> to register.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Background selector (unchanged) -->
    <div class="login-bg-list clearfix">
        <div class="login-bg-list-item active"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-17.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-17.jpg)"></a></div>
        <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-16.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-16.jpg)"></a></div>
        <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-15.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-15.jpg)"></a></div>
        <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-14.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-14.jpg)"></a></div>
        <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-13.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-13.jpg)"></a></div>
        <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-12.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-12.jpg)"></a></div>
    </div>
</template>

<style scoped>
.logo-size {
    width: 200px;
    height: auto;
}

.e-purchasing-text {
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 2px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
    font-family: 'TW Cen MT', sans-serif;
}
</style>
