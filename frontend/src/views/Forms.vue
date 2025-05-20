<template>
  <div class="forms-container">
    <el-card>
      <div class="header">
        <h2>表单管理</h2>
        <div>
          <el-button type="primary" @click="showCreateDialog">
            <el-icon><plus /></el-icon> 新建表单
          </el-button>
          <el-button @click="refresh">
            <el-icon><refresh /></el-icon> 刷新
          </el-button>
        </div>
      </div>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="表单标题/描述" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="选择类型" clearable>
            <el-option label="普通表单" value="normal" />
            <el-option label="审批表单" value="approval" />
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

      <!-- 表单表格 -->
      <el-table
        :data="forms"
        border
        stripe
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column prop="title" label="表单标题" width="200" />
        <el-table-column prop="description" label="描述" width="250" show-overflow-tooltip />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'approval' ? 'success' : ''">
              {{ row.type === 'approval' ? '审批表单' : '普通表单' }}
            </el-tag>
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
            <el-button size="small" @click="showFieldsDialog(row)">字段</el-button>
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
    </el-card>

    <!-- 创建/编辑表单对话框 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="formDialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formForm"
        :model="formForm"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="表单标题" prop="title">
          <el-input v-model="formForm.title" />
        </el-form-item>
        <el-form-item label="表单描述" prop="description">
          <el-input v-model="formForm.description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="表单类型" prop="type">
          <el-radio-group v-model="formForm.type">
            <el-radio-button label="normal">普通表单</el-radio-button>
            <el-radio-button label="approval">审批表单</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="表单状态" prop="status">
          <el-switch
            v-model="formForm.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 字段管理对话框 -->
    <el-dialog
      v-model="fieldsDialogVisible"
      title="表单字段管理"
      width="800px"
      :close-on-click-modal="false"
    >
      <div class="field-manager">
        <div class="field-list">
          <el-table
            :data="currentFormFields"
            border
            stripe
            style="width: 100%"
          >
            <el-table-column prop="label" label="字段标签" width="120" />
            <el-table-column prop="name" label="字段名称" width="120" />
            <el-table-column label="字段类型" width="100">
              <template #default="{ row }">
                {{ fieldTypeMap[row.type] || row.type }}
              </template>
            </el-table-column>
            <el-table-column label="必填" width="60">
              <template #default="{ row }">
                <el-tag v-if="row.required" type="success" size="small">是</el-tag>
                <el-tag v-else type="info" size="small">否</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="order" label="排序" width="60" />
            <el-table-column label="操作" width="120">
              <template #default="{ row, $index }">
                <el-button size="small" @click="editField(row, $index)">编辑</el-button>
                <el-button
                  size="small"
                  type="danger"
                  @click="removeField($index)"
                >删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="field-actions">
            <el-button type="primary" @click="showAddFieldDialog">
              <el-icon><plus /></el-icon> 添加字段
            </el-button>
          </div>
        </div>
      </div>
      <template #footer>
        <el-button @click="fieldsDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveFields">保存</el-button>
      </template>
    </el-dialog>

    <!-- 添加/编辑字段对话框 -->
    <el-dialog
      v-model="fieldDialogVisible"
      :title="fieldDialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="fieldForm"
        :model="fieldForm"
        :rules="fieldRules"
        label-width="100px"
      >
        <el-form-item label="字段标签" prop="label">
          <el-input v-model="fieldForm.label" />
        </el-form-item>
        <el-form-item label="字段名称" prop="name">
          <el-input v-model="fieldForm.name" :disabled="isEditField" />
        </el-form-item>
        <el-form-item label="字段类型" prop="type">
          <el-select v-model="fieldForm.type" placeholder="选择字段类型" style="width: 100%">
            <el-option
              v-for="(label, type) in fieldTypeMap"
              :key="type"
              :label="label"
              :value="type"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="是否必填" prop="required">
          <el-switch v-model="fieldForm.required" />
        </el-form-item>
        <el-form-item label="占位文本" prop="placeholder">
          <el-input v-model="fieldForm.placeholder" />
        </el-form-item>
        <el-form-item label="默认值" prop="default_value">
          <el-input v-model="fieldForm.default_value" />
        </el-form-item>
        <el-form-item label="排序" prop="order">
          <el-input-number v-model="fieldForm.order" :min="0" />
        </el-form-item>
        <el-form-item
          v-if="['radio', 'checkbox', 'select'].includes(fieldForm.type)"
          label="选项配置"
          prop="options"
        >
          <div class="field-options">
            <div v-for="(option, index) in fieldForm.options" :key="index" class="option-item">
              <el-input v-model="option.label" placeholder="显示文本" style="width: 200px" />
              <el-input v-model="option.value" placeholder="值" style="width: 200px; margin-left: 10px" />
              <el-button
                type="danger"
                circle
                size="small"
                @click="removeOption(index)"
                style="margin-left: 10px"
              >
                <el-icon><delete /></el-icon>
              </el-button>
            </div>
            <el-button type="primary" @click="addOption" size="small">
              <el-icon><plus /></el-icon> 添加选项
            </el-button>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="fieldDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitFieldForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Plus, Refresh, Delete } from '@element-plus/icons-vue'
import { getForms, createForm, updateForm, deleteForm, getFormFields } from '@/api/form'

// 表单数据
const forms = ref([])
const loading = ref(false)

// 搜索表单
const searchForm = reactive({
  keyword: '',
  type: '',
  status: ''
})

