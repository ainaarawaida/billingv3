import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Home/**/*.php',
        './resources/views/filament/home/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
