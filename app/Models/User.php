<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'avatar',
        'status',
        'wework_userid',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 获取用户的角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * 获取用户创建的表单
     */
    public function forms()
    {
        return $this->hasMany(Form::class, 'created_by');
    }

    /**
     * 获取用户提交的表单数据
     */
    public function formSubmissions()
    {
        return $this->hasMany(FormData::class, 'submitted_by');
    }

    /**
     * 获取用户的审批记录
     */
    public function approvalRecords()
    {
        return $this->hasMany(ApprovalRecord::class, 'approver_id');
    }

    /**
     * 检查用户是否有指定角色
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('code', $role);
        }

        if (is_array($role)) {
            return $this->roles->whereIn('code', $role)->count() > 0;
        }

        return $this->roles->contains($role);
    }

    /**
     * 检查用户是否有指定权限
     */
    public function hasPermission($permission)
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->contains(function ($value) use ($permission) {
            if (is_string($permission)) {
                return $value->code === $permission;
            }
            return $value->id === $permission->id;
        });
    }

    /**
     * 更新最后登录时间
     */
    public function updateLastLoginAt()
    {
        $this->update(['last_login_at' => now()]);
    }
}