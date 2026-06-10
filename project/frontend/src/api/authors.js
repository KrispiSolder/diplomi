import api from './axios'

export const authorApi = {
  // Получить список авторов
  getAuthors(params = {}) {
    return api.get('/brands', { params })
  },
  
  // Получить одного автора
  getAuthor(id) {
    return api.get(`/brands/${id}`)
  },
  
  // Получить книги автора
  getAuthorBooks(id) {
    return api.get(`/brands/${id}/products`)
  },
  
  // Создать автора
  createAuthor(data) {
    return api.post('/brands', data, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).catch(error => {
        console.log('Ошибка создания бренда:', error.response.data)
        return Promise.reject(error)
    })
},
  
  // Обновить автора
  updateAuthor(id, data) {
    return api.post(`/brands/${id}?_method=PUT`, data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  
  // Удалить автора
  deleteAuthor(id) {
    return api.delete(`/brands/${id}`)
  }
}