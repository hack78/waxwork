<template>
  <div class="dashboard-container">
    <h2>系统概览</h2>
    
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-icon user-icon">
              <el-icon><user /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.userCount }}</div>
              <div class="stat-label">用户总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-icon form-icon">
              <el-icon><document /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.formCount }}</div>
              <div class="stat-label">表单总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-icon approval-icon">
              <el-icon><checked /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.pendingApprovals }}</div>
              <div class="stat-label">待审批</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-icon submission-icon">
              <el-icon><list /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.todaySubmissions }}</div>
              <div class="stat-label">今日提交</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="charts-row">
      <el-col :span="12">
        <el-card shadow="hover">
          <h3>审批状态分布</h3>
          <div ref="approvalChart" style="height: 300px;"></div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card shadow="hover">
          <h3>最近提交</h3>
          <el-table :data="recentSubmissions" style="width: 100%">
            <el-table-column prop="form.title" label="表单" />
            <el-table-column prop="submitter.name" label="提交人" />
            <el-table-column prop="created_at" label="提交时间" />
            <el-table-column prop="status" label="状态">
              <template #default="{ row }">
                <el-tag :type="getStatusTagType(row.status)">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { User, Document, Checked, List } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { getApprovalStatistics } from '@/api/approval'
import { getFormSubmissions } from '@/api/form'

const stats = ref({
  userCount: 0,
  formCount: 0,
  pendingApprovals: 0,
  todaySubmissions: 0
})

const recentSubmissions = ref([])
const approvalChart = ref(null)

const getStatusTagType = (status) => {
  switch (status) {
    case 'approved': return 'success'
    case 'rejected': return 'danger'
    case 'pending': return 'warning'
    default: return 'info'
  }
}

const loadStatistics = async () => {
  try {
    const res = await getApprovalStatistics()
    stats.value = res.data.stats
    
    // 初始化审批状态图表
    initApprovalChart(res.data.approvalStats)
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

const loadRecentSubmissions = async () => {
  try {
    const res = await getFormSubmissions({ limit: 5 })
    recentSubmissions.value = res.data
  } catch (error) {
    console.error('加载最近提交失败:', error)
  }
}

const initApprovalChart = (data) => {
  const chart = echarts.init(approvalChart.value)
  const option = {
    tooltip: {
      trigger: 'item'
    },
    legend: {
      top: '5%',
      left: 'center'
    },
    series: [
      {
        name: '审批状态',
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: '18',
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: [
          { value: data.approved, name: '已通过' },
          { value: data.pending, name: '待审批' },
          { value: data.rejected, name: '已拒绝' }
        ]
      }
    ]
  }
  chart.setOption(option)
}

onMounted(() => {
  loadStatistics()
  loadRecentSubmissions()
})
</script>

<style scoped>
.dashboard-container {
  padding: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
}

.stat-icon i {
  font-size: 24px;
  color: #fff;
}

.user-icon {
  background-color: #409EFF;
}

.form-icon {
  background-color: #67C23A;
}

.approval-icon {
  background-color: #E6A23C;
}

.submission-icon {
  background-color: #F56C6C;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 14px;
  color: #909399;
}

.charts-row {
  margin-top: 20px;
}
</style>