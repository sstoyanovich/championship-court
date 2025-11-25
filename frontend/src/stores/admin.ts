import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const API_URL = 'http://localhost:8000/api/admin'

interface Player {
  id: number
  name: string
  nba_id?: string
  overall_rating: number
  position: string
  team: string
  card_tier: string
  image_url?: string
  collection_id?: number
  obtainable_from_packs?: boolean
  [key: string]: any
}

interface ProgramReward {
  id?: number
  xp_threshold?: number
  stars_threshold?: number
  reward_type: string
  reward_id?: number
  reward_amount?: number
  description?: string
}

interface ProgramChallenge {
  id?: number
  type: string
  target_stat?: string
  target_value: number
  constraint_type?: string
  constraint_value?: string
  stars_reward?: number
  xp_reward?: number
  description: string
}

interface Program {
  id: number
  name: string
  description?: string
  type: string
  category?: string
  team?: string
  total_xp_required?: number
  stars_required?: number
  active: boolean
  image_url?: string
  rewards?: ProgramReward[]
  challenges?: ProgramChallenge[]
}

interface Pack {
  id: number
  name: string
  type: string
  description?: string
  card_count: number
  choice_count?: number
  odds_config?: any
  guaranteed_slots?: Array<{ slot: number; min_tier: string }>
  collection_id?: number
  cost: number
  active: boolean
  available_in_shop?: boolean
  specified_players?: number[]
  featured_players?: Array<{ player_id: number; odds: number }>
  player_pools?: Array<{ name: string; odds: number; player_ids: number[] }>
}

interface Collection {
  id: number
  name: string
  type: string
  sub_collection?: string
  description?: string
  total_items: number
  active: boolean
  rewards?: any[]
}

interface CardArtImage {
  filename: string
  path: string
  fullUrl: string
}

interface PaginationData {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export const useAdminStore = defineStore('admin', () => {
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Players
  const players = ref<Player[]>([])
  const playersPagination = ref<PaginationData | null>(null)
  const allPlayers = ref<Player[]>([])
  const cardArtImages = ref<CardArtImage[]>([])

  async function fetchCardArtImages() {
    try {
      const response = await axios.get(`${API_URL}/card-art-images`)
      cardArtImages.value = response.data.data
      return { success: true }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'Failed to fetch card art images' }
    }
  }

  async function fetchAllPlayers() {
    try {
      const response = await axios.get(`${API_URL}/players`, {
        params: { per_page: 1000 }
      })
      allPlayers.value = response.data.data.data
      return { success: true }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'Failed to fetch players' }
    }
  }

  async function fetchPlayers(page = 1, search = '', perPage = 15, collectionId: number | null = null) {
    loading.value = true
    error.value = null
    try {
      const params: any = { page, search, per_page: perPage }
      if (collectionId !== null && collectionId !== undefined) {
        params.collection_id = collectionId
      }
      const response = await axios.get(`${API_URL}/players`, { params })
      players.value = response.data.data.data
      playersPagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total
      }
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch players'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function getPlayer(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/players/${id}`)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch player'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function createPlayer(playerData: Partial<Player>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/players`, playerData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create player'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function updatePlayer(id: number, playerData: Partial<Player>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_URL}/players/${id}`, playerData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update player'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deletePlayer(id: number) {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`${API_URL}/players/${id}`)
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete player'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function bulkUpdatePlayers(playerIds: number[], updates: any) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/players/bulk-update`, {
        player_ids: playerIds,
        updates: updates
      })
      return { success: true, message: response.data.message }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to bulk update players'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Programs
  const programs = ref<Program[]>([])
  const programsPagination = ref<PaginationData | null>(null)
  const allPrograms = ref<Program[]>([])

