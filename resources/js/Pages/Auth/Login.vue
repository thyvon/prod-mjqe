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
                        <img src="https://sms.mjqeducation.edu.kh/assets/images/logo/logo-dark.png" alt="MJQ Logo" class="logo-size">
                        <span class="pt-4 ml-2"><b> | PROD</b></span>
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
  .logo-size {
    width: 200px; /* Increased width */
    height: auto; /* Maintain aspect ratio */
}

/* Invert logo colors in dark mode */
[data-bs-theme="dark"] .logo-size {
    filter: invert(1);
}
 </style>


