<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'form_id',
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
     * 获取审批流程所属的表单
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * 获取审批流程的节点
     */
    public function nodes()
    {
        return $this->hasMany(ApprovalNode::class, 'flow_id')
            ->orderBy('order');
    }

    /**
     * 获取审批流程的记录
     */
    public function records()
    {
        return $this->hasMany(ApprovalRecord::class, 'flow_id');
    }

    /**
     * 获取审批流程的创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取审批流程的更新者
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 获取第一个审批节点
     */
    public function getFirstNode()
    {
        return $this->nodes()
            ->where('status', true)
            ->orderBy('order')
            ->first();
    }

    /**
     * 获取下一个审批节点
     */
    public function getNextNode($currentNode)
    {
        return $this->nodes()
            ->where('status', true)
            ->where('order', '>', $currentNode->order)
            ->orderBy('order')
            ->first();
    }

    /**
     * 创建审批记录
     */
    public function createApprovalRecord($formData)
    {
        $firstNode = $this->getFirstNode();
        if (!$firstNode) {
            return null;
        }

        // 获取审批人
        $approverId = $firstNode->getApproverId($formData->submitter);
        if (!$approverId) {
            return null;
        }

        // 创建审批记录
        return ApprovalRecord::create([
            'flow_id' => $this->id,
            'node_id' => $firstNode->id,
            'form_data_id' => $formData->id,
            'approver_id' => $approverId,
            'status' => 'pending',
        ]);
    }

    /**
     * 处理审批操作
     */
    public function handleApproval($record, $action, $comment = null)
    {
        // 更新当前审批记录
        $record->update([
            'status' => $action,
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // 如果是拒绝，直接结束审批流程
        if ($action === 'rejected') {
            $record->formData->updateStatus('rejected');
            return;
        }

        // 获取下一个节点
        $currentNode = $record->node;
        $nextNode = $this->getNextNode($currentNode);

        // 如果没有下一个节点，表示审批流程结束
        if (!$nextNode) {
            $record->formData->updateStatus('completed');
            return;
        }

        // 创建下一个节点的审批记录
        $approverId = $nextNode->getApproverId($record->formData->submitter);
        if (!$approverId) {
            return;
        }

        return ApprovalRecord::create([
            'flow_id' => $this->id,
            'node_id' => $nextNode->id,
            'form_data_id' => $record->form_data_id,
            'approver_id' => $approverId,
            'status' => 'pending',
        ]);
    }

    /**
     * 获取审批流程统计数据
     */
    public function getStatistics()
    {
        $records = $this->records();
        
        return [
            'total' => $records->count(),
            'pending' => $records->where('status', 'pending')->count(),
            'approved' => $records->where('status', 'approved')->count(),
            'rejected' => $records->where('status', 'rejected')->count(),
            'average_time' => $this->calculateAverageApprovalTime(),
        ];
    }

    /**
     * 计算平均审批时间（小时）
     */
    protected function calculateAverageApprovalTime()
    {
        $completedRecords = $this->records()
            ->whereNotNull('approved_at')
            ->with('formData')
            ->get();

        if ($completedRecords->isEmpty()) {
            return 0;
        }

        $totalHours = $completedRecords->sum(function ($record) {
            $start = $record->formData->submitted_at;
            $end = $record->approved_at;
            return $end->diffInHours($start);
        });

        return round($totalHours / $completedRecords->count(), 2);
    }
}