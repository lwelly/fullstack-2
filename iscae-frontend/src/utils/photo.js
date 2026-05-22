/**
 * Construit l'URL absolue d'une photo de profil (photo_url ou photo_path).
 */
export function resolvePhotoUrl(pathOrUrl) {
  if (!pathOrUrl) return null
  const p = String(pathOrUrl).trim()
  if (!p) return null
  if (p.startsWith('http://') || p.startsWith('https://') || p.startsWith('blob:')) return p

  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
  const base = (import.meta.env.VITE_API_BASE_URL ?? apiUrl.replace(/\/api\/v\d+\/?$/, '')).replace(/\/$/, '')

  if (p.startsWith('/storage/')) return `${base}${p}`
  if (p.startsWith('storage/')) return `${base}/${p}`
  return `${base}/storage/${p.replace(/^\//, '')}`
}
