<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Livewire;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simtabi\Laranail\EnvKit\Headless\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;

/**
 * Optional reactive panel. Registered only when Livewire is installed (the
 * provider class_exists-guards it). Inline edits drive the headless engine, so
 * they inherit its atomic/guarded/audited commit path.
 */
final class EnvKitPanelComponent extends Component
{
    /** @var list<array{key: string, value: string, secret: bool}> */
    public array $variables = [];

    public ?string $editingKey = null;

    public string $draft = '';

    public function mount(): void
    {
        $this->refreshVariables();
    }

    public function startEditing(string $key): void
    {
        $this->editingKey = $key;
        $this->draft = $this->engine()->getString($key) ?? '';
    }

    public function cancel(): void
    {
        $this->editingKey = null;
        $this->draft = '';
    }

    public function save(): void
    {
        if ($this->editingKey === null) {
            return;
        }

        $this->engine()->set($this->editingKey, $this->draft);
        $this->cancel();
        $this->refreshVariables();
    }

    public function render(): View
    {
        return app(ViewFactory::class)->make('env-kit-webui::livewire.env-kit-panel');
    }

    private function refreshVariables(): void
    {
        $redactor = app(SecretRedactor::class);
        $reveal = (bool) config('env-kit-webui.reveal_secrets', false);

        $this->variables = [];
        foreach ($this->engine()->all() as $key => $value) {
            $this->variables[] = [
                'key' => $key,
                'value' => $reveal ? $value : $redactor->forKey($key, $value),
                'secret' => $redactor->isSecretKey($key),
            ];
        }
    }

    private function engine(): EnvKit
    {
        return app(EnvKit::class);
    }
}
