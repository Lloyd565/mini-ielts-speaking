<script setup>
import { ref } from 'vue';
import AttemptDetail from './components/AttemptDetail.vue';
import AttemptList from './components/AttemptList.vue';
import SubmitAnswerForm from './components/SubmitAnswerForm.vue';

const activeTab = ref('practice');
const selectedAttemptId = ref(null);
const attemptList = ref(null);

function showHistory() {
    activeTab.value = 'history';
    selectedAttemptId.value = null;
}

function onSubmitted() {
    showHistory();
    attemptList.value?.refresh();
}

function onSelectAttempt(id) {
    selectedAttemptId.value = id;
}
</script>

<template>
    <div class="layout">
        <header>
            <h1>Mini IELTS Speaking</h1>
            <nav class="tabs">
                <button
                    type="button"
                    :class="{ active: activeTab === 'practice' }"
                    @click="activeTab = 'practice'"
                >
                    Practice
                </button>
                <button
                    type="button"
                    :class="{ active: activeTab === 'history' }"
                    @click="showHistory"
                >
                    History
                </button>
            </nav>
        </header>

        <main>
            <SubmitAnswerForm v-if="activeTab === 'practice'" @submitted="onSubmitted" />

            <template v-else>
                <AttemptDetail
                    v-if="selectedAttemptId"
                    :attempt-id="selectedAttemptId"
                    @back="selectedAttemptId = null"
                />
                <AttemptList v-else ref="attemptList" @select="onSelectAttempt" />
            </template>
        </main>
    </div>
</template>

<style>
body {
    background: #f8fafc;
    color: #0f172a;
    margin: 0;
}

.layout {
    max-width: 56rem;
    margin: 0 auto;
    padding: 2rem 1rem;
}

header h1 {
    margin: 0 0 1rem;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.tabs button {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    padding: 0.5rem 1.25rem;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.tabs button.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #fff;
}
</style>
