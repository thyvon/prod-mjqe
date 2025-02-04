<!-- <script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
 -->

 <script setup>
 import { useForm } from '@inertiajs/vue3';
 import { Head } from '@inertiajs/vue3';
 import { onMounted } from 'vue';
 import { Link } from '@inertiajs/vue3';

 // Create a form using Inertia.js
 const form = useForm({
     email: '',
     password: '',
     remember: false,
 });

 // Form submit method
 const submit = () => {
     form.post(route('login'), {
         onFinish: () => form.reset('password'),
     });
 };

 // Dynamically import the login-v2.demo.js script when the component is mounted
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
     <!-- BEGIN login -->
     <div class="login login-v2 fw-bold" data-bs-theme="dark">
         <!-- BEGIN login-cover -->
         <div class="login-cover">
             <div class="login-cover-img" :style="{ backgroundImage: 'url(/coloradmin/img/login-bg/login-bg-17.jpg)' }" data-id="login-cover-image"></div>
             <div class="login-cover-bg"></div>
         </div>
         <!-- END login-cover -->

         <!-- BEGIN login-container -->
         <div class="login-container">
             <!-- BEGIN login-header -->
             <div class="login-header">
                 <div class="brand">
                     <div class="d-flex align-items-center">
                        <img src="//www.mjqeducation.edu.kh/FrontEnd/Image/logo/mjq-education-single-logo_1.ico" style="width: 50px; height: 50px;" alt="MJQ Logo"> <b>PROD</b> SYSTEM
                     </div>
                     <!-- <small>Bootstrap 5 Responsive Admin Template</small> -->
                 </div>
                 <div class="icon">
                     <i class="fa fa-lock"></i>
                 </div>
             </div>
             <!-- END login-header -->

             <!-- BEGIN login-content -->
             <div class="login-content">
                 <!-- Display error message -->
                 <div v-if="form.errors.email" class="alert alert-danger">
                     <strong>{{ form.errors.email }}</strong>
                 </div>

                 <!-- Login form -->
                 <form @submit.prevent="submit">
                     <!-- Email Field -->
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

                     <!-- Password Field -->
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

                     <!-- Remember Me Checkbox -->
                     <div class="form-check mb-20px">
                         <input class="form-check-input border-0" type="checkbox" v-model="form.remember" id="rememberMe" />
                         <label class="form-check-label fs-13px text-gray-500" for="rememberMe">Remember Me</label>
                     </div>

                     <!-- Sign-in Button -->
                     <div class="mb-20px">
                         <button type="submit" class="btn btn-theme d-block w-100 h-45px btn-lg" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                             Sign me in
                         </button>
                     </div>

                     <!-- Registration Link -->
                     <div class="text-gray-500">
                         Not a member yet? Click <Link href="/register" class="text-white">here</Link> to register.
                     </div>
                 </form>
             </div>
             <!-- END login-content -->
         </div>
         <!-- END login-container -->
     </div>
     <!-- END login -->

     <!-- BEGIN login-bg -->
     <div class="login-bg-list clearfix">
         <div class="login-bg-list-item active"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-17.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-17.jpg)"></a></div>
         <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-16.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-16.jpg)"></a></div>
         <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-15.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-15.jpg)"></a></div>
         <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-14.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-14.jpg)"></a></div>
         <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-13.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-13.jpg)"></a></div>
         <div class="login-bg-list-item"><a href="javascript:;" class="login-bg-list-link" data-toggle="login-change-bg" data-img="/coloradmin/img/login-bg/login-bg-12.jpg" style="background-image: url(/coloradmin/img/login-bg/login-bg-12.jpg)"></a></div>
     </div>
     <!-- END login-bg -->
 </template>

 <style scoped>
  /* Optional styling specific to this component */
 </style>


