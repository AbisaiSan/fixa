<?php

use App\Models\CommonWord;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

test('the common words table starts empty, with no student data (VOC-04)', function () {
    expect(Schema::hasTable('common_words'))->toBeTrue()
        ->and(CommonWord::count())->toBe(0)
        ->and(Schema::hasColumn('common_words', 'user_id'))->toBeFalse()
        ->and(Schema::getColumnListing('common_words'))->toContain('language', 'term', 'rank', 'source');
});

test('a term appears once per language', function () {
    $row = ['language' => 'en', 'term' => 'the', 'rank' => 1, 'source' => 'lista de teste'];
    (new CommonWord)->forceFill($row)->save();

    expect(fn () => (new CommonWord)->forceFill([...$row, 'rank' => 2])->save())
        ->toThrow(UniqueConstraintViolationException::class);
});
