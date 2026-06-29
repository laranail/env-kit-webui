<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\EnvKit\Headless\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\EnvKitException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\NotEditableException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProductionGuardException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProtectedKeyException;
use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;
use Simtabi\Laranail\EnvKit\WebUI\Http\Requests\StoreEnvVariableRequest;
use Simtabi\Laranail\EnvKit\WebUI\Http\Requests\UpdateEnvVariableRequest;

/**
 * The JSON CRUD surface. Drives the headless {@see EnvKit} engine — every write
 * flows through its atomic/guarded/audited commit path. Validation reuses the
 * headless rules (via the FormRequests); secrets are masked unless revealed.
 */
final class EnvController
{
    public function __construct(
        private readonly EnvKit $env,
        private readonly SecretRedactor $redactor,
    ) {}

    public function index(): JsonResponse
    {
        $reveal = $this->reveal();

        $data = [];
        foreach ($this->env->all() as $key => $value) {
            $data[] = $this->present($key, $value, $reveal);
        }

        return response()->json(['data' => $data]);
    }

    public function show(string $key): JsonResponse
    {
        abort_if($this->env->missing($key), 404);

        return response()->json([
            'data' => $this->present($key, $this->env->getString($key) ?? '', $this->reveal()),
        ]);
    }

    public function store(StoreEnvVariableRequest $request): JsonResponse
    {
        $key = (string) $request->string('key');
        $value = (string) $request->string('value');

        $this->guardWrite(fn () => $this->env->set($key, $value));

        return response()->json(['data' => $this->present($key, $value, $this->reveal())], 201);
    }

    public function update(UpdateEnvVariableRequest $request, string $key): JsonResponse
    {
        abort_if($this->env->missing($key), 404);

        $value = (string) $request->string('value');
        $this->guardWrite(fn () => $this->env->set($key, $value));

        return response()->json(['data' => $this->present($key, $value, $this->reveal())]);
    }

    public function destroy(string $key): JsonResponse
    {
        abort_if($this->env->missing($key), 404);

        $this->guardWrite(fn () => $this->env->forget($key));

        return response()->json(['data' => ['key' => $key, 'deleted' => true]]);
    }

    /** @return array{key: string, value: string, secret: bool} */
    private function present(string $key, string $value, bool $reveal): array
    {
        return [
            'key' => $key,
            'value' => $reveal ? $value : $this->redactor->forKey($key, $value),
            'secret' => $this->redactor->isSecretKey($key),
        ];
    }

    private function reveal(): bool
    {
        return (bool) config('env-kit-webui.reveal_secrets', false);
    }

    /** Map engine guard failures to HTTP statuses (messages are secret-safe). */
    private function guardWrite(Closure $action): void
    {
        try {
            $action();
        } catch (ProductionGuardException|ProtectedKeyException|NotEditableException $e) {
            abort(403, $e->getMessage());
        } catch (EnvKitException $e) {
            abort(422, $e->getMessage());
        }
    }
}
