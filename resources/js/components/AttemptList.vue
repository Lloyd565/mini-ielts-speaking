<script setup>
import { onMounted, ref } from 'vue';
import api from '../api.js';

const emit = defineEmits(['select']);

const attempts = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(true);
const errorMessage = ref('');

async function fetchAttempts(targetPage = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.getAttempts(targetPage);
        attempts.value = data.data;
        meta.value = data.meta;
        page.value = data.meta.current_page;
    } catch {
        errorMessage.value = 'Could not load attempts. Please try again.';
    } finally {
        loading.value = false;
    }
}

function statusLabel(status) {
    return { evaluated: 'Evaluated', failed: 'Failed', pending: 'Pending' }[status] ?? status;
}

onMounted(() => fetchAttempts());

defineExpose({ refresh: () => fetchAttempts(page.value) });
</script>

<template>
    <section class="card">
        <h2>History</h2>

        <p v-if="loading" class="muted">Loading attempts…</p>
        <p v-else-if="errorMessage" class="error">{{ errorMessage }}</p>
        <p v-else-if="attempts.length === 0" class="muted">No attempts yet. Submit an answer from the Practice tab.</p>

        <template v-else>
            <table>
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Part</th>
                        <th>Band</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="attempt in attempts" :key="attempt.id">
                        <td>{{ attempt.question?.topic ?? '—' }}</td>
                        <td>{{ attempt.question?.part ?? '—' }}</td>
                        <td>{{ attempt.feedback ? attempt.feedback.band_score.toFixed(1) : '—' }}</td>
                        <td>
                            <span class="badge" :class="attempt.status">{{ statusLabel(attempt.status) }}</span>
                        </td>
                        <td>{{ new Date(attempt.created_at).toLocaleString() }}</td>
                        <td>
                            <button type="button" class="link" @click="emit('select', attempt.id)">View</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <nav v-if="meta && meta.total > meta.per_page" class="pagination">
                <button type="button" :disabled="page <= 1" @click="fetchAttempts(page - 1)">Previous</button>
                <span>Page {{ meta.current_page }} · {{ meta.total }} attempts</span>
                <button
                    type="button"
                    :disabled="page * meta.per_page >= meta.total"
                    @click="fetchAttempts(page + 1)"
                >
                    Next
                </button>
            </nav>
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

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    text-align: left;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

th {
    font-weight: 600;
    color: #475569;
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

.link {
    background: none;
    border: none;
    color: #4f46e5;
    font: inherit;
    cursor: pointer;
    padding: 0;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.pagination button {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    padding: 0.375rem 0.875rem;
    font: inherit;
    cursor: pointer;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.error {
    color: #dc2626;
}

.muted {
    color: #64748b;
}
</style>
