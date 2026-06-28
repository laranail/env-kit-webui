<?php

declare(strict_types=1);

use Livewire\Livewire;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

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
