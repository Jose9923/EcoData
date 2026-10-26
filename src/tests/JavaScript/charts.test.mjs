import assert from 'node:assert/strict';
import test from 'node:test';
import { groupTrend } from '../../resources/js/charts.js';

const shift = (date, days) => {
    const value = new Date(date + 'T00:00:00Z');
    value.setUTCDate(value.getUTCDate() + days);
    return value.toISOString().slice(0, 10);
};

function point(date, values = {}) {
    return {
        date,
        average: null,
        minimum: null,
        maximum: null,
        count: 0,
        previousDate: shift(date, -31),
        previousAverage: null,
        previousMinimum: null,
        previousMaximum: null,
        previousCount: 0,
        ...values,
    };
}

function days(from, to, values) {
    const result = [];
    for (let date = from; date <= to; date = shift(date, 1)) {
        result.push(point(date, typeof values === 'function' ? values(date) : values));
    }
    return result;
}

test('weekly averages use measurement counts rather than averaging daily means', () => {
    const input = [
        point('2026-10-05', { average: 10, minimum: 8, maximum: 12, count: 9,
            previousAverage: 0, previousMinimum: -1, previousMaximum: 1, previousCount: 8 }),
        point('2026-10-06', { average: 30, minimum: 30, maximum: 30, count: 1,
            previousAverage: 20, previousMinimum: 20, previousMaximum: 20, previousCount: 2 }),
    ];
    const original = structuredClone(input);
    const [result] = groupTrend(input, 'week');

    assert.equal(result.count, 10);
    assert.equal(result.average, 12);
    assert.equal(result.minimum, 8);
    assert.equal(result.maximum, 30);
    assert.equal(result.previousCount, 10);
    assert.equal(result.previousAverage, 4);
    assert.equal(result.previousMinimum, -1);
    assert.equal(result.previousMaximum, 20);
    assert.deepEqual(input, original, 'grouping must not overwrite daily data used by another chart mode');
});

test('daily gaps remain null while a real zero is retained in both periods', () => {
    const input = [
        point('2026-10-01', { average: 0, minimum: 0, maximum: 0, count: 3,
            previousAverage: 0, previousMinimum: 0, previousMaximum: 0, previousCount: 5 }),
        point('2026-10-02'),
        point('2026-10-03', { average: 4, minimum: 4, maximum: 4, count: 1 }),
    ];
    const result = groupTrend(input);

    assert.equal(result.length, 3);
    assert.equal(result[0].average, 0);
    assert.equal(result[0].previousAverage, 0);
    assert.equal(result[0].previousCount, 5);
    assert.equal(result[1].count, 0);
    assert.equal(result[1].previousCount, 0);
    for (const field of ['average', 'minimum', 'maximum', 'previousAverage', 'previousMinimum', 'previousMaximum']) {
        assert.equal(result[1][field], null, field);
    }
    assert.equal(result[2].previousAverage, null);
});

test('a single zero observation has the same valid value in every grouping mode', () => {
    const input = [point('2024-02-29', {
        average: 0, minimum: 0, maximum: 0, count: 1,
        previousAverage: 0, previousMinimum: 0, previousMaximum: 0, previousCount: 1,
    })];
    for (const grouping of ['day', 'week', 'month']) {
        const [result] = groupTrend(input, grouping);
        assert.equal(result.from, '2024-02-29');
        assert.equal(result.to, '2024-02-29');
        assert.equal(result.count, 1);
        assert.equal(result.average, 0);
        assert.equal(result.previousAverage, 0);
    }
});

test('weeks start on Monday across a year boundary and preserve partial edge labels', () => {
    const input = days('2025-12-28', '2026-01-05', { average: 2, minimum: 2, maximum: 2, count: 1 });
    const result = groupTrend(input, 'week');

    assert.deepEqual(result.map(({ from, to, count }) => ({ from, to, count })), [
        { from: '2025-12-28', to: '2025-12-28', count: 1 },
        { from: '2025-12-29', to: '2026-01-04', count: 7 },
        { from: '2026-01-05', to: '2026-01-05', count: 1 },
    ]);
    assert.equal(result[1].previousFrom, shift('2025-12-29', -31));
    assert.equal(result[1].previousTo, shift('2026-01-04', -31));
    assert.equal(result.reduce((sum, group) => sum + group.count, 0), 9);
});

