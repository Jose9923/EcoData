import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const numberFormat = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 4 });
const intervalFormat = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 8 });
const shortDateFormat = new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short', timeZone: 'UTC' });
const groupNames = { day: 'día', week: 'semana', month: 'mes' };
const isoDate = (date) => date.toISOString().slice(0, 10);
const dateValue = (value) => new Date(value + 'T00:00:00Z');
const numeric = (value) => value === null || value === undefined ? null : Number(value);
const format = (value) => value === null || value === undefined ? 'Sin datos' : numberFormat.format(value);
const fullRange = (from, to) => from === to ? from : from + ' a ' + to;

function groupKey(date, grouping) {
    if (grouping === 'month') return date.slice(0, 7);
    if (grouping !== 'week') return date;
    const monday = dateValue(date);
    monday.setUTCDate(monday.getUTCDate() - (monday.getUTCDay() + 6) % 7);
    return isoDate(monday);
}

export function groupTrend(trend, grouping = 'day') {
    const groups = new Map();
    for (const point of trend) {
        const key = groupKey(point.date, grouping);
        if (!groups.has(key)) {
            groups.set(key, {
                from: point.date, to: point.date,
                previousFrom: point.previousDate, previousTo: point.previousDate,
                count: 0, total: 0, minimum: null, maximum: null,
                previousCount: 0, previousTotal: 0, previousMinimum: null, previousMaximum: null,
            });
        }
        const group = groups.get(key);
        group.to = point.date;
        group.previousTo = point.previousDate;
        for (const prefix of ['', 'previous']) {
            const keyFor = (name) => prefix ? prefix + name[0].toUpperCase() + name.slice(1) : name;
            const count = Number(point[keyFor('count')]) || 0;
            const average = numeric(point[keyFor('average')]);
            if (!count || average === null) continue;
            group[keyFor('count')] += count;
            group[keyFor('total')] += average * count;
            for (const metric of ['minimum', 'maximum']) {
                const value = numeric(point[keyFor(metric)]);
                if (value === null) continue;
                const old = group[keyFor(metric)];
                group[keyFor(metric)] = old === null ? value : (metric === 'minimum' ? Math.min(old, value) : Math.max(old, value));
            }
        }
    }
    return Array.from(groups.values(), (group) => ({
        ...group,
        average: group.count ? group.total / group.count : null,
        previousAverage: group.previousCount ? group.previousTotal / group.previousCount : null,
    }));
}

function themeColor() {
    const value = getComputedStyle(document.documentElement).getPropertyValue('--school-primary').trim() || '#16805d';
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const context = canvas.getContext('2d');
    context.fillStyle = value;
    context.fillRect(0, 0, 1, 1);
    const [red, green, blue] = context.getImageData(0, 0, 1, 1).data;
    return { solid: value, light: 'rgba(' + [red, green, blue, .18].join(',') + ')', bar: 'rgba(' + [red, green, blue, .7].join(',') + ')' };
}

function baseOptions(unit = '') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        locale: 'es-CO',
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: { padding: 12, boxPadding: 4 },
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
            y: {
                grid: { color: 'rgba(100,116,139,.14)' },
                ticks: { color: '#64748b', callback: (value) => numberFormat.format(value) },
                title: { display: Boolean(unit), text: unit, color: '#475569' },
            },
        },
    };
}

function tableColumns(compare) {
    return ['Fecha / grupo', 'Mediciones', 'Promedio', 'Mínimo', 'Máximo',
        ...(compare ? ['Fechas anteriores', 'Mediciones anteriores', 'Promedio anterior'] : [])];
}

function tableValues(group, compare, display = true) {
    const value = display ? format : (number) => number === null ? '' : Number(number.toFixed(4));
    return [fullRange(group.from, group.to), group.count, value(group.average), value(group.minimum), value(group.maximum),
        ...(compare ? [fullRange(group.previousFrom, group.previousTo), group.previousCount, value(group.previousAverage)] : [])];
}

function renderTable(groups, compare, grouping, variable) {
    const table = document.getElementById('chart-data-table');
    const head = table.tHead.rows[0];
    head.replaceChildren();
    for (const label of tableColumns(compare)) {
        const cell = document.createElement('th');
        cell.scope = 'col';
        cell.textContent = label;
        head.append(cell);
    }
    const fragment = document.createDocumentFragment();
    for (const group of groups) {
        const row = document.createElement('tr');
        tableValues(group, compare).forEach((value, index) => {
            const cell = document.createElement(index === 0 ? 'th' : 'td');
            if (index === 0) cell.scope = 'row';
            cell.textContent = String(value);
            row.append(cell);
        });
        fragment.append(row);
    }
    document.getElementById('chart-data-body').replaceChildren(fragment);
    document.getElementById('chart-data-caption').textContent =
        'Promedios por ' + groupNames[grouping] + ' de ' + variable.name + ' (' + (variable.unit || 'sin unidad') +
        '). Sin datos indica que no hay mediciones en ese grupo.';
}

