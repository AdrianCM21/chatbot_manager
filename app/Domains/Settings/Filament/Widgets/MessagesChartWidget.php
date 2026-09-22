<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament\Widgets;

use App\Domains\WhatsApp\Models\IncomingMessage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MessagesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Mensajes recibidos';

    protected static ?string $description = 'Volumen de consultas por WhatsApp y cuántas no encontraron un producto para sugerir.';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    public ?string $filter = 'day';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Por día (últimos 14 días)',
            'week' => 'Por semana (últimas 8 semanas)',
            'month' => 'Por mes (últimos 6 meses)',
        ];
    }

    protected function getData(): array
    {
        [$buckets, $labels] = match ($this->filter) {
            'week' => $this->weeklyBuckets(),
            'month' => $this->monthlyBuckets(),
            default => $this->dailyBuckets(),
        };

        $messages = IncomingMessage::query()
            ->receivedSince($buckets->first()['start'])
            ->get();

        $total = [];
        $sinResultado = [];

        foreach ($buckets as $bucket) {
            $inBucket = $messages->whereBetween('created_at', [$bucket['start'], $bucket['end']]);
            $total[] = $inBucket->count();
            $sinResultado[] = $inBucket->where('matched_products_count', 0)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Mensajes recibidos',
                    'data' => $total,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Sin resultado',
                    'data' => $sinResultado,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array{0: Collection<int, array{start: Carbon, end: Carbon}>, 1: array<int, string>}
     */
    private function dailyBuckets(): array
    {
        $buckets = collect(range(13, 0))->map(fn (int $daysAgo) => [
            'start' => now()->subDays($daysAgo)->startOfDay(),
            'end' => now()->subDays($daysAgo)->endOfDay(),
        ]);

        $labels = $buckets->map(fn (array $b) => $b['start']->isoFormat('ddd D'))->all();

        return [$buckets, $labels];
    }

    /**
     * @return array{0: Collection<int, array{start: Carbon, end: Carbon}>, 1: array<int, string>}
     */
    private function weeklyBuckets(): array
    {
        $buckets = collect(range(7, 0))->map(fn (int $weeksAgo) => [
            'start' => now()->subWeeks($weeksAgo)->startOfWeek(),
            'end' => now()->subWeeks($weeksAgo)->endOfWeek(),
        ]);

        $labels = $buckets->map(fn (array $b) => $b['start']->isoFormat('D MMM'))->all();

        return [$buckets, $labels];
    }

    /**
     * @return array{0: Collection<int, array{start: Carbon, end: Carbon}>, 1: array<int, string>}
     */
    private function monthlyBuckets(): array
    {
        $buckets = collect(range(5, 0))->map(fn (int $monthsAgo) => [
            'start' => now()->subMonths($monthsAgo)->startOfMonth(),
            'end' => now()->subMonths($monthsAgo)->endOfMonth(),
        ]);

        $labels = $buckets->map(fn (array $b) => $b['start']->isoFormat('MMM YYYY'))->all();

        return [$buckets, $labels];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
