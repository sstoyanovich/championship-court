import axios from 'axios'

const apiClient = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add auth token to requests if it exists
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default {
  // Auth endpoints
  login(email, password) {
    return apiClient.post('/auth/login', { email, password })
  },
  register(name, email, password, password_confirmation) {
    return apiClient.post('/auth/register', { name, email, password, password_confirmation })
  },
  logout() {
    return apiClient.post('/auth/logout')
  },
  getMe() {
    return apiClient.get('/auth/me')
  },


  // Pack endpoints
  getPacks() {
    return apiClient.get('/packs')
  },
  openPack(packId, userPackId = null) {
    const params = userPackId ? { user_pack_id: userPackId } : {}
    return apiClient.post(`/packs/${packId}/open`, params)
  },
  selectCards(packId, selectedPlayerIds, userPackId = null) {
    const params = {
      selected_player_ids: selectedPlayerIds
    }
    if (userPackId) {
      params.user_pack_id = userPackId
    }
    return apiClient.post(`/packs/${packId}/select`, params)
  },
  
  // Collection endpoints
  getCollection(filters = {}) {
    return apiClient.get('/collection', { params: filters })
  },
  getCollectionStats() {
    return apiClient.get('/collection/stats')
  },
  getCollections() {
    return apiClient.get('/collections')
  },
  getCollectionCards(collectionName) {
    return apiClient.get(`/collections/${encodeURIComponent(collectionName)}/cards`)
  },
  getCollectionTeams(collectionName) {
    return apiClient.get(`/collections/${encodeURIComponent(collectionName)}/teams`)
  },
  getTeamCards(collectionName, team) {
    return apiClient.get(`/collections/${encodeURIComponent(collectionName)}/teams/${encodeURIComponent(team)}`)
  },
  lockCard(cardId) {
    return apiClient.post(`/cards/${cardId}/lock`)
  },
  unlockCard(cardId) {
    return apiClient.post(`/cards/${cardId}/unlock`)
  },

  // Lineup endpoints
  getLineups() {
    return apiClient.get('/lineups')
  },
  getLineup(id) {
    return apiClient.get(`/lineups/${id}`)
  },
  createLineup(name) {
    return apiClient.post('/lineups', { name })
  },
  updateLineup(id, name) {
    return apiClient.put(`/lineups/${id}`, { name })
  },
  deleteLineup(id) {
    return apiClient.delete(`/lineups/${id}`)
  },
  updateLineupSlot(lineupId, position, userCardId) {
    return apiClient.post(`/lineups/${lineupId}/slots`, {
      position,
      user_card_id: userCardId
    })
  },

  // Stats endpoints
  uploadGameScreenshot(file, lineupId) {
    const formData = new FormData()
    formData.append('screenshot', file)
    if (lineupId) {
      formData.append('lineup_id', lineupId)
    }
    
    return apiClient.post('/stats/upload-screenshot', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  confirmStats(gameSessionId, playerStats) {
    return apiClient.post('/stats/confirm', {
      game_session_id: gameSessionId,
      player_stats: playerStats
    })
  },
  getCardStats(userCardId) {
    return apiClient.get(`/stats/card/${userCardId}`)
  },

  // Program endpoints
  getPrograms(category = null) {
    const params = category ? { category } : {}
    return apiClient.get('/programs', { params })
  },
  getProgram(id) {
    return apiClient.get(`/programs/${id}`)
  },
  getUserProgress() {
    return apiClient.get('/programs/progress/user')
  },
  claimReward(rewardId) {
    return apiClient.post(`/programs/rewards/${rewardId}/claim`)
  },

  // Market endpoints
  sellCard(userCardId) {
    return apiClient.post('/cards/sell', { user_card_id: userCardId })
  },
  buyCard(playerId) {
    return apiClient.post('/cards/buy', { player_id: playerId })
  },
  getCardPrice(playerId) {
    return apiClient.get(`/cards/${playerId}/price`)
  },

  // Team Affinity Admin endpoints
  getTeamAffinityPrograms() {
    return apiClient.get('/admin/team-affinity')
  },
  createAllTeamAffinity(fresh = false) {
    return apiClient.post('/admin/team-affinity/create-all', { fresh })
  },
  bulkAddRewards(teamIds, rewards) {
    return apiClient.post('/admin/team-affinity/bulk-rewards', { team_ids: teamIds, rewards })
  },
  bulkAddChallenges(teamIds, challenges) {
    return apiClient.post('/admin/team-affinity/bulk-challenges', { team_ids: teamIds, challenges })
  },
  updateTeamAffinityReward(programId, rewardId, data) {
    return apiClient.patch(`/admin/team-affinity/${programId}/rewards/${rewardId}`, data)
  }
}

