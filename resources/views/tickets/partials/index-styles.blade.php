{{-- Page-specific visual polish for Litepicker and horizontal table scrolling. --}}
<style>
    [x-cloak] { display: none !important; }

    .litepicker {
        font-family: inherit;
        font-size: 13px;
        --litepicker-container-months-color-bg: #ffffff;
        --litepicker-month-header-color: #0f172a;
        --litepicker-button-prev-month-color: #64748b;
        --litepicker-button-next-month-color: #64748b;
        --litepicker-button-prev-month-color-hover: #0f172a;
        --litepicker-button-next-month-color-hover: #0f172a;
        --litepicker-month-week-day-color: #94a3b8;
        --litepicker-day-color: #1e293b;
        --litepicker-day-color-hover: #0f172a;
        --litepicker-is-today-color: #2f88d8;
        --litepicker-is-in-range-color: #1e293b;
        --litepicker-is-in-range-color-bg: #dbeafe;
        --litepicker-is-start-color: #ffffff;
        --litepicker-is-start-color-bg: #2f88d8;
        --litepicker-is-end-color: #ffffff;
        --litepicker-is-end-color-bg: #2f88d8;
        --litepicker-button-apply-color-bg: #2f88d8;
        --litepicker-button-cancel-color-bg: #e2e8f0;
        --litepicker-button-cancel-color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.15);
        overflow: hidden;
    }

    .litepicker .container__footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 8px 16px;
    }

    .litepicker .day-item {
        align-items: center;
        color: #1e293b;
        display: flex !important;
        height: 32px !important;
        justify-content: center;
        line-height: 32px !important;
        padding: 0 !important;
        width: 32px !important;
    }

    .litepicker .day-item:hover {
        background-color: #e2e8f0;
        border-radius: 50% !important;
        color: #0f172a;
    }

    .litepicker .day-item.is-today {
        background: transparent;
        border: 1.5px solid #2f88d8;
        border-radius: 50% !important;
        color: #2f88d8;
        font-weight: 700;
    }

    .litepicker .day-item.is-start-date,
    .litepicker .day-item.is-end-date {
        background-color: #2f88d8 !important;
        border: none !important;
        border-radius: 50% !important;
        color: #fff !important;
    }

    .litepicker .day-item.is-in-range {
        background-color: #bfdbfe !important;
        border-radius: 0 !important;
        color: #1e40af !important;
    }

    .lp-shortcuts {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: 10px 16px 14px;
    }

    .lp-shortcut-btn {
        background: none;
        border: none;
        border-radius: 6px;
        color: #2f88d8;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        padding: 4px 10px;
        transition: background 0.15s;
    }

    .lp-shortcut-btn:hover { background: #eff6ff; }

    .ticket-table-scroll {
        scrollbar-color: #94a3b8 #e2e8f0;
        scrollbar-width: thin;
    }

    .ticket-table-scroll::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }

    .ticket-table-scroll::-webkit-scrollbar-track { background: #e2e8f0; }

    .ticket-table-scroll::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 9999px;
    }

    thead button {
        background: transparent;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
    }

    thead button:hover { color: #2f88d8; }
</style>
