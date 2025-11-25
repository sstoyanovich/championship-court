import { defineStore } from 'pinia'
import programService from '../services/programService'

interface Program {
  id: number
  name: string
  description: string
  type: 'xp' | 'star'
  team?: string | null
  category?: 'general' | 'team_affinity'
  total_xp_required: number | null
  stars_required: number
  image_url: string | null
  rewards_count: number
  challenges_count: number
  user_progress?: {
    current_xp: number
    current_stars: number
    completed: boolean
    completed_at: string | null
    progress_percentage: number
  }
}

interface ProgramDetail extends Program {
  rewards: any[]
  challenges: any[]
}

export const useProgramStore = defineStore('program', {
  state: () => ({
    programs: [] as Program[],
    teamAffinityPrograms: [] as Program[],
    currentProgram: null as ProgramDetail | null,
    userProgress: [] as any[],
    loading: false,
    teamAffinityLoading: false,
    error: null as string | null,
    userId: 1, // TODO: Get from auth/user store
  }),

  getters: {
    xpPrograms: (state) => state.programs.filter(p => p.type === 'xp' && p.category !== 'team_affinity'),
    starPrograms: (state) => state.programs.filter(p => p.type === 'star' && p.category !== 'team_affinity'),
    
    activePrograms: (state) => state.programs.filter(p => 
      !p.user_progress?.completed && p.category !== 'team_affinity'
    ),
    
    completedPrograms: (state) => state.programs.filter(p => 
      p.user_progress?.completed && p.category !== 'team_affinity'
    ),

    teamAffinityByDivision: (state) => {
      // Group Team Affinity programs by division
      const divisions = {
        Atlantic: ['Boston Celtics', 'Brooklyn Nets', 'New York Knicks', 'Philadelphia 76ers', 'Toronto Raptors'],
        Central: ['Chicago Bulls', 'Cleveland Cavaliers', 'Detroit Pistons', 'Indiana Pacers', 'Milwaukee Bucks'],
        Southeast: ['Atlanta Hawks', 'Charlotte Hornets', 'Miami Heat', 'Orlando Magic', 'Washington Wizards'],
        Northwest: ['Denver Nuggets', 'Minnesota Timberwolves', 'Oklahoma City Thunder', 'Portland Trail Blazers', 'Utah Jazz'],
        Pacific: ['Golden State Warriors', 'LA Clippers', 'Los Angeles Lakers', 'Phoenix Suns', 'Sacramento Kings'],
        Southwest: ['Dallas Mavericks', 'Houston Rockets', 'Memphis Grizzlies', 'New Orleans Pelicans', 'San Antonio Spurs']
      }

      const grouped: Record<string, Program[]> = {}
      
      for (const [division, teams] of Object.entries(divisions)) {
        grouped[division] = state.teamAffinityPrograms.filter(p => 
          p.team && teams.includes(p.team)
        ).sort((a, b) => (a.team || '').localeCompare(b.team || ''))
      }

      return grouped
    }
  },

  actions: {
    async fetchPrograms() {
      this.loading = true
      this.error = null
      try {
        const response = await programService.getPrograms(this.userId, 'general')
        if (response.success) {
          this.programs = response.data
        }
      } catch (error: any) {
        this.error = error.message || 'Failed to load programs'
        console.error('Error fetching programs:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchTeamAffinityPrograms() {
      this.teamAffinityLoading = true
      this.error = null
      try {
        const response = await programService.getTeamAffinityPrograms(this.userId)
        if (response.success) {
          this.teamAffinityPrograms = response.data
        }
      } catch (error: any) {
        this.error = error.message || 'Failed to load Team Affinity programs'
        console.error('Error fetching Team Affinity programs:', error)
      } finally {
        this.teamAffinityLoading = false
      }
    },

    async fetchProgramDetails(programId: number) {
      this.loading = true
      this.error = null
      try {
        const response = await programService.getProgramDetails(programId, this.userId)
        if (response.success) {
          this.currentProgram = response.data
        }
      } catch (error: any) {
        this.error = error.message || 'Failed to load program details'
        console.error('Error fetching program details:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchUserProgress() {
      try {
        const response = await programService.getUserProgress(this.userId)
        if (response.success) {
          this.userProgress = response.data
        }
      } catch (error: any) {
        console.error('Error fetching user progress:', error)
      }
    },

    async claimReward(rewardId: number) {
      try {
        const response = await programService.claimReward(rewardId, this.userId)
        if (response.success) {
          // Refresh current program to update claimed status
          if (this.currentProgram) {
            await this.fetchProgramDetails(this.currentProgram.id)
          }
        }
        return response
      } catch (error: any) {
        console.error('Error claiming reward:', error)
        throw error
      }
    },

    setUserId(userId: number) {
      this.userId = userId
    },
  },
})

