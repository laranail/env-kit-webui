<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Rules\MatchesEnvSchema;
use Simtabi\Laranail\EnvKit\Headless\Rules\ValidEnvKey;
use Simtabi\Laranail\EnvKit\Headless\Rules\ValidEnvValue;

/** Validates a new key/value, reusing the headless validation rules + engine schema. */
final class StoreEnvVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth is enforced by route middleware
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $key = is_string($this->input('key')) ? $this->input('key') : '';

        return [
            'key' => ['required', 'string', new ValidEnvKey],
            'value' => ['present', 'string', new ValidEnvValue, new MatchesEnvSchema(EnvKit::schema(), $key)],
        ];
    }
}
