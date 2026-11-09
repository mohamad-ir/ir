<?php

namespace App\Filament\Pages\Auth;

use App\Support\AdminLocale;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/** Team member profile in the dashboard, with the dashboard language. */
class EditProfile extends BaseEditProfile
{
    protected bool $localeChanged = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            Select::make('admin_locale')->label(__('Dashboard language'))
                ->options(\App\Support\Ui::options(AdminLocale::LOCALES))->default('en')->selectablePlaceholder(false)
                ->helperText(__('Only changes the dashboard. The website, member pages and emails always stay in English.')),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['admin_locale'] ??= AdminLocale::current();

        return parent::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['admin_locale'] = AdminLocale::valid($data['admin_locale'] ?? null) ?? 'en';
        $this->localeChanged = $data['admin_locale'] !== AdminLocale::current();
        if ($this->localeChanged) {
            cookie()->queue(cookie()->forever(AdminLocale::COOKIE, $data['admin_locale']));
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    /** Reload after a language change so every part of the dashboard switches at once. */
    protected function getRedirectUrl(): ?string
    {
        return $this->localeChanged ? static::getUrl() : parent::getRedirectUrl();
    }
}
