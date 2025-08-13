<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class Roles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Crear permisos (o usar existentes)
        $viewBooks = Permission::firstOrCreate(['name' => 'view books']);
        $editBooks = Permission::firstOrCreate(['name' => 'edit books']);
        $viewReservations = Permission::firstOrCreate(['name' => 'view reservations']);
        $manageReservations = Permission::firstOrCreate(['name' => 'manage reservations']);
        
        // Asignar permisos a roles (sin duplicarlos)
        $admin->syncPermissions([
            $viewBooks, $editBooks, $viewReservations, $manageReservations
        ]);
        
        $supervisor->syncPermissions([
            $viewBooks, $editBooks, $viewReservations, $manageReservations
        ]);
        
        $user->syncPermissions([
            $viewBooks
        ]);
    }
}
