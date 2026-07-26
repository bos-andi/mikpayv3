document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('gisMap');
    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    const map = L.map('gisMap').setView([-6.2088, 106.8456], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    L.circleMarker([-6.2088, 106.8456], {
        color: '#f59e0b',
        fillColor: '#f59e0b',
        fillOpacity: 0.9,
        radius: 10,
    })
        .addTo(map)
        .bindPopup('<b>OLT Center (HSGQ EPON)</b><br>IP: 192.168.100.1');

    L.circleMarker([-6.2065, 106.848], {
        color: '#06b6d4',
        fillColor: '#06b6d4',
        fillOpacity: 0.8,
        radius: 7,
    })
        .addTo(map)
        .bindPopup('<b>ODP-BLOK-A1</b><br>Terpakai: 4/8 Port');

    L.polyline(
        [
            [-6.2088, 106.8456],
            [-6.2065, 106.848],
        ],
        { color: '#06b6d4', weight: 3, dashArray: '5, 5' }
    ).addTo(map);

    L.circleMarker([-6.2058, 106.849], {
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.8,
        radius: 5,
    })
        .addTo(map)
        .bindPopup('<b>Client: Budi Santoso</b><br>Redaman: -17.8 dBm (Active)');

    setTimeout(() => map.invalidateSize(), 200);
});
