<script setup>
import { onMounted, ref } from 'vue';
import api from '../api.js';

const emit = defineEmits(['submitted']);

const questions = ref([]);
const selectedQuestionId = ref(null);
const answerText = ref('');
const loadingQuestions = ref(true);
const submitting = ref(false);
const feedback = ref(null);
const errorMessage = ref('');
const fieldErrors = ref({});

const minAnswerLength = 20;

async function fetchQuestions() {
    loadingQuestions.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.getQuestions();
        questions.value = data.data;

        if (questions.value.length > 0) {
            selectedQuestionId.value = questions.value[0].id;
        }
    } catch {
        errorMessage.value = 'Could not load questions. Please refresh the page.';
    } finally {
        loadingQuestions.value = false;
    }
}

async function submit() {
    submitting.value = true;
    feedback.value = null;
    errorMessage.value = '';
    fieldErrors.value = {};

    try {
        const { data } = await api.submitAnswer(selectedQuestionId.value, answerText.value);
        feedback.value = data.data;
        answerText.value = '';
        emit('submitted');
    } catch (error) {
        const response = error.response;

        if (response?.status === 422) {
            fieldErrors.value = response.data.errors ?? {};
            errorMessage.value = response.data.message ?? 'The given data was invalid.';
        } else {
            errorMessage.value = response?.data?.message ?? 'Something went wrong. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(fetchQuestions);
</script>

<template>
    <section class="card">
        <h2>Practice</h2>

        <p v-if="loadingQuestions" class="muted">Loading questions…</p>

        <template v-else>
            <div class="field">
                <label for="question">Question</label>
                <select id="question" v-model="selectedQuestionId" :disabled="submitting">
                    <option v-for="question in questions" :key="question.id" :value="question.id">
                        [{{ question.part }}] {{ question.topic }}
                    </option>
                </select>
            </div>

            <div v-if="selectedQuestionId" class="prompt">
                <p>{{ questions.find((q) => q.id === selectedQuestionId)?.prompt }}</p>
            </div>

            <div class="field">
                <label for="answer">Your answer (min {{ minAnswerLength }} characters)</label>
                <textarea
                    id="answer"
                    v-model="answerText"
                    rows="8"
                    :disabled="submitting"
                    placeholder="Type your answer here…"
                ></textarea>
                <p v-if="fieldErrors.answer_text" class="error">{{ fieldErrors.answer_text[0] }}</p>
            </div>

            <p v-if="errorMessage && !fieldErrors.answer_text" class="error">{{ errorMessage }}</p>

            <button type="button" :disabled="submitting || !selectedQuestionId" @click="submit">
                {{ submitting ? 'Evaluating…' : 'Submit for evaluation' }}
            </button>
        </template>

        <div v-if="feedback" class="feedback">
            <h3>Feedback</h3>
            <p class="band">Estimated band score: <strong>{{ feedback.feedback.band_score.toFixed(1) }}</strong></p>

            <h4>Strengths</h4>
            <ul>
                <li v-for="(item, index) in feedback.feedback.strengths" :key="index">{{ item }}</li>
            </ul>

            <h4>Areas to improve</h4>
            <ul>
                <li v-for="(item, index) in feedback.feedback.areas_to_improve" :key="index">{{ item }}</li>
            </ul>
        </div>
    </section>
</template>

<style scoped>
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.field {
    margin-bottom: 1rem;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.375rem;
}

select,
textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font: inherit;
}

.prompt {
    background: #f1f5f9;
    border-left: 3px solid #4f46e5;
    border-radius: 0.25rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
}

button {
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

.error {
    color: #dc2626;
}

.muted {
    color: #64748b;
}

.feedback {
    margin-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
}

.band {
    font-size: 1.125rem;
}
</style>
