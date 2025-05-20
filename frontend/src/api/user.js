import http from './http'

export const getUsers = (params) => {
  return http.get('/users', { params })
}

export const createUser = (data) => {
  return http.post('/users', data)
}

export const updateUser = (id, data) => {
  return http.put(`/users/${id}`, data)
}

export const deleteUser = (id) => {
  return http.delete(`/users/${id}`)
}

export const resetPassword = (id, data) => {
  return http.post(`/users/${id}/reset-password`, data)
}

export const getRoles = () => {
  return http.get('/roles')
}

export const assignRoles = (userId, roleIds) => {
  return http.post(`/users/${userId}/roles`, { role_ids: roleIds })
}