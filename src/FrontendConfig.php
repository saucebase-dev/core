<?php

namespace Saucebase\Core;

class FrontendConfig
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function getFramework(): ?string
    {
        return $this->load()['framework'] ?? null;
    }

    /** A checkout of Saucebase itself: both frameworks side by side, the UI read from core. */
    public function isContributor(): bool
    {
        return ($this->load()['contributor'] ?? false) === true;
    }

    /** Whether pages may render on the server. On unless the project turned it off. */
    public function ssr(): bool
    {
        return ($this->load()['ssr'] ?? true) !== false;
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        if ($this->data === null) {
            $this->data = @json_decode((string) @file_get_contents(base_path('saucebase.json')), true) ?? [];
        }

        return $this->data;
    }
}
