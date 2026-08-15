<?php

namespace Tests\Unit;

use App\Models\Rundown;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RundownCompactBracketNamesTest extends TestCase
{
    #[DataProvider('compactCases')]
    public function test_compacts_bracket_names(array $names, string $expected): void
    {
        $this->assertSame($expected, Rundown::compactBracketNames($names));
    }

    public static function compactCases(): array
    {
        return [
            'same year boys girls' => [
                ['2018 Boys', '2018 Girls'],
                '2018 Boys & Girls',
            ],
            'year range boys girls' => [
                ['2018 Boys', '2018 Girls', '2019 Boys', '2019 Girls'],
                '2018 - 2019 Boys & Girls',
            ],
            'uppercase genders' => [
                ['2020 BOYS', '2020 GIRLS'],
                '2020 BOYS & GIRLS',
            ],
            'single name' => [
                ['2024 MIX'],
                '2024 MIX',
            ],
            'same gender different years' => [
                ['2018 Boys', '2019 Boys'],
                '2018 - 2019 Boys',
            ],
            'non rectangular falls back' => [
                ['2018 Boys', '2019 Girls'],
                '2018 Boys & 2019 Girls',
            ],
            'non year pattern falls back' => [
                ['Open Class', 'Pro'],
                'Open Class & Pro',
            ],
            'non contiguous years' => [
                ['2018 Boys', '2018 Girls', '2020 Boys', '2020 Girls'],
                '2018 & 2020 Boys & Girls',
            ],
        ];
    }
}