  async function fetchAllPrograms() {
    try {
      const response = await axios.get(`${API_URL}/programs`, {
        params: { per_page: 1000 }
      })
      allPrograms.value = response.data.data.data
      return { success: true }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'Failed to fetch programs' }
    }
  }

  async function fetchPrograms(page = 1, search = '', perPage = 15) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/programs`, {
        params: { page, search, per_page: perPage }
      })
      programs.value = response.data.data.data
      programsPagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total
      }
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch programs'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function getProgram(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/programs/${id}`)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch program'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function createProgram(programData: Partial<Program>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/programs`, programData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create program'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function updateProgram(id: number, programData: Partial<Program>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_URL}/programs/${id}`, programData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update program'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deleteProgram(id: number) {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`${API_URL}/programs/${id}`)
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete program'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Packs
  const packs = ref<Pack[]>([])
  const packsPagination = ref<PaginationData | null>(null)
  const allPacks = ref<Pack[]>([])

  async function fetchAllPacks() {
    try {
      const response = await axios.get(`${API_URL}/packs`, {
        params: { per_page: 1000 }
      })
      allPacks.value = response.data.data.data
      return { success: true }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'Failed to fetch packs' }
    }
  }

  async function fetchPacks(page = 1, search = '', perPage = 15) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/packs`, {
        params: { page, search, per_page: perPage }
      })
      packs.value = response.data.data.data
      packsPagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total
      }
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch packs'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function getPack(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/packs/${id}`)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch pack'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function createPack(packData: Partial<Pack>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/packs`, packData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create pack'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function updatePack(id: number, packData: Partial<Pack>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_URL}/packs/${id}`, packData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update pack'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deletePack(id: number) {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`${API_URL}/packs/${id}`)
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete pack'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Collections
  const collections = ref<Collection[]>([])
  const collectionsPagination = ref<PaginationData | null>(null)
  const allCollections = ref<Collection[]>([])

  async function fetchAllCollections() {
    try {
      const response = await axios.get(`${API_URL}/collections`, {
        params: { per_page: 1000 }
      })
      allCollections.value = response.data.data.data
      return { success: true }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'Failed to fetch collections' }
    }
  }

  async function fetchCollections(page = 1, search = '', perPage = 15) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/collections`, {
        params: { page, search, per_page: perPage }
      })
      collections.value = response.data.data.data
      collectionsPagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        per_page: response.data.data.per_page,
        total: response.data.data.total
      }
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch collections'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function getCollection(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/collections/${id}`)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch collection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function createCollection(collectionData: Partial<Collection>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/collections`, collectionData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create collection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function updateCollection(id: number, collectionData: Partial<Collection>) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_URL}/collections/${id}`, collectionData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update collection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deleteCollection(id: number) {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`${API_URL}/collections/${id}`)
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete collection'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Collection Rewards
  async function fetchCollectionRewards(collectionId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`${API_URL}/collections/${collectionId}/rewards`)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch collection rewards'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function createCollectionReward(collectionId: number, rewardData: any) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(`${API_URL}/collections/${collectionId}/rewards`, rewardData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create reward'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function updateCollectionReward(rewardId: number, rewardData: any) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`${API_URL}/rewards/${rewardId}`, rewardData)
      return { success: true, data: response.data.data }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update reward'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function deleteCollectionReward(rewardId: number) {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`${API_URL}/rewards/${rewardId}`)
      return { success: true }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete reward'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    // Players
    players,
    playersPagination,
    allPlayers,
    cardArtImages,
    fetchPlayers,
    fetchAllPlayers,
    fetchCardArtImages,
    getPlayer,
    createPlayer,
    updatePlayer,
    deletePlayer,
    bulkUpdatePlayers,
    // Programs
    programs,
    programsPagination,
    allPrograms,
    fetchPrograms,
    fetchAllPrograms,
    getProgram,
    createProgram,
    updateProgram,
    deleteProgram,
    // Packs
    packs,
    packsPagination,
    allPacks,
    fetchPacks,
    fetchAllPacks,
    getPack,
    createPack,
    updatePack,
    deletePack,
    // Collections
    collections,
    collectionsPagination,
    allCollections,
    fetchCollections,
    fetchAllCollections,
    getCollection,
    createCollection,
    updateCollection,
    deleteCollection,
    // Collection Rewards
    fetchCollectionRewards,
    createCollectionReward,
    updateCollectionReward,
    deleteCollectionReward
  }
})

