BASE=http://localhost:8000

# Using default fixture set from .env (MOCK_FIXTURE=nyc)
curl -i "$BASE/api/weather?lat=40.758&lon=-73.9855"

curl -s "$BASE/api/activities?lat=40.758&lon=-73.9855&radius=3000&type=all" | jq .

curl -s "$BASE/api/recommendations?lat=40.758&lon=-73.9855&radius=3000&type=all" | jq .  

# Geocoding (mocked)
curl -s "$BASE/api/geocode/search?q=Times%20Square" | jq . 
curl -s "$BASE/api/geocode/reverse?lat=40.758&lon=-73.9855" | jq . 

# Override the fixture set per-request (if you later add "sf/" etc.)
curl -s "$BASE/api/weather?lat=37.7749&lon=-122.4194&mock=nyc" | jq . 
