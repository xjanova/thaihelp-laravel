/**
 * ThaiHelp - Google Maps Integration
 *
 * Handles map initialization, marker management, geolocation,
 * radar animation, and demo/live data display.
 */

let map;
let markers = [];
let infoWindow;
let userMarker;
let radarCircle;
let radarPulse;
let currentFilter = 'all';
let refreshInterval;
let userPosition = null;

const CATEGORY_COLORS = {
    accident: '#ef4444',
    flood: '#3b82f6',
    roadblock: '#f97316',
    checkpoint: '#2563eb',
    construction: '#eab308',
    other: '#6b7280',
};

const CATEGORY_EMOJI = {
    accident: '🚗', flood: '🌊', roadblock: '🚧',
    checkpoint: '👮', construction: '🏗️', other: '📌',
};

const FUEL_LABELS = {
    gasohol95: 'แก๊สโซฮอล์ 95',
    gasohol91: 'แก๊สโซฮอล์ 91',
    e20: 'แก๊สโซฮอล์ E20',
    e85: 'แก๊สโซฮอล์ E85',
    diesel: 'ดีเซล',
    diesel_b7: 'ดีเซล B7',
    premium_diesel: 'ดีเซลพรีเมียม',
    ngv: 'NGV',
    lpg: 'LPG',
};

const STATUS_COLORS = {
    available: { bg: '#22c55e', text: '✅ มี', border: '#16a34a' },
    low: { bg: '#eab308', text: '⚠️ เหลือน้อย', border: '#ca8a04' },
    empty: { bg: '#ef4444', text: '❌ หมด', border: '#dc2626' },
    unknown: { bg: '#6b7280', text: '❓ ไม่ทราบ', border: '#4b5563' },
};

/**
 * Initialize Google Map
 */
function initMap() {
    const thailand = { lat: 13.7563, lng: 100.5018 };

    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 7,
        center: thailand,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        styles: [
            { elementType: 'geometry', stylers: [{ color: '#1a1a2e' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1a1a2e' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8892b0' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2d2d44' }] },
            { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#6b7280' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1a3a' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        ],
    });

    infoWindow = new google.maps.InfoWindow();

    getUserLocation();
    loadMapData();
    refreshInterval = setInterval(loadMapData, 30000);
    setupFilterButtons();
}

/**
 * Get user's geolocation with radar animation
 */
function getUserLocation() {
    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            userPosition = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            };

            map.setCenter(userPosition);
            map.setZoom(13);

            if (userMarker) userMarker.setMap(null);
            if (radarCircle) radarCircle.setMap(null);

            // User dot
            userMarker = new google.maps.Marker({
                position: userPosition,
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#3b82f6',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3,
                },
                title: 'ตำแหน่งของคุณ',
                zIndex: 999,
            });

            // Radar pulse animation
            startRadarPulse(userPosition);

            // Reload data with user position for nearby search
            loadMapData();
        },
        () => {
            console.log('Geolocation unavailable');
        }
    );
}

/**
 * Radar scanning pulse animation
 */
function startRadarPulse(pos) {
    let pulseRadius = 50;
    let growing = true;

    radarCircle = new google.maps.Circle({
        center: pos,
        radius: pulseRadius,
        map: map,
        fillColor: '#3b82f6',
        fillOpacity: 0.1,
        strokeColor: '#3b82f6',
        strokeOpacity: 0.4,
        strokeWeight: 2,
        clickable: false,
        zIndex: 1,
    });

    if (radarPulse) clearInterval(radarPulse);

    radarPulse = setInterval(() => {
        if (growing) {
            pulseRadius += 30;
            if (pulseRadius >= 800) growing = false;
        } else {
            pulseRadius -= 30;
            if (pulseRadius <= 50) growing = true;
        }

        if (radarCircle) {
            radarCircle.setRadius(pulseRadius);
            const opacity = Math.max(0.02, 0.15 - (pulseRadius / 8000));
            radarCircle.setOptions({
                fillOpacity: opacity,
                strokeOpacity: Math.max(0.1, 0.4 - (pulseRadius / 2000)),
            });
        }
    }, 50);
}

