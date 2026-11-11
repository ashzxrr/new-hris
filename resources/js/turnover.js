import Chart from 'chart.js/auto';

const turnoverForm = document.querySelector('form[action*="/turnover/riwayat"]');

if (turnoverForm instanceof HTMLFormElement) {
    const employeeRows = Array.from(turnoverForm.querySelectorAll('.turnover-employee-row'));
    const employeeChecks = employeeRows.map(row => row.querySelector('.turnover-employee-check'));
    const selectedCount = document.getElementById('turnoverSelectedCount');
    const selectAll = document.getElementById('turnoverSelectAll');
    const saveButtons = turnoverForm.querySelectorAll('.turnover-save-button, #turnoverSaveButton');

    const updateEmployeeRow = row => {
        const check = row.querySelector('.turnover-employee-check');
        const enabled = check.checked;
        const reasonSelect = row.querySelector('.turnover-reason-select');
        const otherReason = row.querySelector('.turnover-reason-other');

        row.querySelectorAll('.turnover-employee-field, .turnover-reason-other').forEach(field => {
            field.disabled = !enabled;
        });
        row.querySelector('[name$="[tanggal_resign]"]').required = enabled;
        reasonSelect.required = enabled;
        otherReason.hidden = !enabled || reasonSelect.value !== 'other';
        otherReason.required = enabled && reasonSelect.value === 'other';
    };

    const updateSelection = () => {
        const selected = employeeChecks.filter(check => check.checked).length;
        selectedCount.textContent = String(selected);
        saveButtons.forEach(button => {
            button.disabled = selected === 0;
        });
        selectAll.checked = employeeChecks.length > 0 && selected === employeeChecks.length;
        selectAll.indeterminate = selected > 0 && selected < employeeChecks.length;
    };

    employeeRows.forEach(row => {
        const check = row.querySelector('.turnover-employee-check');
        const reasonSelect = row.querySelector('.turnover-reason-select');

        check.addEventListener('change', () => {
            updateEmployeeRow(row);
            updateSelection();
        });
        reasonSelect.addEventListener('change', () => updateEmployeeRow(row));
        row.addEventListener('click', event => {
            if (event.target.closest('input, select, button, a, label')) {
                return;
            }

            check.checked = !check.checked;
            check.dispatchEvent(new Event('change', { bubbles: true }));
        });
        updateEmployeeRow(row);
    });

    selectAll.addEventListener('change', () => {
        employeeChecks.forEach(check => {
            check.checked = selectAll.checked;
            check.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    updateSelection();
}

const canvas = document.getElementById('chartPergerakan');

if (canvas instanceof HTMLCanvasElement) {
    const rows = JSON.parse(canvas.dataset.months);
    const year = Number(canvas.dataset.year);
    const now = new Date();
    const last = year < now.getFullYear() ? 11 : (year === now.getFullYear() ? now.getMonth() : -1);
    const cur = year === now.getFullYear() ? now.getMonth() : -1;

    const show = key => rows.map((month, index) => index > last ? null : month[key]);
    const masuk = show('masuk');
    const resign = show('resign');
    const net = rows.map((month, index) => index > last ? null : month.masuk - month.resign);
    const peak = Math.max(1, ...masuk.concat(resign).filter(value => value !== null));
    const fade = hex => context => context.dataIndex === cur ? `${hex}66` : hex;
    const sign = value => `${value > 0 ? '+' : ''}${value}`;

    const barLabels = {
        id: 'barLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.font = '500 10px sans-serif';
            ctx.fillStyle = '#567C8D';
            ctx.textAlign = 'center';
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                chart.getDatasetMeta(datasetIndex).data.forEach((bar, index) => {
                    const value = dataset.data[index];
                    if (value === null || value === 0) {
                        return;
                    }

                    ctx.fillText(value, bar.x, bar.y - 5);
                });
            });
            ctx.restore();
        },
    };

    const netRow = {
        id: 'netRow',
        afterDraw(chart) {
            const { ctx, chartArea: { left, bottom }, scales: { x } } = chart;
            const y = bottom + 40;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.font = '600 11px sans-serif';
            net.forEach((value, index) => {
                if (value === null) {
                    return;
                }

                ctx.fillStyle = value > 0 ? '#1B7A4A' : (value < 0 ? '#B91C1C' : '#8BAFC4');
                ctx.fillText(sign(value), x.getPixelForTick(index), y);
            });
            ctx.textAlign = 'right';
            ctx.font = '500 10px sans-serif';
            ctx.fillStyle = '#8BAFC4';
            ctx.fillText('Net', left - 8, y);
            ctx.restore();
        },
    };

    Chart.defaults.font.family = 'inherit';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: rows.map((month, index) => index === cur ? `${month.label} •` : month.label),
            datasets: [
                {
                    label: 'Masuk',
                    data: masuk,
                    backgroundColor: fade('#1B7A4A'),
                    borderRadius: { topLeft: 4, topRight: 4 },
                    minBarLength: 3,
                    maxBarThickness: 22,
                },
                {
                    label: 'Resign',
                    data: resign,
                    backgroundColor: fade('#B91C1C'),
                    borderRadius: { topLeft: 4, topRight: 4 },
                    minBarLength: 3,
                    maxBarThickness: 22,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 16, bottom: 26 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterBody: items => {
                            const value = net[items[0].dataIndex];

                            return value === null ? '' : `Net: ${sign(value)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#567C8D', font: { size: 11 } },
                    border: { color: '#C8D9E6' },
                },
                y: {
                    min: 0,
                    suggestedMax: Math.ceil(peak * 1.12),
                    grid: { color: '#EAF1F6' },
                    border: { display: false },
                    ticks: { color: '#8BAFC4', font: { size: 11 }, precision: 0, maxTicksLimit: 6 },
                },
            },
        },
        plugins: [barLabels, netRow],
    });
}
