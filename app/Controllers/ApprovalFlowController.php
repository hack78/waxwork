<?php

namespace App\Controllers;

use App\Models\ApprovalFlow;
use App\Models\ApprovalRecord;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\WeworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApprovalFlowController extends Controller
{
    /**
     * 获取审批流程列表
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'status' => 'nullable|boolean',
            'form_id' => 'nullable|exists:forms,id',
        ]);

        $query = ApprovalFlow::query()
            ->when($request->status !== null, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->form_id, function ($q) use ($request) {
                $q->where('form_id', $request->form_id);
            })
            ->with(['form', 'nodes', 'creator']);

        $flows = $query->paginate($request->limit ?? 20);

        return response()->json([
            'total' => $flows->total(),
            'per_page' => $flows->perPage(),
            'current_page' => $flows->currentPage(),
            'last_page' => $flows->lastPage(),
            'data' => $flows->items(),
        ]);
    }

    /**
     * 创建审批流程
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'form_id' => 'required|exists:forms,id',
            'status' => 'required|boolean',
            'nodes' => 'required|array',
            'nodes.*.name' => 'required|string|max:50',
            'nodes.*.type' => 'required|string|in:approval,notify',
            'nodes.*.approver_type' => 'required|string|in:user,role,department',
            'nodes.*.approver_id' => 'required|integer',
            'nodes.*.conditions' => 'nullable|array',
            'nodes.*.order' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request) {
            $flow = ApprovalFlow::create([
                'name' => $request->name,
                'description' => $request->description,
                'form_id' => $request->form_id,
                'status' => $request->status,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($request->nodes as $nodeData) {
                $flow->nodes()->create([
                    'name' => $nodeData['name'],
                    'type' => $nodeData['type'],
                    'approver_type' => $nodeData['approver_type'],
                    'approver_id' => $nodeData['approver_id'],
                    'conditions' => $nodeData['conditions'] ?? null,
                    'order' => $nodeData['order'] ?? 0,
                ]);
            }

            return response()->json($flow->load(['nodes', 'form']), 201);
        });
    }

    /**
     * 获取审批流程详情
     */
    public function show(ApprovalFlow $flow)
    {
        return response()->json($flow->load(['nodes', 'form', 'creator']));
    }

    /**
     * 更新审批流程
     */
    public function update(Request $request, ApprovalFlow $flow)
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'sometimes|boolean',
            'nodes' => 'sometimes|array',
            'nodes.*.id' => 'nullable|exists:approval_nodes,id',
            'nodes.*.name' => 'required|string|max:50',
            'nodes.*.type' => 'required|string|in:approval,notify',
            'nodes.*.approver_type' => 'required|string|in:user,role,department',
            'nodes.*.approver_id' => 'required|integer',
            'nodes.*.conditions' => 'nullable|array',
            'nodes.*.order' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request, $flow) {
            $flow->update([
                'name' => $request->name ?? $flow->name,
                'description' => $request->description ?? $flow->description,
                'status' => $request->status ?? $flow->status,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->has('nodes')) {
                $existingNodeIds = $flow->nodes->pluck('id')->toArray();
                $updatedNodeIds = [];

                foreach ($request->nodes as $nodeData) {
                    if (isset($nodeData['id'])) {
                        // 更新现有节点
                        $node = $flow->nodes()->findOrFail($nodeData['id']);
                        $node->update([
                            'name' => $nodeData['name'],
                            'type' => $nodeData['type'],
                            'approver_type' => $nodeData['approver_type'],
                            'approver_id' => $nodeData['approver_id'],
                            'conditions' => $nodeData['conditions'] ?? null,
                            'order' => $nodeData['order'] ?? 0,
                        ]);
                        $updatedNodeIds[] = $nodeData['id'];
                    } else {
                        // 创建新节点
                        $node = $flow->nodes()->create([
                            'name' => $nodeData['name'],
                            'type' => $nodeData['type'],
                            'approver_type' => $nodeData['approver_type'],
                            'approver_id' => $nodeData['approver_id'],
                            'conditions' => $nodeData['conditions'] ?? null,
                            'order' => $nodeData['order'] ?? 0,
                        ]);
                        $updatedNodeIds[] = $node->id;
                    }
                }

                // 删除不再存在的节点
                $nodesToDelete = array_diff($existingNodeIds, $updatedNodeIds);
                if (!empty($nodesToDelete)) {
                    $flow->nodes()->whereIn('id', $nodesToDelete)->delete();
                }
            }

            return response()->json($flow->load('nodes'));
        });
    }

    /**
     * 删除审批流程
     */
    public function destroy(ApprovalFlow $flow)
    {
        DB::transaction(function () use ($flow) {
            $flow->nodes()->delete();
            $flow->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * 提交审批申请
     */
    public function submit(Request $request, ApprovalFlow $flow)
    {
        $request->validate([
            'form_data' => 'required|array',
            'form_data.*.field_id' => 'required|exists:form_fields,id',
            'form_data.*.value' => 'required',
        ]);

        return DB::transaction(function () use ($request, $flow) {
            // 创建表单提交记录
            $submission = FormSubmission::create([
                'form_id' => $flow->form_id,
                'data' => $request->form_data,
                'status' => 'submitted',
                'submitted_by' => $request->user()->id,
            ]);

            // 创建审批记录
            $firstNode = $flow->nodes()->orderBy('order')->first();
            if ($firstNode) {
                $approverId = $this->getApproverId($firstNode, $request->user());
                
                $record = ApprovalRecord::create([
                    'flow_id' => $flow->id,
                    'node_id' => $firstNode->id,
                    'form_submission_id' => $submission->id,
                    'approver_id' => $approverId,
                    'status' => 'pending',
                ]);

                // 发送企业微信通知
                $service = app(WeworkService::class);
                $service->sendApprovalNotification($approverId, $flow, $submission);
            }

            return response()->json([
                'submission' => $submission,
                'record' => $record ?? null,
            ], 201);
        });
    }

    /**
     * 获取审批人ID
     */
    protected function getApproverId($node, $user)
    {
        switch ($node->approver_type) {
            case 'user':
                return $node->approver_id;
            case 'role':
                // 返回该角色的第一个用户ID
                return DB::table('users')
                    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                    ->where('user_roles.role_id', $node->approver_id)
                    ->value('users.id');
            case 'department':
                // 返回该部门的负责人ID
                return DB::table('users')
                    ->where('department', $node->approver_id)
                    ->where('is_leader', true)
                    ->value('id');
            default:
                return $user->id;
        }
    }
}