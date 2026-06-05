// src/api/aiAnalysis.js
// Utilise l'instance Axios existante dans votre projet (ajuster l'import si nécessaire)
import api from './axios'

/**
 * Déclenche l'analyse IA d'une réclamation.
 * @param {number} reclamationId
 * @param {object} options - { attachment_id?: number, force_reanalyze?: boolean }
 */
export const analyzeReclamation = (reclamationId, options = {}) => {
  return api.post(`/admin/reclamations/${reclamationId}/analyze`, options)
}

/**
 * Récupère l'analyse IA existante sans relancer OpenAI.
 * @param {number} reclamationId
 */
export const getAnalysis = (reclamationId) => {
  return api.get(`/admin/reclamations/${reclamationId}/analysis`)
}