<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament\Widgets;

use App\Domains\WhatsApp\Models\IncomingMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class BotMetricsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $last7Days = $this->last7DaysCounts();
        $messagesToday = $last7Days->last()['total'];
        $last7Total = $last7Days->sum('total');
        $last7Matched = $last7Days->sum('matched');
        $resolutionRate = $last7Total > 0 ? (int) round(($last7Matched / $last7Total) * 100) : null;
        $lastMessage = IncomingMessage::query()->latest()->first();

        return [
            Stat::make('Mensajes de hoy', (string) $messagesToday)
                ->description('Consultas recibidas por WhatsApp hoy')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart($last7Days->pluck('total')->all())
                ->color('primary'),

            Stat::make('Tasa de resolución', $resolutionRate !== null ? "{$resolutionRate}%" : 'Sin datos')
                ->description($resolutionRate !== null
                    ? 'De los últimos 7 días, encontramos producto para sugerir'
                    : 'Todavía no llegaron mensajes')
                ->descriptionIcon($resolutionRate === null || $resolutionRate >= 70 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->chart($last7Days->map(fn (array $d) => $d['total'] > 0 ? (int) round(($d['matched'] / $d['total']) * 100) : 0)->all())
                ->color($resolutionRate === null ? 'gray' : ($resolutionRate >= 70 ? 'success' : 'warning')),

            Stat::make('Último mensaje recibido', $lastMessage?->created_at?->diffForHumans() ?? 'Todavía no llegó ninguno')
                ->description($lastMessage ? 'de '.$lastMessage->from_number : 'Esperando el primer mensaje')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }

    /**
     * Mensajes totales y con match por cada uno de los últimos 7 días (hoy incluido).
     *
     * @return Collection<int, array{total: int, matched: int}>
     */
    private function last7DaysCounts(): Collection
    {
        $messages = IncomingMessage::query()
            ->receivedSince(now()->subDays(6)->startOfDay())
            ->get();

        return collect(range(6, 0))->map(function (int $daysAgo) use ($messages) {
            $day = now()->subDays($daysAgo);
            $ofDay = $messages->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);

            return [
                'total' => $ofDay->count(),
                'matched' => $ofDay->where('matched_products_count', '>', 0)->count(),
            ];
        });
    }
}
