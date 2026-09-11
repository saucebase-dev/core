<?php

namespace Saucebase\Core\Filament\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Saucebase\Core\Filament\Pages\SettingsPage;
use Saucebase\Core\Settings\GeneralSettings as GeneralSettingsData;

class GeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 1;

    protected static string $settings = GeneralSettingsData::class;

    public static function getNavigationLabel(): string
    {
        return __('General');
    }

    public function getTitle(): string
    {
        return __('General Settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Application Settings'))
                ->description(__('Configure the platform identity.'))
                ->icon(Heroicon::OutlinedInformationCircle)
                ->iconColor('info')
                ->schema([
                    TextInput::make('site_name')
                        ->label(__('Site name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('site_tagline')
                        ->label(__('Site tagline'))
                        ->helperText(__('Shown as the home page title, before the site name.'))
                        ->maxLength(60),
                    Textarea::make('site_description')
                        ->label(__('Site description'))
                        ->maxLength(500)
                        ->columnSpanFull(),
                    $this->brandUpload(
                        'site_logo_on_light',
                        __('Logo (on light backgrounds)'),
                        __('Wide lockup including your name. Shown wherever there is room for it.'),
                    ),
                    $this->brandUpload(
                        'site_logo_on_dark',
                        __('Logo (on dark backgrounds)'),
                        __('The same lockup in light artwork, for dark mode.'),
                    ),
                    $this->brandUpload(
                        'site_icon_on_light',
                        __('Icon (on light backgrounds)'),
                        __('Square mark. Used where only a mark fits, such as a collapsed sidebar or a browser tab.'),
                    ),
                    $this->brandUpload(
                        'site_icon_on_dark',
                        __('Icon (on dark backgrounds)'),
                        __('The same mark in light artwork, for dark mode.'),
                    ),
                ])
                ->columns(2),
        ]);
    }

    /**
     * One upload field per brand asset.
     *
     * `->imageEditor()` is deliberately absent. It is client-side Cropper.js, which
     * cannot open an SVG — and these fields are SVG by default, since the shipped brand
     * is vector. Offering an editor that silently fails on the format we recommend would
     * be worse than offering none.
     *
     * These are never empty: they fall back to the artwork core ships, so clearing one
     * restores the default rather than leaving a gap.
     */
    private function brandUpload(string $name, string $label, string $helperText): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->extraAttributes(['data-testid' => 'admin-'.str_replace('_', '-', $name)])
            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg', 'image/webp'])
            ->disk('public')
            ->directory('site-branding')
            ->visibility('public')
            ->maxSize(1024)
            ->helperText($helperText);
    }
}
