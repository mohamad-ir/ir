<?php

namespace App\Filament\Pages\Settings;

use App\Models\ActivityLog;
use App\Support\SettingDefaults;
use App\Support\Settings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

/**
 * Base for dashboard pages that edit groups of site settings.
 * Form state is nested by group ("theme" => ["primary" => …]) and saved to the settings table.
 */
abstract class SettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    public ?array $data = [];

    /** @return array<int, string> setting groups edited on this page */
    abstract protected function groups(): array;

    abstract protected function permission(): string;

    abstract protected function components(): array;

    public static function canAccess(): bool
    {
        return auth()->user()?->can((new static)->permission()) ?? false;
    }

    public function mount(): void
    {
        $state = [];
        foreach ($this->groups() as $group) {
            $state[$group] = Settings::group($group);
            if (in_array($group, ['mail', 'analytics', 'security'], true)) {
                foreach (SettingDefaults::encrypted() as $key) {
                    [$g, $k] = explode('.', $key, 2);
                    if ($g === $group) {
                        $state[$group][$k.'_set'] = filled($state[$group][$k] ?? null);
                        $state[$group][$k] = null; // never send secrets to the browser
                    }
                }
            }
        }
        $this->form->fill($this->mutateStateBeforeFill($state));
    }

    protected function mutateStateBeforeFill(array $state): array
    {
        return $state;
    }

    protected function mutateStateBeforeSave(array $state): array
    {
        return $state;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->components())->statePath('data');
    }

    public function save(): void
    {
        $state = $this->mutateStateBeforeSave($this->form->getState());
        $values = [];
        $encrypted = SettingDefaults::encrypted();

        foreach ($this->groups() as $group) {
            foreach ($state[$group] ?? [] as $key => $value) {
                if (str_ends_with($key, '_set')) {
                    continue;
                }
                $full = "{$group}.{$key}";
                if (in_array($full, $encrypted, true) && blank($value)) {
                    continue; // empty secret field = keep the stored value
                }
                $values[$full] = $value;
            }
        }

        Settings::setMany($values, $encrypted);
        ActivityLog::record('settings', static::getNavigationLabel().' updated', null, ['keys' => array_keys($values)]);
        $this->afterSave();

        Notification::make()->success()->title('Settings saved')->send();
    }

    protected function afterSave(): void {}

    public function getFormActionsLabel(): string
    {
        return 'Save changes';
    }
}
