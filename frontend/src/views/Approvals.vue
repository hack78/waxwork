<template>
  <div class="approvals-container">
    <el-card>
      <div class="header">
        <h2>审批管理</h2>
        <div>
          <el-button type="primary" @click="showCreateDialog">
            <el-icon><plus /></el-icon> 新建流程
          </el-button>
          <el-button @click="refresh">
            <el-icon><refresh /></el-icon> 刷新
          </el-button>
        </div>
      </div>

      <!-- 选项卡 -->
      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <el-tab-pane label="审批流程" name="flows">
          <!-- 搜索表单 -->
          <el-form :inline="true" :model="searchForm" class="search-form">
            <el-form-item label="关键词">
              <el-input v-model="searchForm.keyword" placeholder="流程名称/描述" clearable />
            </el-form-item>
            <el-form-item label="关联表单">
              <el-select
                v-model="searchForm.form_id"
                placeholder="选择关联表单"
                clearable
                filterable
                style="width: 200px"
              >
                <el-option
                  v-for="form in forms"
                  :key="form.id"
                  :label="form.title"
                  :value="form.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="状态">
              <el-select v-model="searchForm.status" placeholder="选择状态" clearable>
                <el-option label="启用" :value="1" />
                <el-option label="禁用" :value="0" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSearch">搜索</el-button>
            </el-form-item>
          </el-form>

          <!-- 审批流程表格 -->
          <el-table
            :data="approvalFlows"
            border
            stripe
            v-loading="loading"
            style="width: 100%"
          >
            <el-table-column prop="name" label="流程名称" width="200" />
            <el-table-column prop="description" label="描述" width="250" show-overflow-tooltip />
            <el-table-column label="关联表单" width="200">
              <template #default="{ row }">
                {{ getFormTitle(row.form_id) }}
              </template>
            </el-table-column>
            <el-table-column label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="row.status ? 'success' : 'danger'">
                  {{ row.status ? '启用' : '禁用' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_by" label="创建人" width="120" />
            <el-table-column prop="created_at" label="创建时间" width="160" />
            <el-table-column label="操作" width="220" fixed="right">
              <template #default="{ row }">
                <el-button size="small" @click="showEditDialog(row)">编辑</el-button>
                <el-button size="small" @click="showNodesDialog(row)">节点</el-button>
                <el-button
                  size="small"
                  type="danger"
                  @click="handleDelete(row.id)"
                >删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <div class="pagination">
            <el-pagination
              v-model:current-page="pagination.current"
              v-model:page-size="pagination.size"
              :total="pagination.total"
              :page-sizes="[10, 20, 50, 100]"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </el-tab-pane>

        <el-tab-pane label="我的审批" name="my-approvals">
          <!-- 我的审批表格 -->
          <el-table
            :data="myApprovalRecords"
            border
            stripe
            v-loading="recordsLoading"
            style="width: 100%"
          >
            <el-table-column prop="flow.name" label="流程名称" width="200" />
            <el-table-column label="表单标题" width="200">
              <template #default="{ row }">
                {{ row.form_data.form.title }}
              </template>
            </el-table-column>
            <el-table-column label="提交人" width="120">
              <template #default="{ row }">
                {{ row.form_data.submitter.name }}
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="提交时间" width="160" />
            <el-table-column label="当前节点" width="150">
              <template #default="{ row }">
                {{ row.node.name }}
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="getStatusTagType(row.status)">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <el-button
                  v-if="row.status === 'pending'"
                  size="small"
                  type="success"
                  @click="handleApprove(row)"
                >通过</el-button>
                <el-button
                  v-if="row.status === 'pending'"
                  size="small"
                  type="danger"
                  @click="handleReject(row)"
                >拒绝</el-button>
                <el-button
                  size="small"
                  @click="showDetailDialog(row)"
                >详情</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <div class="pagination">
            <el-pagination
              v-model:current-page="recordsPagination.current"
              v-model:page-size="recordsPagination.size"
              :total="recordsPagination.total"
              :page-sizes="[10, 20, 50, 100]"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleRecordsSizeChange"
              @current-change="handleRecordsCurrentChange"
            />
          </div>
        </el-tab-pane>

        <el-tab-pane label="我的申请" name="my-submissions">
          <!-- 我的申请表格 -->
          <el-table
            :data="mySubmissions"
            border
            stripe
            v-loading="submissionsLoading"
            style="width: 100%"
          >
            <el-table-column prop="flow.name" label="流程名称" width="200" />
            <el-table-column label="表单标题" width="200">
              <template #default="{ row }">
                {{ row.form.title }}
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="提交时间" width="160" />
            <el-table-column label="当前状态" width="150">
              <template #default="{ row }">
                {{ row.current_record?.node?.name || '-' }}
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="getStatusTagType(row.status)">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="120" fixed="right">
              <template #default="{ row }">
                <el-button
                  size="small"
                  @click="showSubmissionDetail(row)"
                >详情</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <div class="pagination">
            <el-pagination
              v-model:current-page="submissionsPagination.current"
              v-model:page-size="submissionsPagination.size"
              :total="submissionsPagination.total"
              :page-sizes="[10, 20, 50, 100]"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSubmissionsSizeChange"
              @current-change="handleSubmissionsCurrentChange"
            />
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 创建/编辑审批流程对话框 -->
    <el-dialog
      v-model="flowDialogVisible"
      :title="flowDialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="flowForm"
        :model="flowForm"
        :rules="flowRules"
        label-width="100px"
      >
        <el-form-item label="流程名称" prop="name">
          <el-input v-model="flowForm.name" />
        </el-form-item>
        <el-form-item label="流程描述" prop="description">
          <el-input v-model="flowForm.description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="关联表单" prop="form_id">
          <el-select
            v-model="flowForm.form_id"
            placeholder="选择关联表单"
            style="width: 100%"
            filterable
          >
            <el-option
              v-for="form in forms"
              :key="form.id"
              :label="form.title"
              :value="form.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="流程状态" prop="status">
          <el-switch
            v-model="flowForm.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="flowDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitFlowForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 审批节点管理对话框 -->
    <el-dialog
      v-model="nodesDialogVisible"
      :title="nodesDialogTitle"
      width="800px"
      :close-on-click-modal="false"
    >
      <div class="nodes-manager">
        <div class="nodes-list">
          <el-table
            :data="currentFlowNodes"
            border
            stripe
            style="width: 100%"
          >
            <el-table-column prop="name" label="节点名称" width="150" />
            <el-table-column label="节点类型" width="100">
              <template #default="{ row }">
                <el-tag :type="row.type === 'approval' ? 'success' : 'info'">
                  {{ row.type === 'approval' ? '审批' : '通知' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="审批人类型" width="120">
              <template #default="{ row }">
                {{ getApproverTypeLabel(row.approver_type) }}
              </template>
            </el-table-column>
            <el-table-column label="审批人" width="150">
              <template #default="{ row }">
                {{ getApproverName(row) }}
              </template>
            </el-table-column>
            <el-table-column prop="order" label="排序" width="60" />
            <el-table-column label="操作" width="120">
              <template #default="{ row, $index }">
                <el-button size="small" @click="editNode(row, $index)">编辑</el-button>
                <el-button
                  size="small"
                  type="danger"
                  @click="removeNode($index)"
                >删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="nodes-actions">
            <el-button type="primary" @click="showAddNodeDialog">
              <el-icon><plus /></el-icon> 添加节点
            </el-button>
          </div>
        </div>
      </div>
      <template #footer>
        <el-button @click="nodesDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveNodes">保存</el-button>
      </template>
    </el-dialog>

    <!-- 添加/编辑节点对话框 -->
    <el-dialog
      v-model="nodeDialogVisible"
      :title="nodeDialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="nodeForm"
        :model="nodeForm"
        :rules="nodeRules"
        label-width="100px"
      >
        <el-form-item label="节点名称" prop="name">
          <el-input v-model="nodeForm.name" />
        </el-form-item>
        <el-form-item label="节点类型" prop="type">
          <el-radio-group v-model="nodeForm.type">
            <el-radio-button label="approval">审批</el-radio-button>
            <el-radio-button label="notify">通知</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="审批人类型" prop="approver_type">
          <el-select
            v-model="nodeForm.approver_type"
            placeholder="选择审批人类型"
            style="width: 100%"
          >
            <el-option label="指定用户" value="user" />
            <el-option label="指定角色" value="role" />
            <el-option label="部门负责人" value="department" />
          </el-select>
        </el-form-item>
        <el-form-item label="审批人" prop="approver_id" v-if="nodeForm.approver_type">
          <el-select
            v-if="nodeForm.approver_type === 'user'"
            v-model="nodeForm.approver_id"
            placeholder="选择用户"
            style="width: 100%"
            filterable
          >
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            />
          </el-select>
          <el-select
            v-else-if="nodeForm.approver_type === 'role'"
            v-model="nodeForm.approver_id"
            placeholder="选择角色"
            style="width: 100%"
          >
            <el-option
              v-for="role in roles"
              :key="role.id"
              :label="role.name"
              :value="role.id"
            />
          </el-select>
          <el-select
            v-else-if="nodeForm.approver_type === 'department'"
            v-model="nodeForm.approver_id"
            placeholder="选择部门"
            style="width: 100%"
          >
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="排序" prop="order">
          <el-input-number v-model="nodeForm.order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="nodeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitNodeForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 审批详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="审批详情"
      width="800px"
      :close-on-click-modal="false"
    >
      <div v-if="currentRecord" class="approval-detail">
        <el-descriptions title="审批信息" border>
          <el-descriptions-item label="流程名称">{{ currentRecord.flow.name }}</el-descriptions-item>
          <el-descriptions-item label="当前节点">{{ currentRecord.node.name }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusTagType(currentRecord.status)">
              {{ currentRecord.status }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="提交人">{{ currentRecord.form_data.submitter.name }}</el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ currentRecord.form_data.created_at }}</el-descriptions-item>
          <el-descriptions-item label="审批意见">{{ currentRecord.comment || '无' }}</el-descriptions-item>
        </el-descriptions>

        <el-divider />

        <h3>表单数据</h3>
        <el-table
          :data="getFormattedData(currentRecord.form_data)"
         