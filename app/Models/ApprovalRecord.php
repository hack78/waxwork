<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'flow_id',
        'node_id',
        'form_data_id',
        'approver_id',
        'status',
        'comment',
        'sp_no',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * 获取审批记录所属的审批流程
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * 获取审批记录所属的审批节点
     */
    public function node()
    {
        return $this->belongsTo(ApprovalNode::class, 'node_id');
    }

    /**
     * 获取审批记录所属的表单数据
     */
    public function formData()
    {
        return $this->belongsTo(FormData::class, 'form_data_id');
    }

    /**
     * 获取审批记录的审批人
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * 获取审批记录的附件
     */
    public function attachments()
    {
        return $this->hasMany(ApprovalAttachment::class, 'record_id');
    }

    /**
     * 检查审批记录是否待审批
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * 检查审批记录是否已通过
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * 检查审批记录是否已拒绝
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * 审批通过
     */
    public function approve($comment = null)
    {
        $this->update([
            'status' => 'approved',
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // 处理下一个审批节点
        $this->flow->handleApproval($this, 'approved', $comment);
    }

    /**
     * 审批拒绝
     */
    public function reject($comment = null)
    {
        $this->update([
            'status' => 'rejected',
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // 更新表单数据状态
        $this->formData->updateStatus('rejected');
    }

    /**
     * 获取审批耗时（小时）
     */
    public function getApprovalTime()
    {
        if (!$this->approved_at) {
            return null;
        }

        return $this->approved_at->diffInHours($this->formData->submitted_at);
    }

    /**
     * 获取审批详情
     */
    public function getDetails()
    {
        return [
            'id' => $this->id,
            'flow_name' => $this->flow->name,
            'node_name' => $this->node->name,
            'approver' => $this->approver->name,
            'status' => $this->status,
            'comment' => $this->comment,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approval_time' => $this->getApprovalTime(),
            'form_data' => $this->formData->getFormattedData(),
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'url' => $attachment->url,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->formatted_size,
                ];
            }),
        ];
    }

    /**
     * 添加审批附件
     */
    public function addAttachment($file)
    {
        $path = $file->store('approval_attachments');

        return $this->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}