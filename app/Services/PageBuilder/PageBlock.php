<?php

namespace App\Services\PageBuilder;

use App\Support\SafeUrl;
use App\Support\Ui;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * One page-builder section type. Subclasses define the editing schema,
 * optional data loading, and render through resources/views/blocks/{key}.blade.php.
 */
abstract class PageBlock
{
    abstract public static function key(): string;

    abstract public static function label(): string;

    abstract public static function icon(): string;

    /** Short description shown in the block picker. */
    public static function description(): string
    {
        return '';
    }

    /** @return array<int, Component|Field> */
    abstract public static function schema(): array;

    /** Load data from the database for rendering. */
    public static function prepare(array $data): array
    {
        return $data;
    }

    /** Return false to omit the whole section (e.g. a feed with nothing to show). */
    public static function shouldRender(array $prepared, array $context): bool
    {
        return true;
    }

    public static function defaultTone(): string
    {
        return 'default';
    }

    public static function plainText(array $data): string
    {
        return collect($data)->only(['eyebrow', 'heading', 'subheading', 'intro', 'text'])->filter(fn ($v) => is_string($v))->implode(' ');
    }

    public static function make(): Block
    {
        return Block::make(static::key())
            ->label(function (?array $state) {
                $heading = $state['heading'] ?? $state['name'] ?? null;
                $hidden = ! empty($state['_hidden']) ? ' · '.__('hidden') : '';

                return __(static::label()).($heading ? ' — '.str(strip_tags((string) $heading))->limit(48) : '').$hidden;
            })
            ->icon(static::icon())
            ->schema([
                Tabs::make()->contained(false)->tabs([
                    Tab::make('Content')->label(__('Content'))->icon('heroicon-m-pencil')->schema(static::schema()),
                    Tab::make('Appearance')->label(__('Appearance'))->icon('heroicon-m-swatch')->schema(static::appearanceSchema())->columns(2),
                ]),
            ]);
    }

    public static function appearanceSchema(): array
    {
        return [
            ToggleButtons::make('_tone')->label(__('Background'))->inline()->default(static::defaultTone())->options(Ui::options([
                'default' => 'Page', 'surface' => 'White', 'muted' => 'Soft grey', 'accent' => 'Accent tint', 'ink' => 'Dark',
            ]))->columnSpanFull(),
            ToggleButtons::make('_spacing')->label(__('Vertical spacing'))->inline()->default('normal')->options(Ui::options([
                'none' => 'None', 'tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Generous',
            ])),
            ToggleButtons::make('_width')->label(__('Content width'))->inline()->default('standard')->options(Ui::options([
                'narrow' => 'Narrow', 'standard' => 'Standard', 'wide' => 'Wide',
            ])),
            Select::make('_visibility')->label(__('Show on'))->default('all')->options(Ui::options([
                'all' => 'All devices', 'desktop' => 'Desktop only', 'mobile' => 'Mobile & tablet only',
            ])),
            TextInput::make('_anchor')->label(__('Anchor ID'))->prefix('#')->alphaDash()->helperText(__('Link to this section with /page#anchor')),
            Toggle::make('_hidden')->label(__('Hide this section'))->helperText(__('Keeps the section but removes it from the live page.'))->columnSpanFull(),
        ];
    }

    public static function buttonsRepeater(string $name = 'buttons', int $max = 3)
    {
        return Repeater::make($name)
            ->label(__('Buttons'))
            ->schema([
                TextInput::make('label')->required()->maxLength(60),
                TextInput::make('url')->label(__('Link'))->required()->placeholder(__('/cases or https://…'))->maxLength(500)->rule(SafeUrl::rule()),
                Select::make('style')->options(Ui::options(['primary' => 'Primary', 'accent' => 'Accent', 'secondary' => 'Outline', 'ghost' => 'Text link']))->default('primary')->selectablePlaceholder(false),
            ])
            ->columns(3)
            ->maxItems($max)
            ->defaultItems(0)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state) => $state['label'] ?? null)
            ->addActionLabel(__('Add button'));
    }
}
