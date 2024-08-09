<x-filament-panels::page>

<div class="bg-gray-900 text-white">
    <header class="flex flex-col justify-center items-center">
        <h1 class="text-4xl font-bold p-5">Streamline Your Billing</h1>
        <p class="text-lg mt-4">Effortless management for your finances.</p>
        <div class="flex space-x-4 mt-8">
            <a href="{{ url('/sys/register') }}" wire:navigate class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Register</a>
            <a href="{{ url('/sys/login') }}" wire:navigate class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">Login</a>
        </div>
    </header>
    <main class="container mx-auto py-12 flex flex-col justify-center items-center">
        <h2 class="text-2xl font-bold">Free Online Billing System</h2>
        <p class="text-gray-400 mt-4">Create and share invoices in 1-click. Collect faster payments with auto-reminders. Get insightful reports.</p>
        <div class="flex flex-wrap justify-center mt-8">
            </div>
    </main>
   
</div>
</x-filament-panels::page>
