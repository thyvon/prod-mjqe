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
    card_id: '',
    position: '',
    campus: '',
    division: '',
    department: '',
    phone: '',
    extension: '',
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
        <div class="login-container" style="max-width: 900px;">
            <!-- BEGIN login-header -->
        <div class="login-header justify-content-center d-flex pb-2">
            <div class="brand">
                <div class="d-flex align-items-center justify-content-center flex-column">
                    <img src="https://mjqeducation.edu.kh//storage/photos/MJQEGroupLogo/mjqe-white-logo.png" alt="MJQ Logo" class="logo-size" />
                    <span class="e-purchasing-text mt-2">E-PURCHASING system</span>
                </div>
            </div>
        </div>
            <!-- END login-header -->

            <!-- BEGIN login-content -->
            <div class="login-content">
                <form @submit.prevent="submit">
                    <!-- Section 1: Email and Password -->
                    <div class="form-floating mb-20px">
                        <TextInput
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
                        <TextInput
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
                        <TextInput
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

                    <!-- Section 2: Other Fields -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Name"
                                />
                                <label for="name" class="d-flex align-items-center text-gray-600 fs-13px">Full Name</label>
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="card_id"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.card_id"
                                    placeholder="Card ID"
                                />
                                <label for="card_id" class="d-flex align-items-center text-gray-600 fs-13px">Card ID</label>
                                <InputError class="mt-2" :message="form.errors.card_id" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="position"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.position"
                                    placeholder="Position"
                                />
                                <label for="position" class="d-flex align-items-center text-gray-600 fs-13px">Position</label>
                                <InputError class="mt-2" :message="form.errors.position" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="campus"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.campus"
                                    placeholder="Campus"
                                />
                                <label for="campus" class="d-flex align-items-center text-gray-600 fs-13px">Campus</label>
                                <InputError class="mt-2" :message="form.errors.campus" />
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="division"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.division"
                                    placeholder="Division"
                                />
                                <label for="division" class="d-flex align-items-center text-gray-600 fs-13px">Division</label>
                                <InputError class="mt-2" :message="form.errors.division" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="department"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.department"
                                    placeholder="Department"
                                />
                                <label for="department" class="d-flex align-items-center text-gray-600 fs-13px">Department</label>
                                <InputError class="mt-2" :message="form.errors.department" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.phone"
                                    placeholder="Phone"
                                />
                                <label for="phone" class="d-flex align-items-center text-gray-600 fs-13px">Phone</label>
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <div class="form-floating mb-20px">
                                <TextInput
                                    id="extension"
                                    type="text"
                                    class="form-control fs-13px h-45px border-0"
                                    v-model="form.extension"
                                    placeholder="Extension"
                                />
                                <label for="extension" class="d-flex align-items-center text-gray-600 fs-13px">Extension</label>
                                <InputError class="mt-2" :message="form.errors.extension" />
                            </div>
                        </div>
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

