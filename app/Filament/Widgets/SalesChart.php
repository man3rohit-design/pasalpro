<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\SaleChannel;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Sales, last 30 days';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $daysAgo): string => today()->subDays($daysAgo)->toDateString());

        $totals = Sale::completed()
            ->where('sold_at', '>=', today()->subDays(29))
            ->selectRaw('substr(sold_at, 1, 10) as day, channel, sum(total) as amount')
            ->groupBy('day', 'channel')
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'In store',
                    'data'            => $this->amountsPerDay($days, $totals->where('channel', SaleChannel::Counter)),
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill'            => true,
                ],
                [
                    'label'           => 'Online',
                    'data'            => $this->amountsPerDay($days, $totals->where('channel', SaleChannel::Online)),
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                ],
            ],
            'labels' => $days->map(fn (string $day): string => date('M j', strtotime($day)))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @param Collection<int, string> $days
     * @param Collection<int, Sale>   $totals
     *
     * @return list<float>
     */
    private function amountsPerDay(Collection $days, Collection $totals): array
    {
        $amounts = $totals->pluck('amount', 'day');

        return $days->map(fn (string $day): float => round((float) ($amounts[$day] ?? 0), 2))->values()->all();
    }
}
