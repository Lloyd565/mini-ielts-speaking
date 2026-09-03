import axios from 'axios';
import { ref } from 'vue';

const TOKEN_KEY = 'ielts_token';

/** Shared auth state: components render off this instead of a store. */
export const token = ref(localStorage.getItem(TOKEN_KEY));

function setToken(value) {
    token.value = value;

    if (value) {
        localStorage.setItem(TOKEN_KEY, value);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    if (token.value) {
        config.headers.Authorization = `Bearer ${token.value}`;
    }

    return config;
});

// A revoked or expired token should drop the user back to the login screen.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            setToken(null);
        }

        return Promise.reject(error);
    },
);

export default {
    /**
     * Register a new account and store the returned API token.
     *
     * @param {string} name
     * @param {string} email
     * @param {string} password
     * @returns {Promise<object>} response envelope
     */
    async register(name, email, password) {
        const response = await api.post('/auth/register', { name, email, password });
        setToken(response.data.data.token);

        return response;
    },

    /**
     * Log in and store the returned API token.
     *
     * @param {string} email
     * @param {string} password
     * @returns {Promise<object>} response envelope
     */
    async login(email, password) {
        const response = await api.post('/auth/login', { email, password });
        setToken(response.data.data.token);

        return response;
    },

    /**
     * Revoke the current token server-side and clear it locally.
     *
     * @returns {Promise<void>}
     */
    async logout() {
        try {
            await api.post('/auth/logout');
        } finally {
            setToken(null);
        }
    },

    /**
     * Fetch speaking questions, optionally filtered by part.
     *
     * @param {string|null} part
     * @returns {Promise<object>} response envelope
     */
    getQuestions(part = null) {
        return api.get('/speaking/questions', { params: part ? { part } : {} });
    },

    /**
     * Submit an answer for evaluation.
     *
     * @param {number} questionId
     * @param {string} answerText
     * @returns {Promise<object>} response envelope
     */
    submitAnswer(questionId, answerText) {
        return api.post('/speaking/submit', {
            question_id: questionId,
            answer_text: answerText,
        });
    },

    /**
     * Fetch a paginated list of past attempts.
     *
     * @param {number} page
     * @param {number} perPage
     * @returns {Promise<object>} response envelope with meta
     */
    getAttempts(page = 1, perPage = 15) {
        return api.get('/speaking/attempts', { params: { page, per_page: perPage } });
    },

    /**
     * Fetch the full detail of a single attempt.
     *
     * @param {number} id
     * @returns {Promise<object>} response envelope
     */
    getAttempt(id) {
        return api.get(`/speaking/attempts/${id}`);
    },
};
