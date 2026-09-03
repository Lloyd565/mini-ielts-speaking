<script setup>
import { ref } from 'vue';
import api from '../api.js';

const mode = ref('login');
const name = ref('');
const email = ref('');
const password = ref('');
const submitting = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});

function switchMode() {
    mode.value = mode.value === 'login' ? 'register' : 'login';
    errorMessage.value = '';
    fieldErrors.value = {};
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};

    try {
        if (mode.value === 'login') {
            await api.login(email.value, password.value);
        } else {
            await api.register(name.value, email.value, password.value);
        }
    } catch (error) {
        const response = error.response;
        fieldErrors.value = response?.data?.errors ?? {};
        errorMessage.value = response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <section class="card">
        <h2>{{ mode === 'login' ? 'Log in' : 'Create an account' }}</h2>
        <p class="muted">Your attempts and feedback are private to your account.</p>

        <form @submit.prevent="submit">
            <div v-if="mode === 'register'" class="field">
                <label for="name">Name</label>
                <input id="name" v-model="name" type="text" :disabled="submitting" autocomplete="name" />
                <p v-if="fieldErrors.name" class="error">{{ fieldErrors.name[0] }}</p>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" v-model="email" type="email" :disabled="submitting" autocomplete="email" />
                <p v-if="fieldErrors.email" class="error">{{ fieldErrors.email[0] }}</p>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    :disabled="submitting"
                    :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                />
                <p v-if="fieldErrors.password" class="error">{{ fieldErrors.password[0] }}</p>
            </div>

            <p v-if="errorMessage && !Object.keys(fieldErrors).length" class="error">{{ errorMessage }}</p>

            <button type="submit" :disabled="submitting">
                {{ submitting ? 'Please wait…' : mode === 'login' ? 'Log in' : 'Register' }}
            </button>
        </form>

        <p class="switch">
            {{ mode === 'login' ? 'No account yet?' : 'Already registered?' }}
            <button type="button" class="link" @click="switchMode">
                {{ mode === 'login' ? 'Create one' : 'Log in' }}
            </button>
        </p>
    </section>
</template>

<style scoped>
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    max-width: 26rem;
}

.field {
    margin-bottom: 1rem;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.375rem;
}

input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font: inherit;
    box-sizing: border-box;
}

button[type='submit'] {
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 0.375rem;
    padding: 0.625rem 1.25rem;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.link {
    background: none;
    border: none;
    color: #4f46e5;
    font: inherit;
    font-weight: 600;
    padding: 0;
    cursor: pointer;
    text-decoration: underline;
}

.switch {
    margin-bottom: 0;
}

.error {
    color: #dc2626;
    margin: 0.375rem 0 0;
}

.muted {
    color: #64748b;
    margin-top: 0;
}
</style>
