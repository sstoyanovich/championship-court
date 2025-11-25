import api from './api';

export default {
  /**
   * Get all active programs with optional category filter
   */
  async getPrograms(userId = null, category = null) {
    const response = await api.getPrograms(category);
    return response.data;
  },

  /**
   * Get Team Affinity programs
   */
  async getTeamAffinityPrograms(userId = null) {
    const response = await api.getPrograms('team_affinity');
    return response.data;
  },

  /**
   * Get a specific program with detailed information
   */
  async getProgramDetails(programId, userId = null) {
    const response = await api.getProgram(programId);
    return response.data;
  },

  /**
   * Get user's progress across all programs
   */
  async getUserProgress(userId) {
    const response = await api.getUserProgress();
    return response.data;
  },

  /**
   * Claim a reward
   */
  async claimReward(rewardId, userId) {
    const response = await api.claimReward(rewardId);
    return response.data;
  }
};

