import http from './http'

export const login = (data) => {
  return http.post('/auth/login', data)
}

export const weworkLogin = (code) => {
  return http.get('/auth/wework/login', { params: { code } })
}

export const getUserInfo = () => {
  return http.get('/auth/user')
}

export const logout = () => {
  return http.post('/auth/logout')
}

export const getWeworkQrConnectUrl = () => {
  return http.get('/auth/wework/qrconnect')
}