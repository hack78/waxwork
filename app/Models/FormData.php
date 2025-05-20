<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormData extends Model
{
    use HasFactory;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'form_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'data',
        'status',
        'submitted_by',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * 模型的"启动"方法
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // 创建时自动设置submitted_at
        static::creating(function ($model) {
            $model->submitted_at = $model->submitted_at ?? now();
        });
    }

    /**
     * 获取表单数据所属的表单
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * 获取表单数据的提交者
     */
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * 获取表单数据的文件
     */
    public function files()
    {
        return $this->hasMany(FormDataFile::class);
    }

    /**
     * 获取表单数据的审批记录
     */
    public function approvalRecords()
    {
        return $this->hasMany(ApprovalRecord::class, 'form_data_id');
    }

    /**
     * 获取当前审批记录
     */
    public function currentApprovalRecord()
    {
        return $this->approvalRecords()
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();
    }

    /**
     * 获取表单数据的字段值
     */
    public function getFieldValue($fieldName)
    {
        return $this->data[$fieldName] ?? null;
    }

    /**
     * 设置表单数据的字段值
     */
    public function setFieldValue($fieldName, $value)
    {
        $data = $this->data;
        $data[$fieldName] = $value;
        $this->data = $data;
        return $this;
    }

    /**
     * 获取格式化后的表单数据
     */
    public function getFormattedData()
    {
        $result = [];
        $fields = $this->form->fields;

        foreach ($fields as $field) {
            $value = $this->getFieldValue($field->name);
            $result[$field->name] = [
                'label' => $field->label,
                'value' => $value,
                'formatted_value' => $field->formatValue($value),
                'type' => $field->type,
            ];
        }

        return $result;
    }

    /**
     * 检查表单数据是否已提交
     */
    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    /**
     * 检查表单数据是否正在处理中
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * 检查表单数据是否已完成
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * 检查表单数据是否已拒绝
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * 更新表单数据状态
     */
    public function updateStatus($status)
    {
        $this->update(['status' => $status]);
        return $this;
    }
}