// 分页
const pagination = reactive({
  current: 1,
  size: 20,
  total: 0
})

// 表单对话框
const formDialogVisible = ref(false)
const isEditForm = ref(false)
const formDialogTitle = computed(() => isEditForm.value ? '编辑表单' : '新建表单')
const formForm = ref({
  title: '',
  description: '',
  type: 'normal',
  status: 1
})

// 表单验证规则
const formRules = reactive({
  title: [
    { required: true, message: '请输入表单标题', trigger: 'blur' },
    { min: 2, max: 50, message: '长度在2到50个字符', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择表单类型', trigger: 'change' }
  ]
})

// 字段管理对话框
const fieldsDialogVisible = ref(false)
const currentFormId = ref(null)
const currentFormFields = ref([])

// 字段类型映射
const fieldTypeMap = reactive({
  text: '单行文本',
  textarea: '多行文本',
  radio: '单选',
  checkbox: '多选',
  select: '下拉选择',
  date: '日期',
  file: '文件上传'
})

// 字段对话框
const fieldDialogVisible = ref(false)
const isEditField = ref(false)
const fieldDialogTitle = computed(() => isEditField.value ? '编辑字段' : '添加字段')
const fieldForm = ref({
  name: '',
  label: '',
  type: 'text',
  required: false,
  placeholder: '',
  default_value: '',
  options: [],
  order: 0
})
const fieldEditIndex = ref(null)

// 字段验证规则
const fieldRules = reactive({
  name: [
    { required: true, message: '请输入字段名称', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '只能包含字母、数字和下划线，且不能以数字开头', trigger: 'blur' }
  ],
  label: [
    { required: true, message: '请输入字段标签', trigger: 'blur' }
  ],
  type: [
    { required: true, message: '请选择字段类型', trigger: 'change' }
  ]
})

// 获取表单列表
const fetchForms = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current,
      limit: pagination.size,
      ...searchForm
    }
    const response = await getForms(params)
    forms.value = response.data.data
    pagination.total = response.data.total
  } catch (error) {
    console.error('获取表单列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.current = 1
  fetchForms()
}

// 刷新
const refresh = () => {
  fetchForms()
}

// 分页大小变化
const handleSizeChange = (size) => {
  pagination.size = size
  fetchForms()
}

// 当前页变化
const handleCurrentChange = (current) => {
  pagination.current = current
  fetchForms()
}

// 显示创建对话框
const showCreateDialog = () => {
  isEditForm.value = false
  formForm.value = {
    title: '',
    description: '',
    type: 'normal',
    status: 1
  }
  formDialogVisible.value = true
}

// 显示编辑对话框
const showEditDialog = (row) => {
  isEditForm.value = true
  formForm.value = {
    id: row.id,
    title: row.title,
    description: row.description,
    type: row.type,
    status: row.status
  }
  formDialogVisible.value = true
}

// 提交表单
const submitForm = async () => {
  const form = formForm.value
  try {
    if (isEditForm.value) {
      await updateForm(form.id, form)
    } else {
      await createForm(form)
    }
    formDialogVisible.value = false
    fetchForms()
  } catch (error) {
    console.error('保存表单失败:', error)
  }
}

// 删除表单
const handleDelete = async (id) => {
  try {
    await deleteForm(id)
    fetchForms()
  } catch (error) {
    console.error('删除表单失败:', error)
  }
}

// 显示字段管理对话框
const showFieldsDialog = async (row) => {
  currentFormId.value = row.id
  try {
    const response = await getFormFields(row.id)
    currentFormFields.value = response.data
    fieldsDialogVisible.value = true
  } catch (error) {
    console.error('获取表单字段失败:', error)
  }
}

// 显示添加字段对话框
const showAddFieldDialog = () => {
  isEditField.value = false
  fieldForm.value = {
    name: '',
    label: '',
    type: 'text',
    required: false,
    placeholder: '',
    default_value: '',
    options: [],
    order: currentFormFields.value.length
  }
  fieldDialogVisible.value = true
}

// 编辑字段
const editField = (field, index) => {
  isEditField.value = true
  fieldEditIndex.value = index
  fieldForm.value = {
    ...field,
    options: field.options ? [...field.options] : []
  }
  fieldDialogVisible.value = true
}

// 删除字段
const removeField = (index) => {
  currentFormFields.value.splice(index, 1)
}

// 添加选项
const addOption = () => {
  fieldForm.value.options.push({
    label: '',
    value: ''
  })
}

// 删除选项
const removeOption = (index) => {
  fieldForm.value.options.splice(index, 1)
}

// 提交字段表单
const submitFieldForm = () => {
  const field = { ...fieldForm.value }
  if (field.options && field.options.length === 0) {
    delete field.options
  }
  
  if (isEditField.value) {
    currentFormFields.value[fieldEditIndex.value] = field
  } else {
    currentFormFields.value.push(field)
  }
  
  fieldDialogVisible.value = false
}

// 保存字段
const saveFields = async () => {
  try {
    const formId = currentFormId.value
    const fields = currentFormFields.value
    
    if (isEditForm.value) {
      await updateForm(formId, { fields })
    } else {
      await createForm({ id: formId, fields })
    }
    
    fieldsDialogVisible.value = false
    fetchForms()
  } catch (error) {
    console.error('保存字段失败:', error)
  }
}

// 初始化
onMounted(() => {
  fetchForms()
})
</script>

<style scoped>
.forms-container {
  padding: 20px;
}
