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
        $viewUser = Permission::firstOrCreate(['name' => 'view.recoleccion']);
        $editEmpadronado = Permission::firstOrCreate(['name' => 'edit.recoleccion']);
        $viewSupervisor = Permission::firstOrCreate(['name' => 'supervisor.recoleccion']);
        $manageRecolector = Permission::firstOrCreate(['name' => 'manage.recoleccion']);

    $verMisRecolecciones = Permission::firstOrCreate(['name' => 'verMisRecolecciones']);

        // Asignar permisos a roles (sin duplicarlos)
        $admin->syncPermissions([
            $viewUser, $verMisRecolecciones, $editEmpadronado, $viewSupervisor, $manageRecolector
        ]);
        
        $supervisor->syncPermissions([
            $viewUser, $verMisRecolecciones, $viewSupervisor, $manageRecolector
        ]);
        
        $user->syncPermissions([
            $viewUser, $verMisRecolecciones 
        ]);
    }
}
