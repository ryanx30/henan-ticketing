    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <style>
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
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.15);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .litepicker .container__months {
            background: #ffffff;
        }

        .litepicker .container__footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 8px 16px;
        }

        .litepicker .month-item-header div>.month-item-name,
        .litepicker .month-item-header div>.month-item-year {
            color: #0f172a;
            font-weight: 600;
        }

        .litepicker .day-item {
            color: #1e293b;
            width: 32px !important;
            height: 32px !important;
            line-height: 32px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .litepicker .day-item:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            border-radius: 50% !important;
        }

        .litepicker .day-item.is-today {
            color: #2f88d8;
            font-weight: 700;
            border: 1.5px solid #2f88d8;
            border-radius: 50% !important;
            background: transparent;
        }

        .litepicker .day-item.is-start-date,
        .litepicker .day-item.is-end-date {
            background-color: #2f88d8 !important;
            color: #fff !important;
            border-radius: 50% !important;
            border: none !important;
        }

        .litepicker .day-item.is-in-range {
            background-color: #bfdbfe !important;
            color: #1e40af !important;
            border-radius: 0 !important;
        }
    </style>
