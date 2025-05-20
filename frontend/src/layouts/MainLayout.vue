<template>
  <div class="main-layout">
    <el-container>
      <!-- 顶部导航栏 -->
      <el-header>
        <div class="header-content">
          <div class="logo">
            <img src="@/assets/logo.png" alt="Logo">
            <span>企业微信综合管理系统</span>
          </div>
          <div class="user-info">
            <el-dropdown>
              <span class="el-dropdown-link">
                <el-avatar :size="32" :src="user.avatar" />
                <span>{{ user.name }}</span>
                <el-icon><arrow-down /></el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="handleProfile">个人中心</el-dropdown-item>
                  <el-dropdown-item @click="handleLogout">退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </div>
      </el-header>

      <el-container>
        <!-- 侧边菜单 -->
        <el-aside width="200px">
          <el-menu
            :default-active="activeMenu"
            router
            background-color="#545c64"
            text-color="#fff"
            active-text-color="#ffd04b"
          >
            <el-menu-item index="/">
              <el-icon><house /></el-icon>
              <span>控制台</span>
            </el-menu-item>
            <el-menu-item index="/forms">
              <el-icon><document /></el-icon>
              <span>表单管理</span>
            </el-menu-item>
            <el-menu-item index="/approvals">
              <el-icon><checked /></el-icon>
              <span>审批管理</span>
            </el-menu-item>
            <el-menu-item index="/users">
              <el-icon><user /></el-icon>
              <span>用户管理</span>
            </el-menu-item>
          </el-menu>
        </el-aside>

        <!-- 主内容区 -->
        <el-main>
          <router-view />
        </el-main>
      </el-container>
    </el-container>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  House,
  Document,
  Checked,
  User,
  ArrowDown
} from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()

const user = ref({
  name: '管理员',
  avatar: ''
})

const activeMenu = computed(() => {
  return route.path
})

const handleProfile = () => {
  router.push('/profile')
}

const handleLogout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<style scoped>
.main-layout {
  height: 100vh;
}

.el-header {
  background-color: #409EFF;
  color: #fff;
  line-height: 60px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  font-size: 18px;
  font-weight: bold;
}

.logo img {
  height: 40px;
  margin-right: 10px;
}

.user-info {
  display: flex;
  align-items: center;
}

.el-dropdown-link {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: #fff;
}

.el-dropdown-link span {
  margin: 0 5px;
}

.el-aside {
  background-color: #545c64;
  color: #fff;
}

.el-menu {
  border-right: none;
}
</style>