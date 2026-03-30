/**
 * Coordinate Picker - Simple interface for location selection
 * Works without external map dependencies
 */

class CoordinatePicker {
    constructor() {
        this.selectedLocation = null;
    }

    createSimpleInterface(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 20px; color: white; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 12px;">📍</div>
                <div style="font-weight: 600; margin-bottom: 8px;">Location Selector</div>
                <div style="font-size: 14px; margin-bottom: 16px; opacity: 0.9;">
                    Search cities or enter coordinates manually
                </div>
                
                <div id="quickCities" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; margin-top: 16px;">
                    ${this.getQuickCityButtons()}
                </div>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: 12px; opacity: 0.8;">Click a city above or use search/coordinates below</div>
                </div>
            </div>
        `;
    }

    getQuickCityButtons() {
        const majorCities = [
            { name: 'Mumbai', lat: 19.0760, lng: 72.8777 },
            { name: 'Delhi', lat: 28.7041, lng: 77.1025 },
            { name: 'Bangalore', lat: 12.9716, lng: 77.5946 },
            { name: 'Chennai', lat: 13.0827, lng: 80.2707 },
            { name: 'Kolkata', lat: 22.5726, lng: 88.3639 },
            { name: 'Hyderabad', lat: 17.3850, lng: 78.4867 },
            { name: 'Pune', lat: 18.5204, lng: 73.8567 },
            { name: 'Ahmedabad', lat: 23.0225, lng: 72.5714 }
        ];

        return majorCities.map(city => `
            <button onclick="selectQuickCity(${city.lat}, ${city.lng}, '${city.name}')" 
                    style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ${city.name}
            </button>
        `).join('');
    }

    formatCoordinates(lat, lng) {
        return {
            lat: parseFloat(lat).toFixed(6),
            lng: parseFloat(lng).toFixed(6),
            display: `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`
        };
    }
}

// Global functions for the interface
window.selectQuickCity = function(lat, lng, name) {
    if (typeof setProjectMapLocation === 'function') {
        setProjectMapLocation(lat, lng, name);
    } else {
        // Fallback if main function not available
        document.getElementById('projectLatitude').value = lat.toFixed(6);
        document.getElementById('projectLongitude').value = lng.toFixed(6);
        document.getElementById('projectPlace').value = name;
    }
    
    // Visual feedback
    const buttons = document.querySelectorAll('#quickCities button');
    buttons.forEach(btn => {
        btn.style.background = 'rgba(255,255,255,0.2)';
        btn.style.transform = 'scale(1)';
    });
    
    event.target.style.background = 'rgba(255,255,255,0.4)';
    event.target.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        event.target.style.transform = 'scale(1)';
    }, 150);
};

// Create global instance
window.CoordinatePicker = new CoordinatePicker();
