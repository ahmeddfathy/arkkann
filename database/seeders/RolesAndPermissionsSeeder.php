<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
  public function run()
  {
    $permissions = [
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'hr_respond_absence_request',
      'manager_respond_absence_request',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'hr_respond_permission_request',
      'manager_respond_permission_request',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'hr_respond_overtime_request',
      'manager_respond_overtime_request',
      'view_own_data',
      'view_team_data',
      'view_department_data',
      'view_all_data',
    ];

    foreach ($permissions as $permission) {
      Permission::firstOrCreate(['name' => $permission]);
    }

    $employee = Role::firstOrCreate(['name' => 'employee']);
    $teamLeader = Role::firstOrCreate(['name' => 'team_leader']);
    $departmentManager = Role::firstOrCreate(['name' => 'department_manager']);
    $projectManager = Role::firstOrCreate(['name' => 'project_manager']);
    $hr = Role::firstOrCreate(['name' => 'hr']);
    $companyManager = Role::firstOrCreate(['name' => 'company_manager']);

    $employee->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'view_own_data',
    ]);

    $teamLeader->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'manager_respond_absence_request',
      'manager_respond_permission_request',
      'manager_respond_overtime_request',
      'view_own_data',
      'view_team_data',
    ]);

    $departmentManager->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'manager_respond_absence_request',
      'manager_respond_permission_request',
      'manager_respond_overtime_request',
      'view_own_data',
      'view_department_data',
    ]);

    $projectManager->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'manager_respond_absence_request',
      'manager_respond_permission_request',
      'manager_respond_overtime_request',
      'view_own_data',
      'view_department_data',
      'view_team_data',
    ]);

    $hr->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'hr_respond_absence_request',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'hr_respond_permission_request',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'hr_respond_overtime_request',
      'view_all_data',
    ]);

    $companyManager->givePermissionTo([
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'hr_respond_absence_request',
      'manager_respond_absence_request',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'hr_respond_permission_request',
      'manager_respond_permission_request',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'hr_respond_overtime_request',
      'manager_respond_overtime_request',
      'view_all_data',
    ]);

    $users = User::whereDoesntHave('roles')->get();
    foreach ($users as $user) {
      $user->assignRole('employee');
    }

    $employeePermissions = [
      'view_absence',
      'create_absence',
      'update_absence',
      'delete_absence',
      'view_permission',
      'create_permission',
      'update_permission',
      'delete_permission',
      'view_overtime',
      'create_overtime',
      'update_overtime',
      'delete_overtime',
      'view_own_data',
    ];

    User::all()->each(function ($user) use ($employeePermissions) {
      $user->givePermissionTo($employeePermissions);
    });

    $mandatoryPermissions = [
      'employee' => ['view_own_data'],
      'manager' => ['view_own_data', 'view_team_data'],
      'hr' => ['view_own_data', 'view_all_data']
    ];
  }
}
