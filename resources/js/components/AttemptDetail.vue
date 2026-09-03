<script setup>
import { onMounted, ref } from 'vue';
import api from '../api.js';

const props = defineProps({
    attemptId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['back']);

const attempt = ref(null);
const loading = ref(true);
const errorMessage = ref('');

async function fetchAttempt() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.getAttempt(props.attemptId);
        attempt.value = data.data;
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'Could not load this attempt.';
    } finally {
        loading.value = false;
    }
}

onMounted(fetchAttempt);
</script>

<template>
    <section class="card">
        <button type="button" class="link" @click="emit('back')">&larr; Back to history</button>

        <p v-if="loading" class="muted">Loading attempt…</p>
        <p v-else-if="errorMessage" class="error">{{ errorMessage }}</p>

        <template v-else-if="attempt">
            <h2>{{ attempt.question.topic }} <span class="part">({{ attempt.question.part }})</span></h2>
            <p class="prompt">{{ attempt.question.prompt }}</p>

            <h3>Your answer</h3>
            <p class="answer">{{ attempt.answer_text }}</p>

            <p>
                Status:
                <span class="badge" :class="attempt.status">{{ attempt.status }}</span>
            </p>

            <template v-if="attempt.feedback">
                <p class="band">Estimated band score: <strong>{{ attempt.feedback.band_score.toFixed(1) }}</strong></p>

                <h3>Strengths</h3>
                <ul>
                    <li v-for="(item, index) in attempt.feedback.strengths" :key="index">{{ item }}</li>
                </ul>

                <h3>Areas to improve</h3>
                <ul>
                    <li v-for="(item, index) in attempt.feedback.areas_to_improve" :key="index">{{ item }}</li>
                </ul>
            </template>

            <p v-else class="muted">No feedback is available for this attempt (evaluation failed or is pending).</p>
        </template>
    </section>
</template>

<style scoped>
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.link {
    background: none;
    border: none;
    color: #4f46e5;
    font: inherit;
    cursor: pointer;
    padding: 0;
    margin-bottom: 1rem;
}

.part {
    color: #64748b;
    font-weight: 400;
    font-size: 0.875em;
}

.prompt {
    background: #f1f5f9;
    border-left: 3px solid #4f46e5;
    border-radius: 0.25rem;
    padding: 0.75rem 1rem;
}

.answer {
    white-space: pre-wrap;
}

.badge {
    display: inline-block;
    border-radius: 9999px;
    padding: 0.125rem 0.625rem;
    font-size: 0.8125rem;
    font-weight: 600;
}

.badge.evaluated {
    background: #dcfce7;
    color: #166534;
}

.badge.failed {
    background: #fee2e2;
    color: #991b1b;
}

.badge.pending {
    background: #fef9c3;
    color: #854d0e;
}

.band {
    font-size: 1.125rem;
}

.error {
    color: #dc2626;
}

.muted {
    color: #64748b;
}
</style>
