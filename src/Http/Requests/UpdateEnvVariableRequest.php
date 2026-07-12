<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Rules\MatchesEnvSchema;
use Simtabi\Laranail\EnvKit\Headless\Rules\ValidEnvValue;

/** Validates an updated value for an existing key, against the headless rules + engine schema. */
final class UpdateEnvVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth is enforced by route middleware
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $key = is_string($this->route('key')) ? $this->route('key') : '';

        return [
            'value' => ['present', 'string', new ValidEnvValue, new MatchesEnvSchema(EnvKit::schema(), $key)],
        ];
    }
}
