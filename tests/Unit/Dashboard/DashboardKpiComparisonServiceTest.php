<?php

namespace Tests\Unit\Dashboard;

use App\Services\Dashboard\DashboardKpiComparisonService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DashboardKpiComparisonServiceTest extends TestCase
{
    #[DataProvider('comparisonProvider')]
    public function test_it_builds_absolute_comparisons_with_business_sentiment(
        int $current,
        int $previous,
        string $performanceDirection,
        array $expected
    ): void {
        $service = new DashboardKpiComparisonService();

        $this->assertSame(
            $expected,
            $service->compare($current, $previous, $performanceDirection)
        );
    }

    public static function comparisonProvider(): array
    {
        return [
            'neutral increase' => [
                10,
                0,
                DashboardKpiComparisonService::NEUTRAL,
                [
                    'value' => 10,
                    'label' => '+10',
                    'direction' => 'up',
                    'sentiment' => 'neutral',
                ],
            ],
            'lower is better decrease' => [
                0,
                3,
                DashboardKpiComparisonService::LOWER_IS_BETTER,
                [
                    'value' => -3,
                    'label' => '-3',
                    'direction' => 'down',
                    'sentiment' => 'positive',
                ],
            ],
            'lower is better increase' => [
                3,
                0,
                DashboardKpiComparisonService::LOWER_IS_BETTER,
                [
                    'value' => 3,
                    'label' => '+3',
                    'direction' => 'up',
                    'sentiment' => 'negative',
                ],
            ],
            'higher is better increase' => [
                7,
                4,
                DashboardKpiComparisonService::HIGHER_IS_BETTER,
                [
                    'value' => 3,
                    'label' => '+3',
                    'direction' => 'up',
                    'sentiment' => 'positive',
                ],
            ],
            'higher is better decrease' => [
                2,
                5,
                DashboardKpiComparisonService::HIGHER_IS_BETTER,
                [
                    'value' => -3,
                    'label' => '-3',
                    'direction' => 'down',
                    'sentiment' => 'negative',
                ],
            ],
            'no change' => [
                5,
                5,
                DashboardKpiComparisonService::LOWER_IS_BETTER,
                [
                    'value' => 0,
                    'label' => 'No change',
                    'direction' => 'flat',
                    'sentiment' => 'neutral',
                ],
            ],
        ];
    }
}
