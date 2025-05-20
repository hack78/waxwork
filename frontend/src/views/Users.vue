<template>
  <div class="users-container">
    <el-card>
      <div class="header">
        <h2>用户管理</h2>
        <div>
          <el-button type="primary" @click="showCreateDialog">
            <el-icon><plus /></el-icon> 新增用户
          </el-button>
          <el-button @click="refresh">
            <el-icon><refresh /></el-icon> 刷新
          </el-button>
        </div>
      </div>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户名/姓名" clearable />
        </el-form-item>
        <el-form-item label="部门">
          <el-select v-model="searchForm.department" placeholder="选择部门" clearable>
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
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

      <!-- 用户表格 -->
      <el-table
        :data="users"
        border
        stripe
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="email" label="邮箱" width="180" />
        <el-table-column prop="phone" label="手机号" width="120" />
        <el-table-column prop="department" label="部门" width="120" />
        <el-table-column prop="position" label="职位" width="120" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status ? 'success' : 'danger'">
              {{ row.status ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="角色" min-width="180">
          <template #default="{ row }">
            <el-tag
              v-for="role in row.roles"
              :key="role.id"
              type="info"
              class="role-tag"
            >
              {{ role.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="showEditDialog(row)">编辑</el-button>
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

    <!-- 创建/编辑用户对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="userForm"
        :model="userForm"
        :rules="userRules"
        label-width="100px"
      >
        <el-form-item label="用户名" prop="username">
          <el-input v-model="userForm.username" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="密码" prop="password" v-if="!isEdit">
          <el-input v-model="userForm.password" type="password" show-password />
        </el-form-item>
        <el-form-item label="姓名" prop="name">
          <el-input v-model="userForm.name" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="userForm.email" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="userForm.phone" />
        </el-form-item>
        <el-form-item label="部门" prop="department">
          <el-select
            v-model="userForm.department"
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
        <el-form-item label="职位" prop="position">
          <el-input v-model="userForm.position" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="userForm.status"
            :active-value="1"
            :inactive-value="0"
          />
        </el-form-item>
        <el-form-item label="角色" prop="roleIds">
          <el-select
            v-model="userForm.roleIds"
            multiple
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
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Plus, Refresh } from '@element-plus/icons-vue'
import { getUsers, createUser, updateUser, deleteUser, getRoles } from '@/api/user'

// 用户数据
const users = ref([])
const loading = ref(false)
const departments = ref([
  { id: 1, name: '技术部' },
  { id: 2, name: '市场部' },
  { id: 3, name: '人力资源部' }
])

// 搜索表单
const searchForm = reactive({
  keyword: '',
  department: '',
  status: ''
})

// 分页
const pagination = reactive({
  current: 1,
  size: 20,
  total: 0
})

// 对话框
const dialogVisible = ref(false)
const isEdit = ref(false)
const dialogTitle = computed(() => isEdit.value ? '编辑用户' : '新增用户')
const userForm = ref({
  username: '',
  password: '',
  name: '',
  email: '',
  phone: '',
  department: '',
  position: '',
  status: 1,
  roleIds: []
})

// 表单验证规则
const userRules = reactive({
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '长度在3到20个字符', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 20, message: '长度在6到20个字符', trigger: 'blur' }
  ],
  name: [
    { required: true, message: '请输入姓名', trigger: 'blur' }
  ],
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱地址', trigger: ['blur', 'change'] }
  ],
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
  ],
  roleIds: [
    { required: true, message: '请选择角色', trigger: 'change' }
  ]
})

// 角色数据
const roles = ref([])

// 获取角色列表
const fetchRoles = async () => {
  try {
    const response = await getRoles()
    roles.value = response.data
  } catch (error) {
    console.error('获取角色列表失败:', error)
  }
}

// 获取用户列表
const fetchUsers = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current,
      limit: pagination.size,
      ...searchForm
    }
    const response = await getUsers(params)
    users.value = response.data.data
    pagination.total = response.data.total
  } catch (error) {
    console.error('获取用户列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.current = 1
  fetchUsers()
}

// 刷新
const refresh = () => {
  fetchUsers()
}

// 分页大小变化
const handleSizeChange = (size) => {
  pagination.size = size
  fetchUsers()
}

// 当前页变化
const handleCurrentChange = (current) => {
  pagination.current = current
  fetchUsers()
}

// 显示创建对话框
const showCreateDialog = () => {
  isEdit.value = false
  userForm.value = {
    username: '',
    password: '',
    name: '',
    email: '',
    phone: '',
    department: '',
    position: '',
    status: 1,
    roleIds: []
  }
  dialogVisible.value = true
}

// 显示编辑对话框
const showEditDialog = (row) => {
  isEdit.value = true
  userForm.value = {
    ...row,
    roleIds: row.roles.map(role => role.id)
  }
  dialogVisible.value = true
}

// 提交表单
const submitForm = async () => {
  const form = userForm.value
  try {
    if (isEdit.value) {
      await updateUser(form.id, form)
    } else {
      await createUser(form)
    }
    dialogVisible.value = false
    fetchUsers()
  } catch (error) {
    console.error('保存用户失败:', error)
  }
}

// 删除用户
const handleDelete = async (id) => {
  try {
    await deleteUser(id)
    fetchUsers()
  } catch (error) {
    console.error('删除用户失败:', error)
  }
}

// 初始化
onMounted(() => {
  fetchUsers()
  fetchRoles()
})
</script>

<style scoped>
.users-container {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.search-form {
  margin-bottom: 20px;
}

.role-tag {
  margin-right: 5px;
  margin-bottom: 5px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>