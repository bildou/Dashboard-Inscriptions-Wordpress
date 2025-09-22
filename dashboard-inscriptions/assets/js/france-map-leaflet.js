// Dans assets/js/france-map-leaflet.js

document.addEventListener('DOMContentLoaded', function () {
    // Vérifier si Leaflet est chargé et si le conteneur et les données sont présents.
    if (typeof L === 'undefined' || !document.getElementById('interactive-map') || typeof mapData === 'undefined') {
        console.error("Leaflet, le conteneur de la carte ou les données (mapData) sont manquants.");
        return;
    }

    // 1. Initialisation de la carte, centrée sur la France.
    const map = L.map('interactive-map', {
        scrollWheelZoom: true // Optionnel : désactive le zoom à la molette pour ne pas bloquer le scroll de la page.
    }).setView([46.6, 1.88], 6);

    // 2. Ajout du fond de carte OpenStreetMap.
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // --- 3. Création du Calque des Villes ---
    const cityLayer = L.layerGroup();
    if (mapData.cities && mapData.cities.length > 0) {
        const maxCityCount = Math.max(...mapData.cities.map(c => c.count), 1);

        mapData.cities.forEach(city => {
            const radius = 5 + (city.count / maxCityCount) * 30; // Calcul du rayon du cercle
            const circle = L.circle([city.lat, city.lng], {
                color: '#e60000',
                fillColor: '#ff4d4d',
                fillOpacity: 0.6,
                radius: radius * 100 // Rayon en mètres
            });
            circle.bindTooltip(`<b>${city.city}</b><br>${city.count} inscrits`);
            cityLayer.addLayer(circle);
        });
    }

    // --- 4. Création du Calque des Départements ---
    const departmentLayer = L.layerGroup();
    // On charge le fichier GeoJSON avec les contours des départements.
    fetch('https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements-avec-outre-mer.geojson')
        .then(response => response.json())
        .then(geojson => {
            if (mapData.departments) {
                const maxDeptCount = Math.max(...Object.values(mapData.departments), 1);

                L.geoJson(geojson, {
                    style: function (feature) {
                        const deptCode = feature.properties.code;
                        const count = mapData.departments[deptCode] || mapData.departments[parseInt(deptCode, 10)] || 0;
                        const colorScale = (count / maxDeptCount);
                        const fillColor = count > 0 ? `rgba(59, 130, 246, ${0.1 + colorScale * 0.8})` : '#f0f0f0'; // Bleu variable

                        return {
                            fillColor: fillColor,
                            weight: 1,
                            opacity: 1,
                            color: 'white',
                            fillOpacity: 0.75
                        };
                    },
                    onEachFeature: function (feature, layer) {
                        const deptCode = feature.properties.code;
                        const deptName = feature.properties.nom;
                        const count = mapData.departments[deptCode] || mapData.departments[parseInt(deptCode, 10)] || 0;
                        layer.bindTooltip(`<b>${deptName} (${deptCode})</b><br>${count} inscrits`);
                    }
                }).addTo(departmentLayer);
            }
        })
        .catch(error => console.error('Erreur lors du traitement de la carte GeoJSON:', error));

    // --- 5. Logique du "Toggle Switch" pour changer de calque ---
    const layerSwitch = document.getElementById('map-layer-switch');

    function updateMapLayers() {
        if (!layerSwitch) return; // Sécurité si l'élément n'existe pas

        if (layerSwitch.checked) {
            // "Villes" est sélectionné
            if (map.hasLayer(departmentLayer)) {
                map.removeLayer(departmentLayer);
            }
            if (!map.hasLayer(cityLayer)) {
                map.addLayer(cityLayer);
            }
        } else {
            // "Départements" est sélectionné (par défaut)
            if (map.hasLayer(cityLayer)) {
                map.removeLayer(cityLayer);
            }
            if (!map.hasLayer(departmentLayer)) {
                map.addLayer(departmentLayer);
            }
        }
    }

    // On écoute les changements sur la case à cocher.
    if (layerSwitch) {
        layerSwitch.addEventListener('change', updateMapLayers);
        
        // On applique l'état initial au chargement de la page.
        // Cela garantit que le bon calque est affiché au démarrage.
        updateMapLayers();
    } else {
        // Fallback si le switch n'est pas là : on affiche les départements par défaut.
        departmentLayer.addTo(map);
    }
});