function csvCell(value) {
    if (typeof value === 'number') return String(value);
    let text = String(value ?? '');
    if (/^[\s]*[=+\-@]/.test(text) || /^[\t\r\n]/.test(text)) text = "'" + text;
    return '"' + text.replaceAll('"', '""') + '"';
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function slug(value) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').slice(0, 70) || 'mediciones';
}

function wrapText(context, text, maxWidth) {
    const lines = [];
    let line = '';
    for (const word of String(text).split(/\s+/)) {
        const candidate = line ? line + ' ' + word : word;
        if (line && context.measureText(candidate).width > maxWidth) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }
    if (line) lines.push(line);
    return lines;
}

function exportPng(chart, title, metadata, filename) {
    const canvas = document.createElement('canvas');
    const width = Math.max(1200, chart.canvas.width);
    const padding = 48;
    const ratio = width / Math.max(1, chart.canvas.width);
    canvas.width = width;
    const context = canvas.getContext('2d');
    context.font = '600 28px Arial';
    const titleLines = wrapText(context, title, width - padding * 2);
    context.font = '18px Arial';
    const metaLines = metadata.flatMap((line) => wrapText(context, line, width - padding * 2));
    const headingHeight = padding + titleLines.length * 36 + 14 + metaLines.length * 27 + 24;
    canvas.height = Math.ceil(headingHeight + chart.canvas.height * ratio + padding);
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = '#1e293b';
    context.font = '600 28px Arial';
    let y = padding + 24;
    for (const line of titleLines) {
        context.fillText(line, padding, y, width - padding * 2);
        y += 36;
    }
    y += 10;
    context.fillStyle = '#475569';
    context.font = '18px Arial';
    for (const line of metaLines) {
        context.fillText(line, padding, y, width - padding * 2);
        y += 27;
    }
    context.drawImage(chart.canvas, 0, headingHeight, width, chart.canvas.height * ratio);
    canvas.toBlob((blob) => {
        if (!blob) {
            document.getElementById('chart-export-status').textContent = 'No se pudo crear la imagen. Intenta descargarla de nuevo.';
            return;
        }
        downloadBlob(blob, filename);
        document.getElementById('chart-export-status').textContent = 'Imagen PNG preparada para descargar.';
    }, 'image/png');
}

