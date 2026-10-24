<?php

use App\Domain\Plan\ReminderRule;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // 19h05 de segunda, 26/10, em São Paulo.
    $this->local = CarbonImmutable::parse('2026-10-26 19:05:00', 'America/Sao_Paulo');
});

test('the reminder goes once the chosen hour has come in the student timezone', function () {
    expect(ReminderRule::due(enabled: true, hour: 19, localNow: $this->local, sentOn: null, todayCounted: false))->toBeTrue()
        ->and(ReminderRule::due(enabled: true, hour: 20, localNow: $this->local, sentOn: null, todayCounted: false))->toBeFalse();
});

test('a late run still sends it on the same day', function () {
    expect(ReminderRule::due(enabled: true, hour: 19, localNow: $this->local->setTime(22, 40), sentOn: null, todayCounted: false))->toBeTrue();
});

test('it is sent at most once a day', function () {
    expect(ReminderRule::due(enabled: true, hour: 19, localNow: $this->local, sentOn: '2026-10-26', todayCounted: false))->toBeFalse()
        ->and(ReminderRule::due(enabled: true, hour: 19, localNow: $this->local->addDay(), sentOn: '2026-10-26', todayCounted: false))->toBeTrue();
});

test('nobody who already met the goal today gets a reminder', function () {
    expect(ReminderRule::due(enabled: true, hour: 19, localNow: $this->local, sentOn: null, todayCounted: true))->toBeFalse();
});

test('the reminder is optional and off unless the student turns it on', function () {
    expect(ReminderRule::due(enabled: false, hour: 19, localNow: $this->local, sentOn: null, todayCounted: false))->toBeFalse();
});

test('the hours offered go from 6h to 22h', function () {
    expect(ReminderRule::HOURS)->toBe(range(6, 22))
        ->and(ReminderRule::DEFAULT_HOUR)->toBe(19);
});
