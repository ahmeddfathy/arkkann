<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;

use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\WorkShift;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions']);

        if ($request->has('employee_name') && !empty($request->employee_name)) {
            $query->where('name', 'like', "%{$request->employee_name}%");
        }

        if ($request->has('department') && !empty($request->department)) {
            $query->where('department', $request->department);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('employee_status', $request->status);
        }

        $users = $query->latest()->paginate(10);

        foreach ($users as $user) {
            $allPermissions = Permission::all();
        }

        $employees = User::select('name')->distinct()->get();
        $departments = User::select('department')->distinct()->whereNotNull('department')->get();
        $roles = Role::all();
        $permissions = Permission::all();

        return view('users.index', compact('users', 'employees', 'departments', 'roles', 'permissions'));
    }

    public function show($id)
    {
        $user = User::with('workShift')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }

    public function getRolePermissions($roleName)
    {
        try {
            $role = Role::findByName($roleName);
            if (!$role) {
                return response()->json([]);
            }

            $permissions = $role->permissions->pluck('name')->toArray();

            return response()->json($permissions);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function updateRolesAndPermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            if ($request->has('roles') && !empty($request->roles)) {
                $roleName = $request->roles[0];
                $role = Role::findByName($roleName);

                if (!$role) {
                    throw new \Exception("الرول '{$roleName}' غير موجود");
                }

                $rolePermissions = $role->permissions->pluck('name')->toArray();
                $requestedPermissions = $request->permissions ?? [];
                $permissionsToBlock = array_diff($rolePermissions, $requestedPermissions);

                foreach ($permissionsToBlock as $permission) {
                    $permissionId = Permission::where('name', $permission)->first()->id;
                    DB::table('model_has_permissions')
                        ->updateOrInsert(
                            [
                                'permission_id' => $permissionId,
                                'model_type' => get_class($user),
                                'model_id' => $user->id
                            ],
                            ['forbidden' => true]
                        );
                }

                DB::table('model_has_permissions')
                    ->where([
                        'model_type' => get_class($user),
                        'model_id' => $user->id,
                        'forbidden' => true
                    ])
                    ->whereIn('permission_id', Permission::whereIn('name', $requestedPermissions)->pluck('id'))
                    ->delete();

                $user->syncRoles([$role]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الأدوار والصلاحيات بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الأدوار والصلاحيات: ' . $e->getMessage()
            ], 500);
        }
    }


    public function import(Request $request)
    {
        Excel::import(new UsersImport, $request->file('file'));

        $employeeRole = Role::findByName('employee');
        User::whereDoesntHave('roles')->each(function ($user) use ($employeeRole) {
            $user->assignRole($employeeRole);
        });

        return redirect()->route('users.index')
            ->with('success', 'تم استيراد المستخدمين وتعيين الأدوار بنجاح');
    }

    public function removeRolesAndPermissions($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->syncRoles([]);
            $user->syncPermissions([]);

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إزالة جميع الأدوار والصلاحيات بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إزالة الأدوار والصلاحيات'
            ], 500);
        }
    }

    public function resetToEmployee($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->syncRoles([]);
            $user->syncPermissions([]);

            $user->assignRole('employee');

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
                'view_own_data'
            ];

            $user->syncPermissions($employeePermissions);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين المستخدم كموظف بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين المستخدم'
            ], 500);
        }
    }

    public function getEmployeesWithoutRole()
    {
        $usersWithoutRole = User::whereDoesntHave('roles')->get();
        return view('users.without_roles', compact('usersWithoutRole'));
    }

    public function assignEmployeeRole(Request $request)
    {
        $employeeRole = Role::findByName('employee');

        if ($request->has('user_ids')) {
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->assignRole($employeeRole);
                }
            }
        }

        return redirect()->back()->with('success', 'تم تعيين دور الموظف بنجاح');
    }

    public function getForbiddenPermissions($id)
    {
        $user = User::findOrFail($id);

        $forbiddenPermissions = DB::table('model_has_permissions')
            ->where([
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'forbidden' => true
            ])
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->pluck('permissions.name')
            ->toArray();

        return response()->json($forbiddenPermissions);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $workShifts = WorkShift::where('is_active', true)->get(); // تحميل الورديات النشطة فقط
        return view('users.edit', compact('user', 'workShifts'));
    }

    /**
     * عرض صفحة تعيين الورديات للمستخدمين
     *
     * @return \Illuminate\Http\Response
     */
    public function assignWorkShifts()
    {
        $users = User::orderBy('name')->get();
        $workShifts = WorkShift::where('is_active', true)->get();

        return view('users.assign-work-shifts', compact('users', 'workShifts'));
    }

    /**
     * حفظ تعيينات الورديات للمستخدمين
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveWorkShifts(Request $request)
    {
        $validated = $request->validate([
            'work_shifts' => 'required|array',
            'work_shifts.*' => 'nullable|exists:work_shifts,id',
        ]);

        foreach ($validated['work_shifts'] as $userId => $workShiftId) {
            User::where('id', $userId)->update(['work_shift_id' => $workShiftId]);
        }

        return redirect()->route('users.assign-work-shifts')
            ->with('success', 'تم تعيين الورديات للمستخدمين بنجاح.');
    }
}
