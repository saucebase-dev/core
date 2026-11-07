---
name: saucebase-filament-development
description: Develop Filament resources inside Saucebase modules, including tables, forms, infolists, pages, actions, filters, navigation groups, and tests. Use whenever creating or changing module Filament functionality.
---

# Saucebase Filament Development

## When to Use

Activate this skill when:

- Creating Filament resources (tables, forms, infolists, pages) inside any module
- Adding actions, filters, or bulk actions to Filament tables
- Registering navigation groups or panel configuration in a module
- Testing Filament resources

---

## Resource Directory Structure

Resources in modules are split into separate files for Form, Table, Infolist, and Pages. Each resource lives in its own subdirectory:

```
modules/<name>/src/Filament/
  <Name>Plugin.php
  Pages/                        # standalone pages, e.g. settings pages
  Resources/
    <Models>/
      <Model>Resource.php
      Schemas/
        <Model>Form.php         # Form schema (create/edit)
        <Model>Infolist.php     # Infolist schema (view — optional)
      Tables/
        <Model>Table.php        # Table schema
      Pages/
        List<Models>.php
        Create<Model>.php
        Edit<Model>.php
        View<Model>.php         # optional
```

---

## Plugin Pattern

Core's `ModulesPlugin` registers every installed module's `Modules\{Name}\Filament\{Name}Plugin`, where `{Name}` is the studly-cased module name, so the class needs no manual registration.

```php
namespace Modules\Feature\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Saucebase\Core\Filament\ModulePlugin;

class FeaturePlugin implements Plugin
{
    use ModulePlugin;

    public function getModuleName(): string { return 'Feature'; }

    public function getId(): string { return 'feature'; }

    public function boot(Panel $panel): void
    {
        // Optional: register navigation groups, custom pages, etc.
    }
}
```

The `ModulePlugin` trait discovers Pages, Resources and Widgets under `src/Filament/`, and Livewire components under `src/Livewire/`.

### Dashboard widgets

The panel's dashboard is core's `Filament\Pages\Dashboard`: Filament's, with its `FilterAction` (`HasFiltersAction`) as the period filter. The header button, top right, shows the active period and opens a slide-over: a preset (Today, 7 days, 30 days default, 3 months, 12 months, custom) plus From and To. A preset fills the dates; editing a date makes it custom. Presets include today. Filters are `period`, `start`, `end`, kept in the URL and session. Other dashboards extend it and list their own `getWidgets()` (billing's does).

- **Period:** `use Concerns\ReadsPeriod;` then `$this->period()` gives a `Filament\Period` (`start`, `end`, `previous()`, `labels()`). `$period->series($query, 'sum(amount)')` returns one value per bucket: days up to 31 days, weeks up to 92, then months. The database groups by `date()`, the same in SQLite, MySQL and PostgreSQL.
- **Stats:** `TrendStat::make($label, $now, $before, $chart, lowerIsBetter:, format:)` shows the value, the change against the previous period (green when good), and a trend line. With no baseline it says so instead of a percentage.
- **Order bands (`$sort`):** 10–19 stats, 20–29 needs attention, 30+ charts. Taken: 10 users (app), 11 workspaces (tenancy), 12 billing, 13 packages, 14 newsletter, 20 billing attention, 21 roadmap attention. Pick a free slot; `AdminDashboardTest` fails on a tie.
- **Size:** the dashboard has 2 columns. A widget with one or two stats sets `$columnSpan = 1` and `$columns` to its stat count, so it pairs with its neighbour; three or more stats take `'full'`.
- **Cost:** each lazy widget is its own request. Cache what's expensive (billing's `BillingMetrics` uses `Cache::flexible`, keyed by the dates), never what a person must act on now (attention counts). `tests/Load/DashboardLoadTest.php` times every widget: `LOAD_TEST=1 php -d memory_limit=4G artisan test tests/Load`.
- **Empty states:** no misleading zeros. Hide a widget in `canView()` until there's something to show (no packages, newsletter not configured, nothing needing attention), or show one stat linking to setup (billing without plans). Keep the permission check in `canView()` too.

Optional plugin hooks, read by core's `ModulesPlugin`:

- `public static function getNavigationGroupSort(): int` orders plugin registration, and so the module's
  navigation groups; plugins without it register last.
- `public function getNavigationItems(): array` adds custom `NavigationItem`s to the panel.

With `filament.modules.clusters.enabled`, the trait also discovers clusters in `src/Filament/Clusters/`,
and `filament.modules.clusters.use-top-navigation` switches the panel to top navigation.

## Settings Pages

A module's settings page (backed by a Spatie settings class in `src/Settings/`) extends
`Saucebase\Core\Filament\Pages\SettingsPage`. It places the page in the Settings navigation group, after
any item there that isn't a settings page, orders settings pages by `$navigationSort` (capped at 1000),
and limits the form width. It also asks for `manage settings`, the permission for site-wide settings; a
page whose settings belong to the module's own admin area overrides `canAccess()` with that area's
permission.

## Admin Permission

`access admin panel` only opens the panel. Every module's admin area is closed by its own permission,
`manage {module}` (one per module), checked in `canAccess()` on each resource and page. That also hides
the navigation entry and every record page and action under the resource:

