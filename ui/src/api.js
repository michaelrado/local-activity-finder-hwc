const base = import.meta.env.VITE_API_BASE ?? '' // set to "/" in prod if you want

const j = async (r) => {
  if (!r.ok) throw new Error(`HTTP ${r.status}`)
  return r.json()
}

export const geocodeSearch = (q, limit = 5) =>
  fetch(`${base}/api/geocode/search?q=${encodeURIComponent(q)}&limit=${limit}`).then(j)

export const reverseGeocode = (lat, lon) =>
  fetch(`${base}/api/geocode/reverse?lat=${lat}&lon=${lon}`).then(j)

export const getWeather = (lat, lon) => fetch(`${base}/api/weather?lat=${lat}&lon=${lon}`).then(j)

export const getRecommendations = ({ lat, lon, radius = 3000, type = 'all' }) =>
  fetch(`${base}/api/recommendations?lat=${lat}&lon=${lon}&radius=${radius}&type=${type}`).then(j)
