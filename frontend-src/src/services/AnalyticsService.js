import axiosInstance from "./AxiosInstance";

/**
 * "Аналитика" (KPI, диаграммы, геоотчёт) и "Сводный отчёт (pivot)".
 * Backend: app/Http/Controllers/API/v1/Analytics/AnalyticsController.php
 */
export default {
    meta: (dataType) => axiosInstance.get(`/analytics/meta?dataType=${dataType}`),
    summary: (data) => axiosInstance.post('/analytics/summary', data),
    top: (data) => axiosInstance.post('/analytics/top', data),
    geo: (data) => axiosInstance.post('/analytics/geo', data),
    pivot: (data) => axiosInstance.post('/analytics/pivot', data, { timeout: 5 * 60 * 1000 }),
    plan: (data) => axiosInstance.post('/analytics/pivot/plan', data),
};
