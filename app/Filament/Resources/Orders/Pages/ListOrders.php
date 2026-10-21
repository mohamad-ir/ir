<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export CSV (accounting)')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->action(fn () => response()->streamDownload(function () {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Number', 'Date paid', 'Customer', 'Email', 'Country', 'VAT ID', 'Item', 'Type', 'Currency', 'Subtotal', 'Tax', 'Total', 'Refunded', 'Status', 'Stripe payment']);
                    Order::whereNotNull('paid_at')->orderBy('paid_at')->chunk(500, function ($orders) use ($out) {
                        foreach ($orders as $o) {
                            fputcsv($out, [$o->number, $o->paid_at?->toDateTimeString(), $o->name, $o->email, $o->meta['customer_country'] ?? '', implode(' ', $o->meta['tax_ids'] ?? []),
                                $o->description, $o->type, $o->currency, $o->subtotal / 100, $o->tax / 100, $o->total / 100, $o->refunded_amount / 100, $o->status->value, $o->stripe_payment_intent_id ?: $o->stripe_invoice_id]);
                        }
                    });
                    fclose($out);
                }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv'])),
        ];
    }
}
