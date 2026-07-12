<?php

declare(strict_types=1);

use Livewire\Livewire;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(fn () => config(['env-kit-webui.enabled' => true]));

it('lists variables and masks secrets in the reactive panel', function () {
    $this->bindEnv("APP_NAME=Acme\nDB_PASSWORD=topsecret123\n");

    Livewire::test(EnvKitPanelComponent::class)
        ->assertSee('APP_NAME')
        ->assertDontSee('topsecret123');
});

it('edits a value reactively through the engine', function () {
    $this->bindEnv("A=1\n");

    Livewire::test(EnvKitPanelComponent::class)
        ->call('startEditing', 'A')
        ->assertSet('editingKey', 'A')
        ->set('draft', '2')
        ->call('save')
        ->assertSet('editingKey', null);

    expect(EnvKit::get('A'))->toBe('2');
});

it('cancels an edit without writing', function () {
    $this->bindEnv("A=1\n");

    Livewire::test(EnvKitPanelComponent::class)
        ->call('startEditing', 'A')
        ->set('draft', '999')
        ->call('cancel')
        ->assertSet('editingKey', null);

    expect(EnvKit::get('A'))->toBe('1');
});

it('the panel-access gate honours enabled (the mount() guard)', function () {
    config(['env-kit-webui.enabled' => false]);
    expect(PanelAccess::allowed())->toBeFalse();

    config(['env-kit-webui.enabled' => true]);
    expect(PanelAccess::allowed())->toBeTrue();
});

it('does not expose any env data when disabled', function () {
    $this->bindEnv("CANARY_KEY=visible\n");
    config(['env-kit-webui.enabled' => false]);

    // mount() aborts before reading the engine, so nothing leaks.
    Livewire::test(EnvKitPanelComponent::class)->assertDontSee('CANARY_KEY');
});

it('surfaces a guard failure inline instead of crashing', function () {
    $this->bindEnv("DB_PASSWORD=secret\n", ['env-kit.auto_backup' => false]);

    Livewire::test(EnvKitPanelComponent::class)
        ->call('startEditing', 'DB_PASSWORD') // protected key
        ->set('draft', 'new')
        ->call('save')
        ->assertHasErrors('draft')
        ->assertSet('editingKey', 'DB_PASSWORD'); // stays in edit mode, not committed

    expect(EnvKit::get('DB_PASSWORD'))->toBe('secret'); // unchanged
});
