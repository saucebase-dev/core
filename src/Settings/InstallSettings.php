<?php

namespace Saucebase\Core\Settings;

use BackedEnum;
use Illuminate\Support\Facades\Validator;
use InterNACHI\Modular\Support\ModuleConfig;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelSettings\Settings;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * A module's settings marked {@see InstallSetting}: found by reflection in its
 * `src/Settings`, cast from what a template, a flag or a prompt gives, validated, saved.
 */
final class InstallSettings
{
    /**
     * @param  array<string, array{class: class-string<Settings>, property: ReflectionProperty, setting: InstallSetting}>  $marked
     * @param  list<string>  $unmarked  The module's other settings: never set at install.
     */
    private function __construct(public readonly string $module, private readonly array $marked, private readonly array $unmarked) {}

    public static function of(ModuleConfig $module): self
    {
        $marked = [];
        $unmarked = [];

        foreach (glob($module->path('src/Settings').'/*.php') ?: [] as $file) {
            $class = $module->pathToFullyQualifiedClassName($file);

            if (! class_exists($class) || ! is_subclass_of($class, Settings::class)) {
                continue;
            }

            foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $attribute = $property->getAttributes(InstallSetting::class)[0] ?? null;

                if ($attribute !== null) {
                    $marked[$property->getName()] = ['class' => $class, 'property' => $property, 'setting' => $attribute->newInstance()];
                } elseif ($property->getDeclaringClass()->getName() === $class) {
                    $unmarked[] = $property->getName();
                }
            }
        }

        return new self($module->name, $marked, $unmarked);
    }

    public function marks(string $name): bool
    {
        return isset($this->marked[$name]);
    }

    /** A setting of this module's that is not marked: refused, so a template can't reach it. */
    public function declares(string $name): bool
    {
        return in_array($name, $this->unmarked, true);
    }

    public function isEmpty(): bool
    {
        return $this->marked === [];
    }

    /**
     * The value as the property's type, checked by its rules.
     *
     * @throws InvalidArgumentException Why the value is refused.
     */
    public function cast(string $name, mixed $value): mixed
    {
        if (! isset($this->marked[$name])) {
            throw new InvalidArgumentException("{$this->module}.{$name} can't be set at install: it is not marked #[InstallSetting].");
        }

        ['property' => $property, 'setting' => $setting] = $this->marked[$name];
        $type = $property->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';

        if ($type?->allowsNull() && ($value === null || $value === '')) {
            return null;
        }

        $cast = match (true) {
            $typeName === 'bool' => is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            $typeName === 'int' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value)) ? (int) $value : null,
            $typeName === 'string' => is_scalar($value) ? (string) $value : null,
            is_subclass_of($typeName, BackedEnum::class) => is_int($value) || is_string($value) ? $typeName::tryFrom($value) : null,
            default => throw new InvalidArgumentException("{$this->module}.{$name} has a type install settings don't support yet: {$typeName}."),
        };

        if ($cast === null) {
            throw new InvalidArgumentException("{$this->module}.{$name} must be a {$this->describe($typeName)}: ".json_encode($value).'.');
        }

        // The type's own rule first: `between` and `max` measure a number only next to one.
        $rules = [...($typeName === 'int' ? ['integer'] : []), ...$setting->rules];
        $validator = Validator::make(['value' => $cast instanceof BackedEnum ? $cast->value : $cast], ['value' => $rules]);

        if ($validator->fails()) {
            throw new InvalidArgumentException("{$this->module}.{$name}: ".$validator->errors()->first('value'));
        }

        return $cast;
    }

    /**
     * Asks for each setting not in `$skip`, the prompt picked by its type.
     *
     * @param  array<string, mixed>  $defaults  Cast values to start from (a template's); else the current one.
     * @param  list<string>  $skip  Set by a flag: not asked.
     * @return array<string, mixed>
     */
    public function ask(array $defaults, array $skip): array
    {
        $answers = [];

        foreach ($this->marked as $name => ['class' => $class, 'property' => $property, 'setting' => $setting]) {
            if (in_array($name, $skip, true)) {
                continue;
            }

            $default = array_key_exists($name, $defaults) ? $defaults[$name] : app($class)->{$name};
            $typeName = $property->getType() instanceof ReflectionNamedType ? $property->getType()->getName() : 'mixed';
            $label = __($setting->label);
            $hint = $setting->hint ? __($setting->hint) : '';
            $validate = function (string $value) use ($name): ?string {
                try {
                    $this->cast($name, $value);

                    return null;
                } catch (InvalidArgumentException $e) {
                    return $e->getMessage();
                }
            };

            $answer = match (true) {
                $typeName === 'bool' => confirm($label, (bool) $default, hint: $hint),
                is_subclass_of($typeName, BackedEnum::class) => select(
                    $label,
                    collect($typeName::cases())->mapWithKeys(fn (BackedEnum $case) => [$case->value => method_exists($case, 'getLabel') ? $case->getLabel() : $case->name])->all(),
                    $default instanceof BackedEnum ? $default->value : $default,
                    hint: $hint,
                ),
                default => text($label, default: (string) $default, validate: $validate, hint: $hint),
            };

            $answers[$name] = $this->cast($name, $answer);
        }

        return $answers;
    }

    /** @param  array<string, mixed>  $values  Cast values, by property. */
    public function save(array $values): void
    {
        foreach (collect($values)->groupBy(fn ($value, string $name) => $this->marked[$name]['class'], preserveKeys: true) as $class => $group) {
            $settings = app($class);

            foreach ($group as $name => $value) {
                $settings->{$name} = $value;
            }

            $settings->save();
        }
    }

    private function describe(string $typeName): string
    {
        return match (true) {
            $typeName === 'bool' => 'yes or no',
            $typeName === 'int' => 'whole number',
            is_subclass_of($typeName, BackedEnum::class) => 'one of '.implode(', ', array_map(fn (BackedEnum $case) => $case->value, $typeName::cases())),
            default => $typeName,
        };
    }
}
