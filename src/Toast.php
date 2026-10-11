<?php

namespace Saucebase\Core;

use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Saucebase\Core\Data\ToastActionData;
use Saucebase\Core\Data\ToastData;
use Saucebase\Core\Enums\ToastPosition;
use Saucebase\Core\Enums\ToastType;
use Spatie\LaravelData\Optional;

class Toast
{
    const TYPE_DEFAULT = ToastType::Default->value;

    const TYPE_SUCCESS = ToastType::Success->value;

    const TYPE_ERROR = ToastType::Error->value;

    const TYPE_INFO = ToastType::Info->value;

    const TYPE_WARNING = ToastType::Warning->value;

    const TYPE_LOADING = ToastType::Loading->value;

    const POSITION_TOP_LEFT = ToastPosition::TopLeft->value;

    const POSITION_TOP_CENTER = ToastPosition::TopCenter->value;

    const POSITION_TOP_RIGHT = ToastPosition::TopRight->value;

    const POSITION_BOTTOM_LEFT = ToastPosition::BottomLeft->value;

    const POSITION_BOTTOM_CENTER = ToastPosition::BottomCenter->value;

    const POSITION_BOTTOM_RIGHT = ToastPosition::BottomRight->value;

    const POSITION_DEFAULT = self::POSITION_BOTTOM_RIGHT;

    /**
     * What the frontend receives. An unknown type or position falls back to the default
     * rather than failing a request over a toast.
     *
     * @param  array{label: string, type?: string, onClick?: string}|null  $action
     * @return array<string, mixed>
     */
    private static function payload(
        string $message,
        string $type,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): array {
        return (new ToastData(
            message: $message,
            type: ToastType::tryFrom($type) ?? ToastType::Default,
            description: $description ?? new Optional,
            action: $action === null ? new Optional : ToastActionData::from($action),
            duration: $duration ?? new Optional,
            position: $position === null ? new Optional : (ToastPosition::tryFrom($position) ?? ToastPosition::from(self::POSITION_DEFAULT)),
        ))->toArray();
    }

    /**
     * Show a toast notification.
     */
    public static function show(
        string $message,
        string $type = self::TYPE_SUCCESS,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        $toast = self::payload($message, $type, $description, $action, $duration, $position);

        // Inertia request (XHR)
        if (request()->header('X-Inertia')) {
            Inertia::flash('toast', $toast);

            return;
        }

        // Non-Inertia request (OAuth / full page redirect)
        Session::put('toast', $toast);
    }

    public static function default(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_DEFAULT, $description, $action, $duration, $position);
    }

    public static function success(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_SUCCESS, $description, $action, $duration, $position);
    }

    public static function error(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_ERROR, $description, $action, $duration, $position);
    }

    public static function info(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_INFO, $description, $action, $duration, $position);
    }

    public static function warning(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_WARNING, $description, $action, $duration, $position);
    }

    public static function loading(
        string $message,
        ?string $description = null,
        ?array $action = null,
        ?int $duration = null,
        ?string $position = null
    ): void {
        self::show($message, self::TYPE_LOADING, $description, $action, $duration, $position);
    }
}