/**
 * Load all map data
 */
async function loadMapData() {
    markers.forEach(m => m.setMap(null));
    markers = [];

    try {
        const requests = [fetch('/api/incidents')];

        if (userPosition) {
            requests.push(fetch(`/api/stations?lat=${userPosition.lat}&lng=${userPosition.lng}&radius=10000`));
        }

        const responses = await Promise.all(requests);
        const incidents = await responses[0].json();

        if (currentFilter === 'all' || currentFilter === 'incidents') {
            renderIncidentMarkers(incidents.data || incidents || []);
        }

        if (responses[1] && (currentFilter === 'all' || currentFilter === 'stations')) {
            const stations = await responses[1].json();
            renderStationMarkers(stations.data || stations || []);
        }

        updateCounters();
    } catch (error) {
        console.error('Failed to load map data:', error);
    }
}

/**
 * Render incident markers with demo/live badges
 */
function renderIncidentMarkers(incidents) {
    (Array.isArray(incidents) ? incidents : []).forEach(incident => {
        if (!incident.latitude || !incident.longitude) return;

        const isDemo = incident.is_demo;
        const color = CATEGORY_COLORS[incident.category] || CATEGORY_COLORS.other;

        const marker = new google.maps.Marker({
            position: { lat: parseFloat(incident.latitude), lng: parseFloat(incident.longitude) },
            map: map,
            icon: {
                path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                scale: 6,
                fillColor: color,
                fillOpacity: isDemo ? 0.5 : 0.9,
                strokeColor: isDemo ? '#fbbf24' : '#ffffff',
                strokeWeight: isDemo ? 2 : 1,
            },
            title: `${isDemo ? '[DEMO] ' : ''}${incident.title}`,
        });

        marker.addListener('click', () => {
            const badge = isDemo
                ? '<span style="background:#fbbf24;color:#000;padding:1px 6px;border-radius:9px;font-size:10px;font-weight:bold;">DEMO</span>'
                : '<span style="background:#22c55e;color:#fff;padding:1px 6px;border-radius:9px;font-size:10px;font-weight:bold;">LIVE</span>';

            infoWindow.setContent(`
                <div style="color:#1a1a2e;max-width:280px;font-family:sans-serif;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        ${badge}
                        <span style="font-size:14px;font-weight:bold;">
                            ${CATEGORY_EMOJI[incident.category] || '📌'} ${incident.title}
                        </span>
                    </div>
                    <p style="margin:0 0 6px;font-size:12px;color:#555;">${incident.description || ''}</p>
                    <div style="font-size:11px;color:#888;">
                        👍 ${incident.upvotes || 0} &bull; ${timeAgo(incident.created_at)}
                    </div>
                </div>
            `);
            infoWindow.open(map, marker);
        });

        markers.push(marker);
    });
}

/**
 * Render station markers with fuel status colors and demo/live badges
 */
