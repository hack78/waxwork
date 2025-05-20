<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
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
        'type',
        'parent_id',
        'path',
        'icon',
        'sort',
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
     * 获取父级权限
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 获取子权限
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id')
            ->orderBy('sort');
    }

    /**
     * 获取拥有此权限的角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * 检查是否是菜单权限
     */
    public function isMenu()
    {
        return $this->type === 'menu';
    }

    /**
     * 检查是否是按钮权限
     */
    public function isButton()
    {
        return $this->type === 'button';
    }

    /**
     * 检查是否是API权限
     */
    public function isApi()
    {
        return $this->type === 'api';
    }

    /**
     * 获取完整的权限路径
     */
    public function getFullPathAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_path . '/' . $this->path;
        }
        return $this->path;
    }
}