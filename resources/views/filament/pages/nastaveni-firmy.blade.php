<x-filament-panels::page>
    <form wire:submit="save" class="fi-form">
        {{ $this->form }}

        <div style="margin-top:1.25rem">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Uložit nastavení
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