```php
public static function canAccess(): bool
{
    return auth()->user()?->can('manage blog') ?? false;
}
```

The module's `Database\Seeders\DatabaseSeeder` creates it with `Permission::findOrCreate('manage blog')`,
granted to nobody; the app's `DatabaseSeeder` decides which roles get it, and `admin` passes every
check. Add a policy only when a module needs finer control than the whole area (edit but not delete).

---

## Resource Pattern

```php
namespace Modules\Feature\Filament\Resources\Features;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Feature\Filament\Resources\Features\Pages;
use Modules\Feature\Filament\Resources\Features\Schemas\FeatureForm;
use Modules\Feature\Filament\Resources\Features\Tables\FeatureTable;
use Modules\Feature\Models\Feature;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return FeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit'   => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
```

---

## Form Schema Pattern

```php
namespace Modules\Feature\Filament\Resources\Features\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->nullable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
```

Use `->components([...])` (not `->schema([...])`). Layout components (`Grid`, `Section`) come from `Filament\Schemas\Components\`.

A `RichEditor` field whose HTML a page renders as markup (`v-html`, `dangerouslySetInnerHTML`) is cast with `Saucebase\Core\Casts\SanitizedHtml` on the model: `'description' => SanitizedHtml::class`. It runs `Str::sanitizeHtml()` on every read and stores what was written, so scripts and event handlers never reach a visitor, whoever wrote the value.

---

## Table Schema Pattern

```php
namespace Modules\Feature\Filament\Resources\Features\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeatureTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

**Filament v5 API:** use `->recordActions()` for row actions and `->toolbarActions()` for bulk/header actions. Never use the old `->actions()` / `->bulkActions()`.

---

## Infolist Schema Pattern

Used for read-only view pages. Infolist entries come from `Filament\Infolists\Components\`.

```php
namespace Modules\Feature\Filament\Resources\Features\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make()
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('created_at')->dateTime(),
                        IconEntry::make('is_active')->boolean()
                            ->trueColor('success')->falseColor('danger'),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
```

---

## Pages Pattern

**List page:**
```php
namespace Modules\Feature\Filament\Resources\Features\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Feature\Filament\Resources\Features\FeatureResource;

class ListFeatures extends ListRecords
{
    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
```

**Create page (with creator tracking):**
```php
class CreateFeature extends CreateRecord
{
    protected static string $resource = FeatureResource::class;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
```

**Edit page:**
```php
class EditFeature extends EditRecord
{
    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->requiresConfirmation(),
        ];
    }
}
```

---

## Correct Namespaces

| Component type | Namespace |
|---|---|
| Form fields (`TextInput`, `Select`, `Toggle`, etc.) | `Filament\Forms\Components\` |
| Infolist entries (`TextEntry`, `IconEntry`, etc.) | `Filament\Infolists\Components\` |
| Layout (`Grid`, `Section`, `Tabs`, `Wizard`, `Text`) | `Filament\Schemas\Components\` |
| Schema utilities (`Get`, `Set`) | `Filament\Schemas\Components\Utilities\` |
| Actions (all — `EditAction`, `DeleteAction`, `BulkActionGroup`, etc.) | `Filament\Actions\` |
| Table columns | `Filament\Tables\Columns\` |
| Table filters | `Filament\Tables\Filters\` |
| Icons | `Filament\Support\Icons\Heroicon` enum |

**Never** use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or other sub-namespaces for actions.

---

## Testing

Always authenticate before testing panel functionality. Tests must be PHPUnit classes:

```php
namespace Modules\Feature\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Feature\Filament\Resources\Features\Pages\CreateFeature;
use Modules\Feature\Filament\Resources\Features\Pages\ListFeatures;
use Modules\Feature\Models\Feature;
use Tests\TestCase;

class FeatureResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    public function test_can_list_features(): void
    {
        $features = Feature::factory()->count(3)->create();

        Livewire::test(ListFeatures::class)
            ->assertCanSeeTableRecords($features);
    }

    public function test_can_create_feature(): void
    {
        Livewire::test(CreateFeature::class)
            ->fillForm(['name' => 'Test Feature'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('features', ['name' => 'Test Feature']);
    }

    public function test_validates_required_fields(): void
    {
        Livewire::test(CreateFeature::class)
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }
}
```

Run with:
```bash
php -d memory_limit=2048M artisan test --testsuite=Modules --filter='^Modules\\Feature\\Tests'
```

---

## Common Mistakes

- **Wrong namespace**: module Filament classes live in `src/Filament/` under `Modules\Feature\Filament\`; `src` is not part of the namespace.
- **Old table API**: use `->recordActions()` / `->toolbarActions()`, never `->actions()` / `->bulkActions()`.
- **Action namespaces**: always import from `Filament\Actions\`, never from sub-namespaces.
- **Layout components**: `Grid`, `Section`, `Tabs` come from `Filament\Schemas\Components\`, not `Filament\Forms\Components\`.
- **Plugin not discovered**: the class must be `Modules\{Name}\Filament\{Name}Plugin`, with `{Name}` the studly-cased module directory name.
- **File visibility**: file uploads are `private` by default — use `->visibility('public')` when public access is needed.
- **Column spans**: `Grid` and `Section` do not span all columns by default — set `->columnSpanFull()` or `->columnSpan(n)` explicitly.
