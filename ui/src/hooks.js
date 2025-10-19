import { useQuery } from '@tanstack/react-query'

import { geocodeSearch, reverseGeocode, getWeather, getRecommendations } from './api'

export const useGeocode = (q) =>
  useQuery({
    queryKey: ['geocode', q],
    queryFn: () => geocodeSearch(q),
    enabled: !!q && q.length >= 2,
  })

export const useReverse = (coords) =>
  useQuery({
    queryKey: ['reverse', coords],
    queryFn: () => reverseGeocode(coords.lat, coords.lon),
    enabled: !!coords,
  })

export const useWeather = (coords) =>
  useQuery({
    queryKey: ['wx', coords],
    queryFn: () => getWeather(coords.lat, coords.lon),
    enabled: !!coords,
  })

export const useRecs = (params) =>
  useQuery({
    queryKey: ['recs', params],
    queryFn: () => getRecommendations(params),
    enabled: !!params,
  })
