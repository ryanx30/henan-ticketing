/**
 * Frontend export queue poller for long-running export jobs.
 * Tracks queued export batches and updates the UI without blocking the main request.
 */

import { apiGet } from './utils/apiClient';

/**
 * Shared queued export helper.
 *
 * Backend export endpoints now return a 202 JSON payload with a queue batch id.
 * This helper prevents the browser from opening that JSON directly. It starts the
 * export request through fetch(), polls the queue status endpoint, and downloads
 * the generated file when the batch is finished.
 */
async function queueExport(url, options = {}) {
    const {
        onQueued = null,
        onReady = null,
        onError = null,
        intervalMs = 1500,
        maxAttempts = 80,
    } = options;

    try {
        const json = await apiGet(url);

        const payload = json.data || {};

        if (!payload.queued || !payload.batch_id || !payload.storage_path || !payload.filename) {
            throw new Error('Invalid queued export response.');
        }

        onQueued?.(payload, json.message || 'Export has been queued.');

        const statusUrl = `/api/exports/${encodeURIComponent(payload.batch_id)}/status?${new URLSearchParams({
            path: payload.storage_path,
            filename: payload.filename,
        }).toString()}`;

        const status = await pollExportStatus(statusUrl, intervalMs, maxAttempts);

        if (!status.ready || !status.download_url) {
            throw new Error('Export is not ready yet. Please try again shortly.');
        }

        onReady?.(status);
        window.dispatchEvent(new CustomEvent('henan:export-ready', { detail: status }));
        window.location.href = status.download_url;

        return status;
    } catch (error) {
        onError?.(error);
        throw error;
    }
}

async function pollExportStatus(url, intervalMs, maxAttempts) {
    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        const json = await apiGet(url);

        const status = json.data || {};

        if (status.failed || status.cancelled) {
            throw new Error('Export failed or was cancelled.');
        }

        if (status.ready) {
            return status;
        }

        await new Promise((resolve) => setTimeout(resolve, intervalMs));
    }

    throw new Error('Export is taking longer than expected. Please check again later.');
}

window.HenanExportQueue = {
    queueExport,
};
