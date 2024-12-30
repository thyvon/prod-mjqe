<!-- <script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template> -->


<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />
    <!-- BEGIN registration -->
    <div class="login login-v2 fw-bold" data-bs-theme="dark">
        <!-- BEGIN login-cover -->
        <div class="login-cover">
            <div class="login-cover-img" :style="{ backgroundImage: 'url(/coloradmin/img/login-bg/login-bg-17.jpg)' }"></div>
            <div class="login-cover-bg"></div>
        </div>
        <!-- END login-cover -->

        <!-- BEGIN login-container -->
        <div class="login-container">
            <!-- BEGIN login-header -->
            <div class="login-header">
                <div class="brand">
                    <div class="d-flex align-items-center">
                        <span class="logo"></span> <b>PROD</b> SYSTEM
                    </div>
                </div>
                <div class="icon">
                    <i class="fa fa-user"></i>
                </div>
            </div>
            <!-- END login-header -->

            <!-- BEGIN login-content -->
            <div class="login-content">
                <form @submit.prevent="submit">
                    <div class="form-floating mb-20px">
                        <input
                            id="name"
                            type="text"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Name"
                        />
                        <label for="name" class="d-flex align-items-center text-gray-600 fs-13px">Confirm</label>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="form-floating mb-20px">
                        <input
                            id="email"
                            type="email"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email Address"
                        />
                        <label for="email" class="d-flex align-items-center text-gray-600 fs-13px">Email</label>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="form-floating mb-20px">
                        <input
                            id="password"
                            type="password"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="Password"
                        />
                        <label for="password" class="d-flex align-items-center text-gray-600 fs-13px">Password</label>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="form-floating mb-20px">
                        <input
                            id="password_confirmation"
                            type="password"
                            class="form-control fs-13px h-45px border-0"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Confirm Password"
                        />
                        <label for="password_confirmation" class="d-flex align-items-center text-gray-600 fs-13px">Confirm</label>
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <Link
                            :href="route('login')"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md"
                        >
                            Already registered?
                        </Link>

                        <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Register
                        </PrimaryButton>
                    </div>
                </form>
            </div>
            <!-- END login-content -->
        </div>
        <!-- END login-container -->
    </div>
    <!-- END registration -->
</template>

<style scoped>
/* Optional styling specific to this component */
</style>

