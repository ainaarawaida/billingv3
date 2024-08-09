

<x-filament-panels::page>
    <x-filament::breadcrumbs :breadcrumbs="[
        $settingurl => 'Document Setting',
        $settingurl.'/#' => 'Edit',
    ]" />
    <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                {{ __('Document Setting') }}
            </h1>

        <x-filament-panels::form wire:submit="save"> 
            {{ $this->form }}
    
            <x-filament-panels::form.actions 
                :actions="$this->getFormActions()"
            /> 
        </x-filament-panels::form>

</x-filament-panels::page>
