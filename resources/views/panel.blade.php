<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme['root'] ?? '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('env-kit-webui::messages.title') }}</title>
</head>
<body class="{{ $theme['body'] ?? '' }}">
    <main>
        <h1 class="{{ $theme['heading'] ?? '' }}">
            {{ __('env-kit-webui::messages.heading', ['theme' => $vm->theme]) }}
        </h1>

        @if ($vm->production)
            <p class="{{ $theme['warning'] ?? '' }}" role="alert">
                {{ __('env-kit-webui::messages.production_warning') }}
            </p>
        @endif

        <table class="{{ $theme['table'] ?? '' }}">
            <thead>
                <tr>
                    <th scope="col">{{ __('env-kit-webui::messages.key') }}</th>
                    <th scope="col">{{ __('env-kit-webui::messages.value') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vm->variables as $variable)
                    <tr class="{{ $theme['row'] ?? '' }}">
                        <td class="{{ $theme['cell'] ?? '' }}">{{ $variable['key'] }}</td>
                        <td class="{{ $theme['cell'] ?? '' }}">{{ $variable['value'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">{{ __('env-kit-webui::messages.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
