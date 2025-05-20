<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalNode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'flow_id',
        'name',
        'type',
        'approver_type',
        'approver_id',
        'conditions',
        'order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'array',
        'status' => 'boolean',
    ];

    /**
     * 获取节点所属的审批流程
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * 获取节点的审批记录
     */
    public function records()
    {
        return $this->hasMany(ApprovalRecord::class, 'node_id');
    }

    /**
     * 获取审批人ID
     */
    public function getApproverId($submitter)
    {
        switch ($this->approver_type) {
            case 'user':
                return $this->approver_id;
            case 'role':
                // 返回该角色的第一个用户ID
                return DB::table('users')
                    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                    ->where('user_roles.role_id', $this->approver_id)
                    ->value('users.id');
            case 'department':
                // 返回该部门的负责人ID
                return DB::table('users')
                    ->where('department', $this->approver_id)
                    ->where('is_leader', true)
                    ->value('id');
            default:
                return $submitter->id;
        }
    }

    /**
     * 检查节点是否是审批类型
     */
    public function isApproval()
    {
        return $this->type === 'approval';
    }

    /**
     * 检查节点是否是通知类型
     */
    public function isNotify()
    {
        return $this->type === 'notify';
    }

    /**
     * 检查节点是否满足条件
     */
    public function checkConditions($formData)
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $fieldValue = $formData->getFieldValue($condition['field']);
            $operator = $condition['operator'];
            $value = $condition['value'];

            switch ($operator) {
                case '>':
                    if (!($fieldValue > $value)) return false;
                    break;
                case '<':
                    if (!($fieldValue < $value)) return false;
                    break;
                case '>=':
                    if (!($fieldValue >= $value)) return false;
                    break;
                case '<=':
                    if (!($fieldValue <= $value)) return false;
                    break;
                case '==':
                    if (!($fieldValue == $value)) return false;
                    break;
                case '!=':
                    if (!($fieldValue != $value)) return false;
                    break;
                case 'in':
                    if (!in_array($fieldValue, explode(',', $value))) return false;
                    break;
                case 'not_in':
                    if (in_array($fieldValue, explode(',', $value))) return false;
                    break;
                case 'contains':
                    if (strpos($fieldValue, $value) === false) return false;
                    break;
                case 'not_contains':
                    if (strpos($fieldValue, $value) !== false) return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * 获取节点的审批人
     */
    public function getApprover()
    {
        switch ($this->approver_type) {
            case 'user':
                return User::find($this->approver_id);
            case 'role':
                return Role::find($this->approver_id);
            case 'department':
                return DB::table('departments')
                    ->where('id', $this->approver_id)
                    ->first();
            default:
                return null;
        }
    }

    /**
     * 获取节点的审批人名称
     */
    public function getApproverName()
    {
        $approver = $this->getApprover();
        if (!$approver) {
            return '未知';
        }

        switch ($this->approver_type) {
            case 'user':
                return $approver->name;
            case 'role':
                return $approver->name;
            case 'department':
                return $approver->name;
            default:
                return '未知';
        }
    }

    /**
     * 获取节点的审批统计
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