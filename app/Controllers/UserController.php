<?php

namespace App\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 获取用户列表
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'keyword' => 'nullable|string',
            'department' => 'nullable|string',
        ]);

        $query = User::query()
            ->when($request->keyword, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                  ->orWhere('username', 'like', "%{$request->keyword}%");
            })
            ->when($request->department, function ($q) use ($request) {
                $q->where('department', $request->department);
            });

        $users = $query->paginate($request->limit ?? 20);

        return response()->json([
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'data' => $users->items(),
        ]);
    }

    /**
     * 创建用户
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:5|max:20|unique:users',
            'password' => 'required|string|min:8|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:20',
            'department' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:50',
            'status' => 'required|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'department' => $request->department,
            'position' => $request->position,
            'status' => $request->status,
        ]);

        if ($request->role_ids) {
            $user->roles()->sync($request->role_ids);
        }

        return response()->json($user, 201);
    }

    /**
     * 获取用户详情
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    /**
     * 更新用户
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|max:50',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|string|max:20',
            'department' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:50',
            'status' => 'sometimes|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user->update($request->only([
            'name', 'email', 'phone', 'department', 'position', 'status'
        ]));

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        return response()->json($user);
    }

    /**
     * 删除用户
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|max:20',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => '密码重置成功']);
    }
}