<?php

use App\Domain\Progress\ResumeMessage;

test('studying yesterday needs no return message', function () {
    expect(ResumeMessage::for(lastStudied: '2026-10-28', today: '2026-10-29', yesterdayFrozen: false))->toBeNull()
        ->and(ResumeMessage::for(lastStudied: '2026-10-29', today: '2026-10-29', yesterdayFrozen: false))->toBeNull();
});

test('a brand new student gets no return message', function () {
    expect(ResumeMessage::for(lastStudied: null, today: '2026-10-29', yesterdayFrozen: false))->toBeNull();
});

test('one day protected by a freeze shows the freeze message (GAM-02, GAM-05)', function () {
    expect(ResumeMessage::for(lastStudied: '2026-10-27', today: '2026-10-29', yesterdayFrozen: true))->toBe(ResumeMessage::FreezeUsed);
});

test('coming back after missing days welcomes back, whatever the number of days (GAM-05)', function (string $lastStudied) {
    expect(ResumeMessage::for(lastStudied: $lastStudied, today: '2026-10-29', yesterdayFrozen: false))->toBe(ResumeMessage::WelcomeBack);
})->with(['2026-10-27', '2026-10-20', '2026-08-01']);

test('the message never carries a number of missed days', function () {
    foreach (ResumeMessage::cases() as $message) {
        expect($message->value)->not->toMatch('/\d/');
    }
});
