<?php

declare(strict_types=1);

use Filament\Contracts\Plugin;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPage;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPlugin;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

it('the Filament page honours the enabled flag and the optional gate', function () {
    config(['env-kit-webui.enabled' => false]);
    expect(EnvKitPage::canAccess())->toBeFalse(); // disabled → no access

    config(['env-kit-webui.enabled' => true, 'env-kit-webui.gate' => null]);
    expect(EnvKitPage::canAccess())->toBeTrue(); // enabled, no gate

    config(['env-kit-webui.gate' => 'manage-env']);
    Gate::define('manage-env', fn () => false);
    expect(EnvKitPage::canAccess())->toBeFalse(); // gate denies
});

it('ships a Filament page that surfaces the panel', function () {
    expect(class_exists(EnvKitPage::class))->toBeTrue()
        ->and(is_subclass_of(EnvKitPage::class, Page::class))->toBeTrue();
});

it('ships a Filament plugin that registers the page', function () {
    $plugin = EnvKitPlugin::make();

    expect($plugin)->toBeInstanceOf(Plugin::class)
        ->and($plugin->getId())->toBe('env-kit-webui');
});

it('ships a Nova tool when Nova is installed', function () {
    // Referenced by string so the class (which extends a Nova base) is never
    // autoloaded when Nova is absent.
    expect(is_subclass_of(
        'Simtabi\\Laranail\\EnvKit\\WebUI\\Adapters\\Nova\\EnvKitTool',
        'Laravel\\Nova\\Tool',
    ))->toBeTrue();
})->skip(! class_exists('Laravel\\Nova\\Nova'), 'Laravel Nova is not installed.');
