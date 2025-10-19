import { useEffect, useMemo, useState } from 'react'

import { useGeocode, useWeather, useRecs } from './hooks'

const getSaved = () => JSON.parse(localStorage.getItem('saved') || '[]')
const setSaved = (x) => localStorage.setItem('saved', JSON.stringify(x))

export default function App() {
  const [coords, setCoords] = useState(null) // { lat, lon }
  const [query, setQuery] = useState({ radius: 3000, type: 'all' })
  const [search, setSearch] = useState('') // text box
  const [saved, setSavedState] = useState(getSaved())

  useEffect(() => {
    setSaved(saved)
  }, [saved])

  const params = useMemo(() => (coords ? { ...coords, ...query } : null), [coords, query])

  const { data: searchData, isLoading: searching } = useGeocode(search.length >= 2 ? search : null)
  //const { data: rev } = useReverse(coords ?? null)            // pretty place name
  const { data: wx, isLoading: wxLoading } = useWeather(coords ?? null)
  const { data: recs, isLoading: recsLoading } = useRecs(params)

  const onUseMyLocation = () =>
    navigator.geolocation.getCurrentPosition(
      (pos) =>
        setCoords({ lat: +pos.coords.latitude.toFixed(5), lon: +pos.coords.longitude.toFixed(5) }),
      () => alert('Location permission denied. Enter a place or coordinates.'),
    )

  const onPickSearchResult = (item) => {
    setCoords({ lat: item.lat, lon: item.lon })
    setSearch(item.display_name)
  }

  const toggleSave = (item) => {
    const exists = saved.find((s) => s.id === item.id)
    const next = exists ? saved.filter((s) => s.id !== item.id) : [...saved, item]
    setSavedState(next)
  }

  return (
    <div className="container">
      <header>
        <h1>Local Activity Finder</h1>
        <p className="muted">
          Search a place or use your location → see weather → see forecast → see recommendations →
          save picks
        </p>
      </header>

      <section className="card">
        <div className="row">
          <label className="grow">
            <span>Search place</span>
            <input
              aria-label="Search place"
              placeholder="Type a place to search, e.g., Philadelphia, PA"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </label>
          <button onClick={onUseMyLocation}>Use my location</button>
        </div>

        {searching && <div className="muted">Searching…</div>}
        {searchData?.items?.length ? (
          <ul className="list">
            {searchData.items.slice(0, 5).map((it, i) => (
              <li key={i} className="row">
                <button
                  className="link"
                  onClick={() => onPickSearchResult(it)}
                  title="Set coordinates"
                >
                  {it.display_name}
                </button>
                <span className="muted">
                  {it.lat.toFixed(5)}, {it.lon.toFixed(5)}
                </span>
              </li>
            ))}
          </ul>
        ) : null}

        <div className="row compact">
          <label>
            <span>Lat</span>
            <input
              type="number"
              step="0.0001"
              value={coords?.lat ?? ''}
              onChange={(e) => setCoords((c) => ({ ...(c || {}), lat: Number(e.target.value) }))}
            />
          </label>
          <label>
            <span>Lon</span>
            <input
              type="number"
              step="0.0001"
              value={coords?.lon ?? ''}
              onChange={(e) => setCoords((c) => ({ ...(c || {}), lon: Number(e.target.value) }))}
            />
          </label>
          <label>
            <span>Radius (m)</span>
            <input
              type="number"
              min="100"
              max="20000"
              value={query.radius}
              onChange={(e) => setQuery((q) => ({ ...q, radius: Number(e.target.value) }))}
            />
          </label>
          <label>
            <span>Type</span>
            <select
              value={query.type}
              onChange={(e) => setQuery((q) => ({ ...q, type: e.target.value }))}
            >
              <option value="all">All</option>
              <option value="indoor">Indoor</option>
              <option value="outdoor">Outdoor</option>
            </select>
          </label>
        </div>
      </section>

      <section className="grid">
        <div className="card">
          <h3>Current Weather</h3>
          {!coords && <p className="muted">Pick a place to see weather</p>}
          {wxLoading && <p>Loading weather…</p>}
          {wx && (
            <p>
              Temp <strong>{wx.tempC}°C</strong> · Wind <strong>{wx.windKph} kph</strong> · Precip{' '}
              <strong>{wx.precipProb}%</strong> ({wx.precipMm} mm)
            </p>
          )}
        </div>

        <section className="card">
          <h3>Forecast</h3>
          {!coords && <p className="muted">Pick a place to see forecast</p>}
          {wxLoading && <p>Loading forecast…</p>}
          {wx && (
            <>
              {Array.isArray(wx.hourlyDetail) && wx.hourlyDetail.length > 0 && (
                <div className="table-scroll">
                  <table className="table">
                    <thead>
                      <tr>
                        <th style={{ width: '36%' }}>Time</th>
                        <th>°C</th>
                        <th>mm</th>
                        <th>Prob%</th>
                        <th>Wind kph</th>
                      </tr>
                    </thead>
                    <tbody>
                      {wx.hourlyDetail.slice(0, 240).map((h, i) => (
                        <tr key={i}>
                          <td>{new Date(h.time).toLocaleString()}</td>
                          <td>{Math.round(h.tempC)}</td>
                          <td>{h.precipMm}</td>
                          <td>{h.precipProb}</td>
                          <td>{h.windKph}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </>
          )}
        </section>

        <div className="card">
          <h3>Recommendations</h3>
          {!coords && <p className="muted">Set coordinates to load recommendations</p>}
          {recsLoading && <p>Loading…</p>}
          {recs?.items?.length ? (
            <ul className="list">
              {recs.items.map((it) => (
                <li key={it.id} className="row">
                  <div className="grow">
                    <div>
                      <strong>{it.name}</strong> {it.indoor ? '· Indoor' : '· Outdoor'}
                    </div>
                    <div className="muted">
                      {Math.round(it.distanceM)} m · score {it.score.toFixed(2)}
                    </div>
                  </div>
                  <button onClick={() => toggleSave(it)}>
                    {saved.find((s) => s.id === it.id) ? 'Unsave' : 'Save'}
                  </button>
                </li>
              ))}
            </ul>
          ) : coords && !recsLoading ? (
            <p>No results.</p>
          ) : null}
        </div>

        <div className="card">
          <h3>Saved</h3>
          {saved.length ? (
            <ul className="list">
              {saved.map((s) => (
                <li key={s.id} className="row">
                  <div className="grow">{s.name}</div>
                  <button onClick={() => toggleSave(s)}>Remove</button>
                </li>
              ))}
            </ul>
          ) : (
            <p className="muted">Nothing saved yet.</p>
          )}
        </div>
      </section>

      <footer className="muted" style={{ marginTop: 16 }}>
        Local Activity Finder submission by Michael Rado 2025
      </footer>
    </div>
  )
}