function renderStationMarkers(stations) {
    (Array.isArray(stations) ? stations : []).forEach(station => {
        const lat = parseFloat(station.lat || station.latitude);
        const lng = parseFloat(station.lng || station.longitude);
        if (!lat || !lng) return;

        const isDemo = station.is_demo;
        const fuels = station.fuel_reports || [];

        // Determine overall color
        let color = '#6b7280'; // gray = no reports
        if (fuels.length > 0) {
            const hasAvailable = fuels.some(f => f.status === 'available');
            const hasLow = fuels.some(f => f.status === 'low');
            const allEmpty = fuels.every(f => f.status === 'empty');

            if (allEmpty) color = '#ef4444'; // red
            else if (hasAvailable) color = '#22c55e'; // green
            else if (hasLow) color = '#eab308'; // yellow
        }

        const marker = new google.maps.Marker({
            position: { lat, lng },
            map: map,
            icon: {
                path: 'M-4,-12 L4,-12 L4,0 L0,4 L-4,0 Z', // gas pump shape
                scale: 1.8,
                fillColor: color,
                fillOpacity: isDemo ? 0.6 : 1,
                strokeColor: isDemo ? '#fbbf24' : '#ffffff',
                strokeWeight: isDemo ? 2 : 1.5,
                anchor: new google.maps.Point(0, 4),
            },
            title: `${isDemo ? '[DEMO] ' : ''}${station.name || station.station_name}`,
        });

        marker.addListener('click', () => {
            const badge = isDemo
                ? '<span style="background:#fbbf24;color:#000;padding:1px 6px;border-radius:9px;font-size:10px;font-weight:bold;">DEMO</span>'
                : '<span style="background:#22c55e;color:#fff;padding:1px 6px;border-radius:9px;font-size:10px;font-weight:bold;">LIVE</span>';

            const verifiedBadge = station.is_verified
                ? '<span style="background:#3b82f6;color:#fff;padding:1px 6px;border-radius:9px;font-size:10px;">✓ ยืนยันแล้ว</span>'
                : '';

            let fuelHtml = '';
            if (fuels.length > 0) {
                fuelHtml = '<div style="margin:6px 0;">';
                fuels.forEach(f => {
                    const s = STATUS_COLORS[f.status] || STATUS_COLORS.unknown;
                    const price = f.price ? `฿${parseFloat(f.price).toFixed(2)}` : '';
                    fuelHtml += `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:3px 0;border-bottom:1px solid #eee;">
                            <span style="font-size:12px;">${FUEL_LABELS[f.fuel_type] || f.fuel_type}</span>
                            <div style="display:flex;gap:6px;align-items:center;">
                                ${price ? `<span style="font-size:11px;color:#666;">${price}</span>` : ''}
                                <span style="background:${s.bg};color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">${s.text}</span>
                            </div>
                        </div>
                    `;
                });
                fuelHtml += '</div>';
            } else {
                fuelHtml = '<p style="font-size:12px;color:#999;margin:6px 0;">ยังไม่มีรายงาน</p>';
            }

            infoWindow.setContent(`
                <div style="color:#1a1a2e;max-width:300px;font-family:sans-serif;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                        ${badge} ${verifiedBadge}
                    </div>
                    <h3 style="margin:4px 0;font-size:14px;">⛽ ${station.name || station.station_name}</h3>
                    ${station.vicinity || station.note ? `<p style="margin:0 0 2px;font-size:11px;color:#666;">${station.vicinity || station.note}</p>` : ''}
                    ${fuelHtml}
                    <div style="font-size:11px;color:#888;margin-top:4px;">
                        ${station.reporter_name ? `📝 ${station.reporter_name}` : ''}
                        ${station.last_report_at ? ` &bull; ${timeAgo(station.last_report_at)}` : ''}
                        ${station.confirmation_count ? ` &bull; 👥 ${station.confirmation_count} ยืนยัน` : ''}
                    </div>
                </div>
            `);
            infoWindow.open(map, marker);
        });

        markers.push(marker);
    });
}

/**
 * Setup filter buttons
 */
function setupFilterButtons() {
    document.querySelectorAll('[data-map-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.mapFilter;

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

/**
 * Update stat counters in the UI
 */
function updateCounters() {
    const stationCount = markers.filter(m => m.getTitle()?.includes('⛽') || m.getTitle()?.includes('PTT') || m.getTitle()?.includes('Shell') || m.getTitle()?.includes('Bangchak')).length;
    const el = document.getElementById('station-count');
    if (el) el.textContent = stationCount;
}

/**
 * Time ago helper (Thai)
 */
function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60) return 'เมื่อสักครู่';
    if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`;
    return `${Math.floor(diff / 86400)} วันที่แล้ว`;
}

// Cleanup
window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (radarPulse) clearInterval(radarPulse);
});

// Expose globally
window.initMap = initMap;
window.loadMapData = loadMapData;
