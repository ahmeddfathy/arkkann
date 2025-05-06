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
            // Technical Team Review Permissions
            'view_technical_team_review',
            'create_technical_team_review',
            'update_technical_team_review',
            'delete_technical_team_review',
            // Marketing Review Permissions
            'view_marketing_review',
            'create_marketing_review',
            'update_marketing_review',
            'delete_marketing_review',
            // Customer Service Review Permissions
            'view_customer_service_review',
            'create_customer_service_review',
            'update_customer_service_review',
            'delete_customer_service_review',
            // Coordination Review Permissions
            'view_coordination_review',
            'create_coordination_review',
            'update_coordination_review',
            'delete_coordination_review',
            // Review Management Permission
            'manage_reviews',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Common roles
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $companyManager = Role::firstOrCreate(['name' => 'company_manager']);
        $projectManager = Role::firstOrCreate(['name' => 'project_manager']);
        $teamLeader = Role::firstOrCreate(['name' => 'team_leader']);
        $departmentManager = Role::firstOrCreate(['name' => 'department_manager']);

        // Department-specific roles
        $technicalTeamLeader = Role::firstOrCreate(['name' => 'technical_team_leader']);
        $marketingTeamLeader = Role::firstOrCreate(['name' => 'marketing_team_leader']);
        $customerServiceTeamLeader = Role::firstOrCreate(['name' => 'customer_service_team_leader']);
        $coordinationTeamLeader = Role::firstOrCreate(['name' => 'coordination_team_leader']);

        $technicalDepartmentManager = Role::firstOrCreate(['name' => 'technical_department_manager']);
        $marketingDepartmentManager = Role::firstOrCreate(['name' => 'marketing_department_manager']);
        $customerServiceDepartmentManager = Role::firstOrCreate(['name' => 'customer_service_department_manager']);
        $coordinationDepartmentManager = Role::firstOrCreate(['name' => 'coordination_department_manager']);

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

        // Basic manager permissions
        $managerBasePermissions = [
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
        ];

        $departmentManagerBasePermissions = array_merge($managerBasePermissions, [
            'view_department_data',
        ]);

        // Default general roles
        $teamLeader->givePermissionTo($managerBasePermissions);
        $departmentManager->givePermissionTo($departmentManagerBasePermissions);

        // Technical team leader specific permissions
        $technicalTeamLeader->givePermissionTo(array_merge($managerBasePermissions, [
            'view_technical_team_review',
            'create_technical_team_review',
            'update_technical_team_review',
            'delete_technical_team_review',
        ]));

        // Marketing team leader specific permissions
        $marketingTeamLeader->givePermissionTo(array_merge($managerBasePermissions, [
            'view_marketing_review',
            'create_marketing_review',
            'update_marketing_review',
            'delete_marketing_review',
        ]));

        // Customer service team leader specific permissions
        $customerServiceTeamLeader->givePermissionTo(array_merge($managerBasePermissions, [
            'view_customer_service_review',
            'create_customer_service_review',
            'update_customer_service_review',
            'delete_customer_service_review',
        ]));

        // Coordination team leader specific permissions
        $coordinationTeamLeader->givePermissionTo(array_merge($managerBasePermissions, [
            'view_coordination_review',
            'create_coordination_review',
            'update_coordination_review',
            'delete_coordination_review',
        ]));

        // Technical department manager specific permissions
        $technicalDepartmentManager->givePermissionTo(array_merge($departmentManagerBasePermissions, [
            'view_technical_team_review',
            'create_technical_team_review',
            'update_technical_team_review',
            'delete_technical_team_review',
        ]));

        // Marketing department manager specific permissions
        $marketingDepartmentManager->givePermissionTo(array_merge($departmentManagerBasePermissions, [
            'view_marketing_review',
            'create_marketing_review',
            'update_marketing_review',
            'delete_marketing_review',
        ]));

        // Customer service department manager specific permissions
        $customerServiceDepartmentManager->givePermissionTo(array_merge($departmentManagerBasePermissions, [
            'view_customer_service_review',
            'create_customer_service_review',
            'update_customer_service_review',
            'delete_customer_service_review',
        ]));

        // Coordination department manager specific permissions
        $coordinationDepartmentManager->givePermissionTo(array_merge($departmentManagerBasePermissions, [
            'view_coordination_review',
            'create_coordination_review',
            'update_coordination_review',
            'delete_coordination_review',
        ]));

        // Project manager can see all department reviews
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
            'view_technical_team_review',
            'create_technical_team_review',
            'update_technical_team_review',
            'delete_technical_team_review',
            'view_marketing_review',
            'create_marketing_review',
            'update_marketing_review',
            'delete_marketing_review',
            'view_customer_service_review',
            'create_customer_service_review',
            'update_customer_service_review',
            'delete_customer_service_review',
            'view_coordination_review',
            'create_coordination_review',
            'update_coordination_review',
            'delete_coordination_review',
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
            'view_technical_team_review',
            'update_technical_team_review',
            'view_marketing_review',
            'update_marketing_review',
            'view_customer_service_review',
            'update_customer_service_review',
            'view_coordination_review',
            'update_coordination_review',
            'manage_reviews',
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
            'view_technical_team_review',
            'create_technical_team_review',
            'update_technical_team_review',
            'delete_technical_team_review',
            'view_marketing_review',
            'create_marketing_review',
            'update_marketing_review',
            'delete_marketing_review',
            'view_customer_service_review',
            'create_customer_service_review',
            'update_customer_service_review',
            'delete_customer_service_review',
            'view_coordination_review',
            'create_coordination_review',
            'update_coordination_review',
            'delete_coordination_review',
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