export function initCharts() {
    const root = document.getElementById('measurement-charts');
    const payload = document.getElementById('measurement-chart-data');
    if (!root || root.dataset.chartsReady === 'true') return;
    document.getElementById('report-variable')?.addEventListener('change', () => {
        const station = document.getElementById('report-station');
        if (!station) return;
        station.value = '';
        station.dispatchEvent(new Event('change', { bubbles: true }));
        window.ecodataSearchableSelects?.refresh(station);
    });
    if (!payload) return;
    const data = JSON.parse(payload.textContent);
    const grouping = ['day', 'week', 'month'].includes(root.dataset.groupBy) ? root.dataset.groupBy : 'day';
    const chartType = root.dataset.chartType === 'bar' ? 'bar' : 'line';
    const groups = groupTrend(data.trend, grouping);
    const colors = themeColor();
    const unit = data.variable.unit || 'sin unidad';
    const compareToggle = document.getElementById('compare-period');
    const limitsToggle = document.getElementById('show-limits');
    const labels = groups.map((group) => group.from === group.to ? shortDateFormat.format(dateValue(group.from)) :
        shortDateFormat.format(dateValue(group.from)) + ' – ' + shortDateFormat.format(dateValue(group.to)));
    const compare = () => Boolean(compareToggle?.checked);
    const showLimits = () => Boolean(limitsToggle?.checked);

    const trendDatasets = () => {
        const datasets = [{
            label: 'Período seleccionado',
            data: groups.map((point) => point.average),
            borderColor: colors.solid, backgroundColor: chartType === 'bar' ? colors.bar : colors.light,
            borderWidth: 2.5, pointRadius: groups.length > 60 ? 1.5 : 3.5, pointHoverRadius: 6,
            pointHitRadius: 12, tension: 0, spanGaps: false, fill: false, maxBarThickness: 42,
        }];
        if (compare()) {
            datasets.push({
                label: 'Período anterior',
                data: groups.map((point) => point.previousAverage),
                borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.35)',
                borderDash: [6, 4], borderWidth: 2, pointRadius: groups.length > 60 ? 1 : 3,
                pointStyle: 'rectRot', pointHitRadius: 12, tension: 0, spanGaps: false, fill: false,
                maxBarThickness: 42, previous: true,
            });
        }
        if (showLimits()) {
            for (const [key, label] of [['min', 'Límite mínimo'], ['max', 'Límite máximo']]) {
                const value = numeric(data.variable[key]);
                if (value === null) continue;
                datasets.push({
                    type: 'line', label: label + ': ' + format(value) + ' ' + unit,
                    data: groups.map(() => value), borderColor: '#b45309', backgroundColor: '#b45309',
                    borderDash: [3, 5], borderWidth: 1.5, pointRadius: 0, fill: false, reference: true,
                });
            }
        }
        return datasets;
    };

    const trendOptions = baseOptions(unit);
    trendOptions.scales.y.beginAtZero = chartType === 'bar';
    trendOptions.plugins.legend = {
        display: true, position: 'bottom',
        labels: { usePointStyle: true, boxWidth: 8, padding: 18, color: '#475569' },
        onClick: () => {},
    };
    trendOptions.plugins.tooltip = {
        ...trendOptions.plugins.tooltip,
        filter: (item) => !item.dataset.reference,
        callbacks: {
            title: (items) => items.length ? fullRange(groups[items[0].dataIndex].from, groups[items[0].dataIndex].to) : '',
            label: (item) => {
                const point = groups[item.dataIndex];
                const previous = Boolean(item.dataset.previous);
                return (previous ? 'Anterior' : 'Actual') + ': ' + format(previous ? point.previousAverage : point.average) + ' ' + unit;
            },
            afterLabel: (item) => {
                const point = groups[item.dataIndex];
                const previous = Boolean(item.dataset.previous);
                return [
                    ...(previous ? ['Fechas: ' + fullRange(point.previousFrom, point.previousTo)] : []),
                    'Mediciones: ' + (previous ? point.previousCount : point.count),
                    'Mínimo: ' + format(previous ? point.previousMinimum : point.minimum) + ' ' + unit,
                    'Máximo: ' + format(previous ? point.previousMaximum : point.maximum) + ' ' + unit,
                ];
            },
        },
    };
    const trend = new Chart(document.getElementById('trend-chart'), {
        type: chartType, data: { labels, datasets: trendDatasets() }, options: trendOptions,
    });

    const stationRows = [...data.stations].sort((a, b) => b.count - a.count || a.name.localeCompare(b.name, 'es')).slice(0, 20);
    document.getElementById('station-chart-wrap').style.height = Math.max(304, stationRows.length * 42 + 55) + 'px';
    if (data.stations.length > stationRows.length) {
        const note = document.createElement('p');
        note.className = 'small text-secondary mt-2 mb-0';
        note.textContent = 'Se muestran las 20 estaciones con más mediciones. La tabla incluye todas las estaciones.';
        document.getElementById('station-chart-wrap').parentElement.after(note);
    }
    const stationOptions = baseOptions();
    stationOptions.indexAxis = 'y';
    stationOptions.interaction = { intersect: false, mode: 'nearest', axis: 'y' };
    stationOptions.scales.x = {
        beginAtZero: true, title: { display: true, text: unit, color: '#475569' },
        grid: { color: 'rgba(100,116,139,.14)' }, ticks: { maxTicksLimit: 5, callback: (value) => numberFormat.format(value) },
    };
    stationOptions.scales.y = {
        grid: { display: false },
        ticks: {
            autoSkip: false, color: '#475569', font: { size: 11 },
            callback: function (value) {
                const name = this.getLabelForValue(value);
                const limit = root.clientWidth < 450 ? 19 : 27;
                return name.length > limit ? name.slice(0, limit - 1) + '…' : name;
            },
        },
    };
    stationOptions.plugins.tooltip.callbacks = {
        title: (items) => items.length ? stationRows[items[0].dataIndex].name : '',
        label: (item) => 'Promedio: ' + format(stationRows[item.dataIndex].average) + ' ' + unit,
        afterLabel: (item) => {
            const row = stationRows[item.dataIndex];
            return ['Mediciones: ' + row.count, 'Mínimo: ' + format(row.minimum) + ' ' + unit, 'Máximo: ' + format(row.maximum) + ' ' + unit];
        },
    };
    const stations = new Chart(document.getElementById('station-chart'), {
        type: 'bar',
        data: { labels: stationRows.map((row) => row.name), datasets: [{ label: 'Promedio', data: stationRows.map((row) => row.average), backgroundColor: colors.bar, borderColor: colors.solid, borderWidth: 1, borderRadius: 4, maxBarThickness: 26 }] },
        options: stationOptions,
    });

    const intervalLabel = (interval) => interval.from === interval.to ? intervalFormat.format(interval.from) :
        intervalFormat.format(interval.from) + ' – ' + intervalFormat.format(interval.to);
    const distributionOptions = baseOptions('Mediciones');
    distributionOptions.scales.y.beginAtZero = true;
    distributionOptions.scales.y.ticks.precision = 0;
    distributionOptions.scales.x.title = { display: true, text: unit, color: '#475569' };
    distributionOptions.scales.x.ticks = { ...distributionOptions.scales.x.ticks, maxRotation: 35, maxTicksLimit: 5, font: { size: 10 } };
    distributionOptions.plugins.tooltip.callbacks = {
        title: (items) => items.length ? intervalLabel(data.distribution[items[0].dataIndex]) + ' ' + unit : '',
        label: (item) => 'Mediciones: ' + data.distribution[item.dataIndex].count,
        afterLabel: (item) => item.dataIndex === data.distribution.length - 1 ? 'Incluye el valor máximo.' : 'Incluye el inicio; excluye el final.',
    };
    const distribution = new Chart(document.getElementById('distribution-chart'), {
        type: 'bar',
        data: { labels: data.distribution.map(intervalLabel), datasets: [{ label: 'Mediciones', data: data.distribution.map((row) => row.count), backgroundColor: colors.bar, borderColor: colors.solid, borderWidth: 1, borderRadius: 3, categoryPercentage: 1, barPercentage: .96 }] },
        options: distributionOptions,
    });

    const refresh = () => {
        trend.data.datasets = trendDatasets();
        trend.update();
        renderTable(groups, compare(), grouping, data.variable);
    };
    compareToggle?.addEventListener('change', refresh);
    limitsToggle?.addEventListener('change', refresh);
    renderTable(groups, compare(), grouping, data.variable);
    document.getElementById('chart-group-note').textContent = 'Agrupación por ' + groupNames[grouping] +
        '. Los promedios se ponderan por la cantidad de mediciones; los grupos vacíos se muestran como huecos.';
    const filename = slug(data.variable.name) + '-' + data.range.from + '-' + data.range.to;
    document.getElementById('download-chart-csv').addEventListener('click', () => {
        const headers = tableColumns(compare()).map((label) => label.includes('Promedio') || label === 'Mínimo' || label === 'Máximo' ? label + ' (' + unit + ')' : label);
        const rows = [
            ['Variable', data.variable.name], ['Unidad', unit], ['Período', fullRange(data.range.from, data.range.to)],
            ['Estación', root.dataset.scopeLabel], ['Agrupación', groupNames[grouping]],
            ...(compare() ? [['Período anterior', fullRange(data.range.previousFrom, data.range.previousTo)]] : []),
            [],
            headers, ...groups.map((group) => tableValues(group, compare(), false)),
        ];
        const content = '\uFEFF' + rows.map((row) => row.map(csvCell).join(',')).join('\r\n') + '\r\n';
        downloadBlob(new Blob([content], { type: 'text/csv;charset=utf-8' }), filename + '-' + grouping + '.csv');
        document.getElementById('chart-export-status').textContent = 'Datos CSV preparados para descargar.';
    });
    const charts = { trend, stations, distribution };
    const titles = { trend: 'Evolución de mediciones', stations: 'Comparación entre estaciones', distribution: 'Distribución de valores' };
    root.querySelectorAll('[data-chart-download]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.chartDownload;
            const metadata = [
                data.variable.name + ' · ' + unit,
                'Período: ' + fullRange(data.range.from, data.range.to),
                root.dataset.scopeLabel,
                ...(key === 'trend' ? ['Promedios por ' + groupNames[grouping],
                    ...(compare() ? ['Comparación: ' + fullRange(data.range.previousFrom, data.range.previousTo)] : [])] : []),
                ...(key === 'stations' && data.stations.length > 20 ? ['20 estaciones con más mediciones; datos completos en la tabla.'] : []),
            ];
            exportPng(charts[key], titles[key] + ' · EcoData', metadata, filename + '-' + key + '.png');
        });
        button.disabled = false;
    });
    document.getElementById('download-chart-csv').disabled = false;
    document.getElementById('charts-fallback').hidden = true;
    root.dataset.chartsReady = 'true';
    root.dataset.chartGroups = String(groups.length);
    root.ecodataCharts = charts;
}
