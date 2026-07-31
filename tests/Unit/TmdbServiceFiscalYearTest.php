<?php

use App\Services\TmdbService;
use Carbon\CarbonImmutable;

test('1月から3月は前年を年度として期間を計算する', function (string $date, string $expectedEnd) {
    $period = app(TmdbService::class)->fiscalYearPeriod(CarbonImmutable::parse($date));

    expect($period['year'])->toBe(2026)
        ->and($period['start']->toDateString())->toBe('2026-04-01')
        ->and($period['end']->toDateString())->toBe($expectedEnd);
})->with([
    ['2027-01-15', '2027-01-15'],
    ['2027-03-31', '2027-03-31'],
]);

test('4月から12月は現在年を年度として期間を計算する', function (string $date) {
    $period = app(TmdbService::class)->fiscalYearPeriod(CarbonImmutable::parse($date));

    expect($period['year'])->toBe(2026)
        ->and($period['start']->toDateString())->toBe('2026-04-01')
        ->and($period['end']->toDateString())->toBe($date);
})->with([
    ['2026-04-01'],
    ['2026-07-30'],
    ['2026-12-31'],
]);

test('4月1日に新しい年度へ切り替わる', function () {
    $period = app(TmdbService::class)->fiscalYearPeriod(CarbonImmutable::parse('2027-04-01'));

    expect($period['year'])->toBe(2027)
        ->and($period['start']->toDateString())->toBe('2027-04-01')
        ->and($period['end']->toDateString())->toBe('2027-04-01');
});
