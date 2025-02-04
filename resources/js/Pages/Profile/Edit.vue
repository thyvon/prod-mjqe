<script setup>
import MainLayout from '@/Layouts/Main.vue';
import { Head } from '@inertiajs/vue3';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const profileForm = useForm({
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

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({
    password: '',
});

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const confirmingUserDeletion = ref(false);

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });
};

const deleteUser = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => deleteForm.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
    modal.hide();
    deleteForm.reset();
};
</script>

<template>
    <Head title="Profile" />

    <MainLayout>
        <template #header>
            <h2 class="h4">Profile</h2>
        </template>

        <div class="py-4">
            <div class="container">
                <div class="card mb-4">
                    <div class="card-body">
                        <section>
                            <header>
                                <h2 class="h4">Profile Information</h2>

                                <p class="text-muted">
                                    Update your account's profile information and email address.
                                </p>
                            </header>

                            <form @submit.prevent="profileForm.patch(route('profile.update'))" class="mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="name"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.name"
                                                required
                                                autofocus
                                                autocomplete="name"
                                                placeholder="Name"
                                            />
                                            <label for="name">Full Name</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.name" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="email"
                                                type="email"
                                                class="form-control"
                                                v-model="profileForm.email"
                                                required
                                                autocomplete="username"
                                                placeholder="Email Address"
                                            />
                                            <label for="email">Email</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.email" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="card_id"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.card_id"
                                                autocomplete="card_id"
                                                placeholder="Card ID"
                                            />
                                            <label for="card_id">Card ID</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.card_id" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="position"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.position"
                                                autocomplete="position"
                                                placeholder="Position"
                                            />
                                            <label for="position">Position</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.position" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="campus"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.campus"
                                                autocomplete="campus"
                                                placeholder="Campus"
                                            />
                                            <label for="campus">Campus</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.campus" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="division"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.division"
                                                autocomplete="division"
                                                placeholder="Division"
                                            />
                                            <label for="division">Division</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.division" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="department"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.department"
                                                autocomplete="department"
                                                placeholder="Department"
                                            />
                                            <label for="department">Department</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.department" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="phone"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.phone"
                                                autocomplete="phone"
                                                placeholder="Phone"
                                            />
                                            <label for="phone">Phone</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.phone" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <TextInput
                                                id="extension"
                                                type="text"
                                                class="form-control"
                                                v-model="profileForm.extension"
                                                autocomplete="extension"
                                                placeholder="Extension"
                                            />
                                            <label for="extension">Extension</label>
                                            <InputError class="text-danger mt-2" :message="profileForm.errors.extension" />
                                        </div>
                                    </div>
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
                                    <PrimaryButton :disabled="profileForm.processing">Save</PrimaryButton>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p v-if="profileForm.recentlySuccessful" class="text-muted">Saved.</p>
                                    </Transition>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <section>
                            <header>
                                <h2 class="h4">Update Password</h2>

                                <p class="text-muted">
                                    Ensure your account is using a long, random password to stay secure.
                                </p>
                            </header>

                            <form @submit.prevent="updatePassword" class="mt-4">
                                <div class="mb-3">
                                    <InputLabel for="current_password" value="Current Password" />

                                    <TextInput
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        class="form-control"
                                        autocomplete="current-password"
                                    />

                                    <InputError :message="passwordForm.errors.current_password" class="text-danger mt-2" />
                                </div>

                                <div class="mb-3">
                                    <InputLabel for="new_password" value="New Password" />

                                    <TextInput
                                        id="new_password"
                                        ref="passwordInput"
                                        v-model="passwordForm.password"
                                        type="password"
                                        class="form-control"
                                        autocomplete="new-password"
                                    />

                                    <InputError :message="passwordForm.errors.password" class="text-danger mt-2" />
                                </div>

                                <div class="mb-3">
                                    <InputLabel for="password_confirmation" value="Confirm Password" />

                                    <TextInput
                                        id="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        class="form-control"
                                        autocomplete="new-password"
                                    />

                                    <InputError :message="passwordForm.errors.password_confirmation" class="text-danger mt-2" />
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <PrimaryButton :disabled="passwordForm.processing">Save</PrimaryButton>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p v-if="passwordForm.recentlySuccessful" class="text-muted">Saved.</p>
                                    </Transition>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <section class="space-y-6">
                            <header>
                                <h2 class="h4">Delete Account</h2>

                                <p class="text-muted">
                                    Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting
                                    your account, please download any data or information that you wish to retain.
                                </p>
                            </header>

                            <DangerButton @click="confirmUserDeletion">Delete Account</DangerButton>

                            <!-- Bootstrap Modal -->
                            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Delete Account</h5>
                                            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted">
                                                Once your account is deleted, all of its resources and data will be permanently deleted. Please
                                                enter your password to confirm you would like to permanently delete your account.
                                            </p>
                                            <div class="mt-3">
                                                <InputLabel for="delete_password" value="Password" class="sr-only" />

                                                <TextInput
                                                    id="delete_password"
                                                    ref="passwordInput"
                                                    v-model="deleteForm.password"
                                                    type="password"
                                                    class="form-control"
                                                    placeholder="Password"
                                                    @keyup.enter="deleteUser"
                                                />

                                                <InputError :message="deleteForm.errors.password" class="text-danger mt-2" />
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                                            <DangerButton
                                                class="ms-3"
                                                :class="{ 'opacity-25': deleteForm.processing }"
                                                :disabled="deleteForm.processing"
                                                @click="deleteUser"
                                            >
                                                Delete Account
                                            </DangerButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
