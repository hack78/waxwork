import http from './http'

export const getApprovalFlows = (params) => {
  return http.get('/approval-flows', { params })
}

export const getApprovalFlow = (id) => {
  return http.get(`/approval-flows/${id}`)
}

export const createApprovalFlow = (data) => {
  return http.post('/approval-flows', data)
}

export const updateApprovalFlow = (id, data) => {
  return http.put(`/approval-flows/${id}`, data)
}

export const deleteApprovalFlow = (id) => {
  return http.delete(`/approval-flows/${id}`)
}

export const getApprovalRecords = (params) => {
  return http.get('/approval-records', { params })
}

export const submitApproval = (flowId, data) => {
  return http.post(`/approval-flows/${flowId}/submit`, data)
}

export const approve = (recordId, data) => {
  return http.post(`/approval-records/${recordId}/approve`, data)
}

export const reject = (recordId, data) => {
  return http.post(`/approval-records/${recordId}/reject`, data)
}

export const getApprovalStatistics = () => {
  return http.get('/approval-records/statistics')
}