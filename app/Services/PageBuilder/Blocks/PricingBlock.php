<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\MembershipPlan;
use App\Services\PageBuilder\PageBlock;
use App\Services\Payments\StripeClient;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class PricingBlock extends PageBlock
{
    public static function key(): string { return 'pricing'; }

    public static function label(): string { return 'Membership plans'; }

    public static function icon(): string { return 'heroicon-o-sparkles'; }

    public static function description(): string { return 'Membership plans with prices and a monthly / yearly switch.'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Membership')->maxLength(80),
            TextInput::make('heading')->default('Unlock everything')->maxLength(160),
            Textarea::make('intro')->rows(2),
        ];
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || collect($prepared['plans'] ?? [])->isNotEmpty();
    }

    public static function prepare(array $data): array
    {
        $data['plans'] = MembershipPlan::active()->get();
        $data['subscription'] = auth()->user()?->activeSubscription();
        $data['payments_enabled'] = StripeClient::configured();

        return $data;
    }
}
