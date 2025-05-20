<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
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
     * 获取表单的字段
     */
    public function fields()
    {
        return $this->hasMany(FormField::class)
            ->orderBy('order');
    }

    /**
     * 获取表单的提交数据
     */
    public function submissions()
    {
        return $this->hasMany(FormData::class);
    }

    /**
     * 获取表单的审批流程
     */
    public function approvalFlow()
    {
        return $this->hasOne(ApprovalFlow::class);
    }

    /**
     * 获取表单创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取表单更新者
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 检查是否是审批表单
     */
    public function isApprovalForm()
    {
        return $this->type === 'approval';
    }

    /**
     * 获取表单的必填字段
     */
    public function getRequiredFields()
    {
        return $this->fields()->where('required', true)->get();
    }

    /**
     * 验证表单数据
     */
    public function validateData($data)
    {
        $rules = [];
        $messages = [];

        foreach ($this->fields as $field) {
            if ($field->required) {
                $rules["data.{$field->name}"] = 'required';
                $messages["data.{$field->name}.required"] = "{$field->label}不能为空";
            }

            if ($field->validation_rules) {
                $rules["data.{$field->name}"] = array_merge(
                    $rules["data.{$field->name}"] ?? [],
                    $field->validation_rules
                );
            }
        }

        return validator($data, $rules, $messages);
    }

    /**
     * 提交表单数据
     */
    public function submit($data, $userId)
    {
        // 验证数据
        $validator = $this->validateData($data);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // 创建提交记录
        $submission = $this->submissions()->create([
            'data' => $data,
            'status' => 'submitted',
            'submitted_by' => $userId,
        ]);

        // 如果是审批表单，创建审批记录
        if ($this->isApprovalForm() && $this->approvalFlow) {
            $this->approvalFlow->createApprovalRecord($submission);
        }

        return $submission;
    }

    /**
     * 获取表单统计数据
     */
    public function getStatistics()
    {
        return [
            'total_submissions' => $this->submissions()->count(),
            'today_submissions' => $this->submissions()
                ->whereDate('created_at', today())
                ->count(),
            'pending_approvals' => $this->isApprovalForm() ? 
                $this->submissions()
                    ->whereHas('approvalRecords', function ($query) {
                        $query->where('status', 'pending');
                    })
                    ->count() : 0,
        ];
    }
}