# local-activity-finder

## Stack

- Laravel backend
- Sample React UI
- SQLite Database
- Nominatim API for address lookup and reverse address lookups
- GeoAPIfy for poi retrieval by location
- Open Meteo API for live weather data

## Installation

1. Clone the repo
2. run "#composer setup"

## Setup

1. Configure Nominatim email address via "NOMINATIM_EMAIL" environment varible or .env file
2. Configure via GeoAPIfy Key via "GEOAPIFY_API_KEY" environment varible or .env file

## Running the server

From project root run "php artisan serve" to start the server.

## Running the sample UI (Vite)

From ui folder run "npm run dev" to start the http server.

## Building the sample UI (Vite)

From ui folder run "npm run build" to build the sample UI.

## OpenAPI spec

OpenAPI (Swagger) spec is in public/openapi.json.

## Testing the API

### Check formatting using lint

1. Run "npm run lint" from project folder.
2. Run "npm run lint" from ui folder.
3. Run "npm run format" from ui folder

### Fix laravel php formatting using pint

Run "composer exec pint -- --config pint.json"

### Fix react ui formatting using lint

npx eslint . --fix

### Use npx to format code

npx prettier . --write

### Mock Mode

- Set MOCK_MODE=true in .env to bypass external providers.
- Fixtures live under storage/app/private/fixtures/<set>/.
- Included fixtures are default and raining, set via MOCK_FIXTURE in .env

### Capture Mode

- Set DEBUG_CAPTURE=true in .env to enable capture. Set the output dir via CAPTURE_SET.
- Run "scripts/promote-fixtures.sh YOUR_BUCKET_NAME" to promote your last captured data to a data fixture.
- Data is captured to storage/private/captures
- Copy the promoted files to tests/Fixtures if you want to commit a fixture.

### Pest tests

- Run "php artisan test" to run the PEST tests. (or npm run test)

### Example curl commands

#### Testing Enviroment Vars

LAT=40.7128
LON=-74.0060
BASE=http://localhost:8000

#### OpenAPI spec

curl -s $BASE/openapi.json | jq .

#### WEATHER (happy path)

curl -s "$BASE/api/weather?lat=$LAT&lon=$LON" | jq .

#### WEATHER (validation errors)

##### missing params → 422

curl -i "$BASE/api/weather"

##### out-of-range lat → 422

curl -i "$BASE/api/weather?lat=123&lon=0"

#### ACTIVITIES (happy path)

curl -s "$BASE/api/activities?lat=$LAT&lon=$LON&radius=3000&type=all" | jq .

#### ACTIVITIES (filters)

##### indoor only

curl -s "$BASE/api/activities?lat=$LAT&lon=$LON&type=indoor" | jq .

##### outdoor only + larger radius

curl -s "$BASE/api/activities?lat=$LAT&lon=$LON&radius=8000&type=outdoor" | jq .

##### ACTIVITIES (validation errors)

###### invalid type

curl -i "$BASE/api/activities?lat=$LAT&lon=$LON&type=water"

###### radius too small

curl -i "$BASE/api/activities?lat=$LAT&lon=$LON&radius=10"

#### RECOMMENDATIONS (weather-aware ranking)

curl -s "$BASE/api/recommendations?lat=$LAT&lon=$LON&radius=3000&type=all" | jq .

#### Check caching behavior (call twice; second should be faster)

time curl -s "$BASE/api/weather?lat=$LAT&lon=$LON" > /dev/null
time curl -s "$BASE/api/weather?lat=$LAT&lon=$LON" > /dev/null

# include headers to see X-Source: mock or live and cache hints you set

curl -i "$BASE/api/weather?lat=$LAT&lon=$LON"

#### Mock mode (if you enabled config/app.mock_mode or an env flag)

curl -i "$BASE/api/activities?lat=$LAT&lon=$LON"

#### Geo search forward and reverse

##### Forward

curl -s "$BASE/api/geocode/search?q=Times%20Square&limit=3" | jq .

##### Reverse

curl -s "$BASE/api/geocode/reverse?lat=$LAT&lon=$LON" | jq .
