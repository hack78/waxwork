import http from './http'

export const getWeworkUserInfo = (code) => {
  return http.get('/wework/user-info', { params: { code } })
}

export const syncWeworkContacts = () => {
  return http.post('/wework/sync-contacts')
}

export const sendWeworkMessage = (data) => {
  return http.post('/wework/send-message', data)
}

export const getWeworkDepartments = () => {
  return http.get('/wework/departments')
}

export const getWeworkDepartmentUsers = (departmentId) => {
  return http.get(`/wework/departments/${departmentId}/users`)
}

export const getWeworkQrConnectUrl = () => {
  return http.get('/wework/qrconnect-url')
}