<?php

namespace Saucebase\Core\Filament\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Saucebase\Core\Filament\Pages\SettingsPage;
use Saucebase\Core\Settings\SeoSettings as SeoSettingsData;

class SeoSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 3;

    protected static string $settings = SeoSettingsData::class;

    public static function getNavigationLabel(): string
    {
        return __('SEO');
    }

    public function getTitle(): string
    {
        return __('SEO Settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Search engines'))
                ->description(__('Control what search engines can discover on your site.'))
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->iconColor('info')
                ->schema([
                    Toggle::make('sitemap_enabled')
                        ->label(__('Publish sitemap.xml'))
                        ->helperText(__('Lists your public pages and posts for search engines.')),
                    Toggle::make('robots_enabled')
                        ->label(__('Publish robots.txt'))
                        ->live(),
                    Textarea::make('robots_content')
                        ->label(__('robots.txt rules'))
                        ->helperText(__('The Sitemap line is added automatically when the sitemap is published.'))
                        ->rows(8)
                        ->required()
                        ->visible(fn (Get $get): bool => (bool) $get('robots_enabled'))
                        ->extraInputAttributes(['class' => 'font-mono']),
                ]),
        ]);
    }
}
