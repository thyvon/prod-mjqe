<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import MainLayout from '@/Layouts/Main.vue'; // Import Main layout

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <MainLayout> <!-- Use Main layout -->
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
                        v-model="form.current_password"
                        type="password"
                        class="form-control"
                        autocomplete="current-password"
                    />

                    <InputError :message="form.errors.current_password" class="text-danger mt-2" />
                </div>

                <div class="mb-3">
                    <InputLabel for="password" value="New Password" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                    />

                    <InputError :message="form.errors.password" class="text-danger mt-2" />
                </div>

                <div class="mb-3">
                    <InputLabel for="password_confirmation" value="Confirm Password" />

                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                    />

                    <InputError :message="form.errors.password_confirmation" class="text-danger mt-2" />
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
