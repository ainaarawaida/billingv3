<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
 

        Permission::create(['name' => 'viewany']);
        $roleAdmin = Role::create(['name' => 'admin','guard_name' => 'web']);
        $roleAdmin->givePermissionTo(['viewany']);

        $roleCustomer = Role::create(['name' => 'customer','guard_name' => 'web']);
        $roleCustomer->givePermissionTo(['viewany']);
        
        $user = User::all();
        foreach($user AS $key => $val){
            $val->assignRole($roleAdmin);
            $val->assignRole($roleCustomer);
        }

        
    
    }
}
