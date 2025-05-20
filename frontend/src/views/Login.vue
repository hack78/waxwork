<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <img src="@/assets/logo.png" alt="Logo">
        <h2>企业微信综合管理系统</h2>
      </div>

      <el-tabs v-model="activeTab" stretch>
        <el-tab-pane label="账号登录" name="account">
          <el-form
            ref="loginForm"
            :model="loginForm"
            :rules="loginRules"
            @keyup.enter="handleLogin"
          >
            <el-form-item prop="username">
              <el-input
                v-model="loginForm.username"
                placeholder="请输入用户名"
                prefix-icon="user"
              />
            </el-form-item>
            <el-form-item prop="password">
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="请输入密码"
                prefix-icon="lock"
                show-password
              />
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                :loading="loading"
                @click="handleLogin"
                style="width: 100%"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="企业微信登录" name="wework">
          <div class="wework-login">
            <img 
              src="@/assets/wework-qrcode.png" 
              alt="企业微信扫码登录"
              class="qrcode"
              @click="handleWeworkLogin"
            >
            <p>使用企业微信扫码登录</p>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { login } from '@/api/auth'

const router = useRouter()

const activeTab = ref('account')
const loading = ref(false)

const loginForm = ref({
  username: '',
  password: ''
})

const loginRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}

const handleLogin = () => {
  loading.value = true
  login(loginForm.value)
    .then(response => {
      localStorage.setItem('token', response.data.token)
      router.push('/')
      ElMessage.success('登录成功')
    })
    .catch(() => {
      loading.value = false
    })
}

const handleWeworkLogin = () => {
  // 调用企业微信扫码登录API
  window.location.href = '/api/auth/wework/qrconnect'
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f0f2f5;
  background-image: url('@/assets/login-bg.jpg');
  background-size: cover;
}

.login-box {
  width: 400px;
  padding: 40px;
  background-color: #fff;
  border-radius: 5px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-header img {
  height: 60px;
  margin-bottom: 10px;
}

.login-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}

.wework-login {
  text-align: center;
  padding: 20px 0;
}

.qrcode {
  width: 200px;
  height: 200px;
  margin-bottom: 20px;
  cursor: pointer;
}

.qrcode:hover {
  opacity: 0.8;
}
</style>