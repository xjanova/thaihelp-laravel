/**
 * ThaiHelp - Google Maps Integration
 *
 * Handles map initialization, marker management, geolocation,
 * and auto-refresh of incidents and station data.
 */

let map;
let markers = [];
let infoWindow;
let userMarker;
let currentFilter = 'all'; // 'all', 'stations', 'incidents'
let refreshInterval;

const CATEGORY_COLORS = {
    accident: '#ef4444',
    flood: '#3b82f6',
    roadblock: '#f97316',
    checkpoint: '#2563eb',
    construction: '#eab308',
    other: '#6b7280',
};

const STATION_COLORS = {
    has_fuel: '#22c55e',
    empty: '#ef4444',
    no_reports: '#f97316',
};

/**
 * Initialize Google Map centered on Thailand
 */
function initMap() {
    const thailand = { lat: 13.7563, lng: 100.5018 }; // Bangkok default

    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 7,
        center: thailand,
        mapTypeControl: false,
        streetViewControl: false,
        styles: [
            { elementType: 'geometry', stylers: [{ color: '#1a1a2e' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1a1a2e' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8892b0' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2d2d44' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1a3a' }] },
        ],
    });

    infoWindow = new google.maps.InfoWindow();

    // Try to get user location
    getUserLocation();

    // Fetch and display data
    loadMapData();

    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(loadMapData, 30000);

    // Setup filter buttons
    setupFilterButtons();
}

/**
 * Get user's current geolocation
 */
function getUserLocation() {
    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            };

            map.setCenter(pos);
            map.setZoom(12);

            if (userMarker) userMarker.setMap(null);

            userMarker = new google.maps.Marker({
                position: pos,
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#3b82f6',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3,
                },
                title: 'You are here',
                zIndex: 999,
            });
        },
        () => {
            console.log('Geolocation permission denied or unavailable');
        }
    );
}

/**
 * Load all map data (incidents + stations)
 */
async function loadMapData() {
    // Clear existing markers
    markers.forEach(m => m.setMap(null));
    markers = [];

    try {
        const [incidentsRes, stationsRes] = await Promise.all([
            fetch('/api/incidents'),
            fetch('/api/stations'),
        ]);

        const incidents = await incidentsRes.json();
        const stations = await stationsRes.json();

        if (currentFilter === 'all' || currentFilter === 'incidents') {
            renderIncidentMarkers(incidents.data || incidents);
        }

        if (currentFilter === 'all' || currentFilter === 'stations') {
            renderStationMarkers(stations.data || stations);
        }
    } catch (error) {
        console.error('Failed to load map data:', error);
    }
}

/**
 * Create markers for incidents
 */
function renderIncidentMarkers(incidents) {
    incidents.forEach(incident => {
        if (!incident.latitude || !incident.longitude) return;

        const marker = new google.maps.Marker({
            position: { lat: parseFloat(incident.latitude), lng: parseFloat(incident.longitude) },
            map: map,
            icon: {
                path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                scale: 6,
                fillColor: CATEGORY_COLORS[incident.category] || CATEGORY_COLORS.other,
                fillOpacity: 0.9,
                strokeColor: '#ffffff',
                strokeWeight: 1,
            },
            title: incident.title,
        });

        marker.addListener('click', () => {
            const categoryEmoji = {
                accident: '🚗', flood: '🌊', roadblock: '🚧',
                checkpoint: '👮', construction: '🏗️', other: '📌',
            };

            infoWindow.setContent(`
                <div style="color:#1a1a2e;max-width:250px;">
                    <h3 style="margin:0 0 4px;font-size:14px;">
                        ${categoryEmoji[incident.category] || '📌'} ${incident.title}
                    </h3>
                    <p style="margin:0 0 4px;font-size:12px;color:#666;">
                        ${incident.description || 'No description'}
                    </p>
                    <div style="font-size:11px;color:#999;">
                        👍 ${incident.upvotes || 0} upvotes
                        &bull; ${new Date(incident.created_at).toLocaleString('th-TH')}
                    </div>
                </div>
            `);
            infoWindow.open(map, marker);
        });

        markers.push(marker);
    });
}

/**
 * Create markers for stations
 */
function renderStationMarkers(stations) {
    stations.forEach(station => {
        if (!station.latitude || !station.longitude) return;

        // Determine station color based on fuel availability
        let color = STATION_COLORS.no_reports;
        if (station.fuel_reports && station.fuel_reports.length > 0) {
            const hasFuel = station.fuel_reports.some(r => r.is_available);
            color = hasFuel ? STATION_COLORS.has_fuel : STATION_COLORS.empty;
        }

        const marker = new google.maps.Marker({
            position: { lat: parseFloat(station.latitude), lng: parseFloat(station.longitude) },
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 7,
                fillColor: color,
                fillOpacity: 0.9,
                strokeColor: '#ffffff',
                strokeWeight: 2,
            },
            title: station.station_name,
        });

        marker.addListener('click', () => {
            const fuelList = (station.fuel_reports || [])
                .map(f => `<li>${f.fuel_type}: ${f.is_available ? '✅ Available' : '❌ Unavailable'}</li>`)
                .join('');

            infoWindow.setContent(`
                <div style="color:#1a1a2e;max-width:250px;">
                    <h3 style="margin:0 0 4px;font-size:14px;">⛽ ${station.station_name}</h3>
                    ${station.note ? `<p style="margin:0 0 4px;font-size:12px;color:#666;">${station.note}</p>` : ''}
                    ${fuelList ? `<ul style="margin:4px 0;padding-left:16px;font-size:12px;">${fuelList}</ul>` : '<p style="font-size:12px;color:#999;">No fuel reports yet</p>'}
                    <div style="font-size:11px;color:#999;">
                        Reported by: ${station.reporter_name || 'Anonymous'}
                    </div>
                </div>
            `);
            infoWindow.open(map, marker);
        });

        markers.push(marker);
    });
}

/**
 * Setup filter button click handlers
 */
function setupFilterButtons() {
    document.querySelectorAll('[data-map-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.mapFilter;

            // Update active button style
            document.querySelectorAll('[data-map-filter]').forEach(b => {
                b.classList.remove('bg-orange-500', 'text-white');
                b.classList.add('bg-gray-700', 'text-gray-300');
            });
            btn.classList.remove('bg-gray-700', 'text-gray-300');
            btn.classList.add('bg-orange-500', 'text-white');

            loadMapData();
        });
    });
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
});

// Expose globally for Google Maps callback
window.initMap = initMap;
