<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/Main.vue'; // Import Main layout

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    card_id: user.card_id || '',
    position: user.position || '',
    campus: user.campus || '',
    division: user.division || '',
    department: user.department || '',
    phone: user.phone || '',
    extension: user.extension || '',
});
</script>

<template>
    <MainLayout> <!-- Use Main layout -->
        <section>
            <header>
                <h2 class="h4">Profile Information</h2>

                <p class="text-muted">
                    Update your account's profile information and email address.
                </p>
            </header>

            <form @submit.prevent="form.patch(route('profile.update'))" class="mt-4">
                <div class="form-floating mb-3">
                    <TextInput
                        id="name"
                        type="text"
                        class="form-control"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Name"
                    />
                    <label for="name">Full Name</label>
                    <InputError class="text-danger mt-2" :message="form.errors.name" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="email"
                        type="email"
                        class="form-control"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Email Address"
                    />
                    <label for="email">Email</label>
                    <InputError class="text-danger mt-2" :message="form.errors.email" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="card_id"
                        type="text"
                        class="form-control"
                        v-model="form.card_id"
                        autocomplete="card_id"
                        placeholder="Card ID"
                    />
                    <label for="card_id">Card ID</label>
                    <InputError class="text-danger mt-2" :message="form.errors.card_id" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="position"
                        type="text"
                        class="form-control"
                        v-model="form.position"
                        autocomplete="position"
                        placeholder="Position"
                    />
                    <label for="position">Position</label>
                    <InputError class="text-danger mt-2" :message="form.errors.position" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="campus"
                        type="text"
                        class="form-control"
                        v-model="form.campus"
                        autocomplete="campus"
                        placeholder="Campus"
                    />
                    <label for="campus">Campus</label>
                    <InputError class="text-danger mt-2" :message="form.errors.campus" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="division"
                        type="text"
                        class="form-control"
                        v-model="form.division"
                        autocomplete="division"
                        placeholder="Division"
                    />
                    <label for="division">Division</label>
                    <InputError class="text-danger mt-2" :message="form.errors.division" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="department"
                        type="text"
                        class="form-control"
                        v-model="form.department"
                        autocomplete="department"
                        placeholder="Department"
                    />
                    <label for="department">Department</label>
                    <InputError class="text-danger mt-2" :message="form.errors.department" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="phone"
                        type="text"
                        class="form-control"
                        v-model="form.phone"
                        autocomplete="phone"
                        placeholder="Phone"
                    />
                    <label for="phone">Phone</label>
                    <InputError class="text-danger mt-2" :message="form.errors.phone" />
                </div>

                <div class="form-floating mb-3">
                    <TextInput
                        id="extension"
                        type="text"
                        class="form-control"
                        v-model="form.extension"
                        autocomplete="extension"
                        placeholder="Extension"
                    />
                    <label for="extension">Extension</label>
                    <InputError class="text-danger mt-2" :message="form.errors.extension" />
                </div>

                <div v-if="mustVerifyEmail && user.email_verified_at === null">
                    <p class="text-muted">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="btn btn-link p-0"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="text-success"
                    >
                        A new verification link has been sent to your email address.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-muted">Saved.</p>
                    </Transition>
                </div>
            </form>
        </section>
    </MainLayout>
</template>