test('monthly grouping includes leap day, excludes gaps from averages and keeps partial months', () => {
    const input = days('2024-01-31', '2024-03-01', (date) => date === '2024-02-14'
        ? {}
        : { average: date === '2024-02-29' ? 20 : 10, minimum: 5, maximum: 25, count: 2,
            previousAverage: 0, previousMinimum: 0, previousMaximum: 0, previousCount: 3 });
    const result = groupTrend(input, 'month');

    assert.deepEqual(result.map(({ from, to, count }) => ({ from, to, count })), [
        { from: '2024-01-31', to: '2024-01-31', count: 2 },
        { from: '2024-02-01', to: '2024-02-29', count: 56 },
        { from: '2024-03-01', to: '2024-03-01', count: 2 },
    ]);
    assert.ok(Math.abs(result[1].average - (27 * 10 + 20) / 28) < 1e-12);
    assert.equal(result[1].previousAverage, 0);
    assert.equal(result[1].previousCount, 84);
    assert.equal(result[1].previousFrom, '2024-01-01');
    assert.equal(result[1].previousTo, '2024-01-29');
    assert.equal(result.reduce((sum, group) => sum + group.count, 0), 60);
});

test('negative extrema and period totals survive every grouping without losing observations', () => {
    const input = days('2026-01-29', '2026-02-09', (date) => {
        const day = Number(date.slice(-2));
        const count = day % 3 + 1;
        return {
            average: -day, minimum: -day - 0.5, maximum: -day + 0.5, count,
            previousAverage: -day / 2, previousMinimum: -day, previousMaximum: 0,
            previousCount: count + 1,
        };
    });
    const expectedCount = input.reduce((sum, day) => sum + day.count, 0);
    const expectedPreviousCount = input.reduce((sum, day) => sum + day.previousCount, 0);
    const expectedTotal = input.reduce((sum, day) => sum + day.average * day.count, 0);
    const expectedPreviousTotal = input.reduce((sum, day) => sum + day.previousAverage * day.previousCount, 0);

    for (const grouping of ['day', 'week', 'month']) {
        const result = groupTrend(input, grouping);
        assert.equal(result.reduce((sum, group) => sum + group.count, 0), expectedCount);
        assert.equal(result.reduce((sum, group) => sum + group.previousCount, 0), expectedPreviousCount);
        assert.ok(Math.abs(result.reduce((sum, group) => sum + group.average * group.count, 0) - expectedTotal) < 1e-10);
        assert.ok(Math.abs(result.reduce((sum, group) => sum + group.previousAverage * group.previousCount, 0) - expectedPreviousTotal) < 1e-10);
        assert.equal(Math.min(...result.map(group => group.minimum)), -31.5);
        assert.equal(Math.max(...result.map(group => group.maximum)), -0.5);
        assert.equal(Math.min(...result.map(group => group.previousMinimum)), -31);
        assert.equal(Math.max(...result.map(group => group.previousMaximum)), 0);
    }
});

test('an entirely missing month has null metrics independently in each period', () => {
    const input = days('2026-02-01', '2026-02-28', {});
    const [result] = groupTrend(input, 'month');

    assert.equal(result.from, '2026-02-01');
    assert.equal(result.to, '2026-02-28');
    assert.equal(result.count, 0);
    assert.equal(result.previousCount, 0);
    for (const field of ['average', 'minimum', 'maximum', 'previousAverage', 'previousMinimum', 'previousMaximum']) {
        assert.equal(result[field], null, field);
    }
});

test('no source days produce no fabricated groups', () => {
    for (const grouping of ['day', 'week', 'month']) {
        assert.deepEqual(groupTrend([], grouping), []);
    }
});
