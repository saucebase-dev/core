<?php

namespace Saucebase\Core\Filament\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Saucebase\Core\Filament\Pages\SettingsPage;
use Saucebase\Core\Settings\LocalizationSettings as LocalizationSettingsData;

class LocalizationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 2;

    protected static string $settings = LocalizationSettingsData::class;

    public static function getNavigationLabel(): string
    {
        return __('Localization');
    }

    public function getTitle(): string
    {
        return __('Localization Settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Languages'))
                ->description(__('Choose which languages visitors can switch between. With only one language on, the language selector is hidden.'))
                ->icon(Heroicon::OutlinedLanguage)
                ->iconColor('info')
                ->schema([
                    CheckboxList::make('enabled_locales')
                        ->label(__('Available languages'))
                        ->extraAttributes(['data-testid' => 'admin-enabled-locales'])
                        ->options(fn (): array => app(LocalizationSettingsData::class)->available())
                        ->helperText(__('Only languages with translation files installed are listed.'))
                        ->required()
                        ->minItems(1)
                        ->live()
                        ->columns(2)
                        ->columnSpanFull(),
                    Select::make('default_locale')
                        ->label(__('Default language'))
                        ->extraAttributes(['data-testid' => 'admin-default-locale'])
                        ->options(fn (Get $get): array => array_intersect_key(
                            app(LocalizationSettingsData::class)->available(),
                            array_flip((array) $get('enabled_locales')),
                        ))
                        ->helperText(__('Used until a visitor picks a language of their own.'))
                        ->required()
                        ->native(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
