<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * 获取拥有此角色的用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * 获取角色的权限
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * 检查角色是否有指定权限
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions->contains('code', $permission);
        }

        if (is_array($permission)) {
            return $this->permissions->whereIn('code', $permission)->count() > 0;
        }

        return $this->permissions->contains($permission);
    }

    /**
     * 为角色分配权限
     */
    public function assignPermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('code', $permission)->first();
        }

        if (is_array($permission)) {
            return $this->permissions()->syncWithoutDetaching($permission);
        }

        return $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * 移除角色的权限
     */
    public function removePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('code', $permission)->first();
        }

        if (is_array($permission)) {
            return $this->permissions()->detach($permission);
        }

        return $this->permissions()->detach($permission);
    }
}