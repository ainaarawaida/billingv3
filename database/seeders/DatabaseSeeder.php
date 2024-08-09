<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'luqman',
            'email' => 'luqman@test.com',
            'password' => Hash::make('luqman1234'),
        ]);

        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            RoleSeeder::class,
            PaymentMethodSeeder::class,
            CustomerSeeder::class, // Example seeder class
            ProductSeeder::class,
            QuotationSeeder::class, 
            InvoiceSeeder::class,   // Example seeder class
            RecurringInvoiceSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
