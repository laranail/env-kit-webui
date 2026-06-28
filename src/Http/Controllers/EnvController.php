<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;

/**
 * The JSON read surface. Drives the headless {@see EnvKitInterface} engine —
 * it never parses or mutates .env itself. Secret-shaped values are masked
 * unless `reveal_secrets` is enabled.
 */
final class EnvController
{
    public function __construct(
        private readonly EnvKitInterface $env,
        private readonly SecretRedactor $redactor,
    ) {}

    public function index(): JsonResponse
    {
        $reveal = (bool) config('env-kit-webui.reveal_secrets', false);

        $data = [];
        foreach ($this->env->all() as $key => $value) {
            $data[] = $this->present($key, $value, $reveal);
        }

        return response()->json(['data' => $data]);
    }

    public function show(string $key): JsonResponse
    {
        abort_if($this->env->missing($key), 404);

        $reveal = (bool) config('env-kit-webui.reveal_secrets', false);

        return response()->json([
            'data' => $this->present($key, $this->env->getString($key) ?? '', $reveal),
        ]);
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
}
