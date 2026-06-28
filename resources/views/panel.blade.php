<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EnvKit</title>
</head>
<body class="{{ $theme['body'] ?? '' }}">
    <main>
        <h1 class="{{ $theme['heading'] ?? '' }}">EnvKit &mdash; {{ $vm->theme }}</h1>

        @if ($vm->production)
            <p class="{{ $theme['warning'] ?? '' }}" role="alert">
                Production environment &mdash; writes are guarded.
            </p>
        @endif

        <table class="{{ $theme['table'] ?? '' }}">
            <thead>
                <tr>
                    <th scope="col">Key</th>
                    <th scope="col">Value</th>
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
                        <td colspan="2">No variables.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
