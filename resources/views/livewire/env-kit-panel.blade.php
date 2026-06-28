<div>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('env-kit-webui::messages.key') }}</th>
                <th scope="col">{{ __('env-kit-webui::messages.value') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($variables as $variable)
                <tr wire:key="{{ $variable['key'] }}">
                    <td>{{ $variable['key'] }}</td>
                    <td>
                        @if ($editingKey === $variable['key'])
                            <input type="text" wire:model="draft" aria-label="{{ $variable['key'] }}">
                            <button type="button" wire:click="save">Save</button>
                            <button type="button" wire:click="cancel">Cancel</button>
                        @else
                            <span>{{ $variable['value'] }}</span>
                            <button type="button" wire:click="startEditing('{{ $variable['key'] }}')">Edit</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">{{ __('env-kit-webui::messages.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
