<?php

declare(strict_types=1);

use Filament\Panel;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Bootstrap\BootstrapAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPage;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPlugin;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\FilamentAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Events\AccessDenied;
use Simtabi\Laranail\EnvKit\WebUI\Http\ViewModels\EnvKitViewModel;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

it('the bootstrap + filament theme adapters expose a name and render the panel', function () {
    $vm = new EnvKitViewModel(variables: [], theme: 'x', apiPrefix: 'api/v1/env-kit', production: false);

    expect((new BootstrapAdapter)->name())->toBe('bootstrap')
        ->and((new BootstrapAdapter)->render($vm))->toBeInstanceOf(View::class)
        ->and((new FilamentAdapter)->name())->toBe('filament')
        ->and((new FilamentAdapter)->render($vm))->toBeInstanceOf(View::class);
});

it('the Filament plugin registers its page on a panel', function () {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('pages')->once()->with([EnvKitPage::class])->andReturnSelf();

    $plugin = EnvKitPlugin::make();
    $plugin->register($panel);
    $plugin->boot($panel); // no-op

    expect($plugin->getId())->toBe('env-kit-webui');
});

it('records the authenticated user id + reason on an access denial', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => true, 'env-kit-webui.access.allowed_ips' => ['10.0.0.0/8']]);
    Event::fake([AccessDenied::class]);

    $this->actingAs(new GenericUser(['id' => 7]))
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->getJson('api/v1/env-kit/keys')->assertForbidden();

    Event::assertDispatched(AccessDenied::class, fn (AccessDenied $e) => $e->userId === '7' && $e->reason === 'ip');
});

it('maps an engine validation failure (value too long) to 422', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => true, 'env-kit.limits.max_value_length' => 5]);

    $this->postJson('api/v1/env-kit/keys', ['key' => 'NEW', 'value' => 'waytoolong'])->assertStatus(422);
});
