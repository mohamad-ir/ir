<?php

namespace App\Filament\Pages\Settings;

use App\Services\Analytics\GeoLocator;
use App\Services\Google\GoogleClient;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Throwable;

class AnalyticsSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/analytics';

    protected static ?string $navigationLabel = 'Analytics & Google';

    protected static ?string $title = 'Analytics & Google integration';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 5;

    protected function groups(): array
    {
        return ['analytics'];
    }

    protected function permission(): string
    {
        return 'settings.manage';
    }

    protected function mutateStateBeforeSave(array $state): array
    {
        $json = $state['analytics']['google_credentials'] ?? null;
        if (filled($json)) {
            $decoded = json_decode($json, true);
            if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                Notification::make()->danger()->title('The service account JSON is not valid')->body('Paste the whole key file downloaded from Google Cloud.')->send();
                $state['analytics']['google_credentials'] = null;
            }
        }

        if (! empty($state['analytics']['geoip_upload'])) {
            $file = is_array($state['analytics']['geoip_upload']) ? reset($state['analytics']['geoip_upload']) : $state['analytics']['geoip_upload'];
            @mkdir(dirname(GeoLocator::databasePath()), 0755, true);
            copy($file->getRealPath(), GeoLocator::databasePath());
        }
        unset($state['analytics']['geoip_upload']);

        return $state;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Test Google connection')->icon('heroicon-o-signal')->color('gray')
                ->action(function () {
                    try {
                        $result = app(GoogleClient::class)->test();
                        Notification::make()->success()->title('Connected to Google')->body(implode("\n", $result))->persistent()->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Google connection failed')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }

    protected function components(): array
    {
        return [
            Section::make('Built-in privacy-friendly analytics')
                ->description('Counts page views, visitors, sources, devices, countries and engagement without cookies. Visitors are identified only by an anonymous hash that changes every day.')
                ->columns(2)->schema([
                    Toggle::make('analytics.enabled')->label('Collect statistics'),
                    Toggle::make('analytics.exclude_staff')->label('Exclude signed-in team members'),
                    TextInput::make('analytics.retention_days')->label('Keep raw data for (days)')->numeric()->minValue(30)->maxValue(3650),
                    FileUpload::make('analytics.geoip_upload')->label('Country lookup database (optional)')->storeFiles(false)->acceptedFileTypes(['application/octet-stream'])
                        ->helperText(fn () => (GeoLocator::available() ? '✓ GeoLite2 database installed. ' : '').'Upload GeoLite2-Country.mmdb from maxmind.com (free account). Not needed if the site uses Cloudflare.'),
                ]),
            Section::make('Google Analytics 4 (optional)')
                ->description('Adds the GA4 tag after visitors accept cookies. Reports appear in Insights › Google.')
                ->columns(2)->schema([
                    TextInput::make('analytics.ga4_measurement_id')->label('Measurement ID')->placeholder('G-XXXXXXXXXX')->regex('/^G-[A-Z0-9]+$/'),
                    TextInput::make('analytics.ga4_property_id')->label('Property ID (for reports)')->placeholder('123456789')->numeric(),
                ]),
            Section::make('Google Search Console (optional)')->columns(2)->schema([
                TextInput::make('analytics.gsc_site_url')->label('Property')->placeholder('sc-domain:example.com or https://example.com/'),
            ]),
            Section::make('Google service account')
                ->description(new HtmlString('Reports are read with a service account: create one in Google Cloud, enable the <em>Google Analytics Data API</em> and <em>Search Console API</em>, download a JSON key, then add the service-account email as a <strong>Viewer</strong> in GA4 and as a <strong>user</strong> in Search Console. Step-by-step instructions are in the deployment guide.'))
                ->schema([
                    Textarea::make('analytics.google_credentials')->label('Service account key (JSON)')->rows(5)->extraInputAttributes(['class' => 'font-mono text-xs'])
                        ->placeholder(fn (Get $get) => $get('analytics.google_credentials_set') ? 'A key is saved and encrypted. Paste a new key to replace it.' : '{ "type": "service_account", … }'),
                    Placeholder::make('stored')->hiddenLabel()->visible(fn (Get $get) => (bool) $get('analytics.google_credentials_set'))
                        ->content(new HtmlString('<span class="text-sm text-success-600">✓ Credentials stored (encrypted at rest).</span>')),
                ]),
        ];
    }
}
