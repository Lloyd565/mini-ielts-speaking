import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

export default {
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
