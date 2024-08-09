import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Sys/**/*.php',
        './resources/views/filament/sys/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './app/Livewire/**/*.php',
        './resources/views/livewire/**/*.blade.php',
    ],
}
