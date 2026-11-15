<?php

namespace Saucebase\Core;

class ProjectConfig
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

    /** The template a template workspace develops (its folder under modules/). */
    public function template(): ?string
    {
        return $this->load()['template'] ?? null;
    }

    /** Names the template this app builds, in saucebase.json. */
    public function setTemplate(string $template): void
    {
        $this->data = [...$this->load(), 'template' => $template];

        file_put_contents(base_path('saucebase.json'), json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
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
