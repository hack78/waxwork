import http from './http'

export const getForms = (params) => {
  return http.get('/forms', { params })
}

export const getForm = (id) => {
  return http.get(`/forms/${id}`)
}

export const createForm = (data) => {
  return http.post('/forms', data)
}

export const updateForm = (id, data) => {
  return http.put(`/forms/${id}`, data)
}

export const deleteForm = (id) => {
  return http.delete(`/forms/${id}`)
}

export const getFormFields = (formId) => {
  return http.get(`/forms/${formId}/fields`)
}

export const submitFormData = (formId, data) => {
  return http.post(`/forms/${formId}/data`, data)
}

export const getFormSubmissions = (formId, params) => {
  return http.get(`/forms/${formId}/data`, { params })
}