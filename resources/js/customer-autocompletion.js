/**
 * Customer Form Location Autocompletion Module
 * Provides real-time autocompletion for Address, Commune, City, Country and Nationality.
 * Scopes City by Country, and Commune by City & Country.
 */

const FALLBACK_COUNTRY_NATIONALITY = {
    "Côte d'Ivoire": "Ivoirienne",
    "République Démocratique du Congo": "Congolaise",
    "Congo": "Congolaise",
    "Togo": "Togolaise",
    "Bénin": "Béninoise",
    "Sénégal": "Sénégalaise",
    "Mali": "Malienne",
    "Burkina Faso": "Burkinabé",
    "Guinée": "Guinéenne",
    "Cameroun": "Camerounaise",
    "Gabon": "Gabonaise",
    "France": "Française",
    "Belgique": "Belge",
    "Suisse": "Suisse",
    "Canada": "Canadienne",
    "États-Unis": "Américaine",
    "Maroc": "Marocaine",
    "Algérie": "Algérienne",
    "Tunisie": "Tunisienne",
    "Ghana": "Ghanéenne",
    "Nigeria": "Nigériane",
    "Niger": "Nigérienne",
    "Tchad": "Tchadienne",
    "Mauritanie": "Mauritanienne",
    "Rwanda": "Rwandaise",
    "Burundi": "Burundaise",
    "République Centrafricaine": "Centrafricaine",
    "Haïti": "Haïtienne",
    "Madagascar": "Malgache"
};

const POPULAR_CITIES = {
    "Côte d'Ivoire": ["Abidjan", "Yamoussoukro", "Bouaké", "San-Pédro", "Korhogo", "Daloa", "Man", "Gagnoa"],
    "République Démocratique du Congo": ["Kinshasa", "Lubumbashi", "Mbuji-Mayi", "Kananga", "Kisangani", "Goma", "Bukavu", "Likasi", "Kikwit", "Matadi"],
    "Congo": ["Brazzaville", "Pointe-Noire", "Dolisie", "Nkayi"],
    "Togo": ["Lomé", "Sokodé", "Kara", "Kpalimé", "Atakpamé"],
    "Bénin": ["Cotonou", "Porto-Novo", "Parakou", "Abomey-Calavi", "Djougou"],
    "Sénégal": ["Dakar", "Thiès", "Kaolack", "Ziguinchor", "Saint-Louis", "Touba", "Mbour"],
    "Mali": ["Bamako", "Sikasso", "Mopti", "Koutiala", "Kayes", "Ségou"],
    "Burkina Faso": ["Ouagadougou", "Bobo-Dioulasso", "Koudougou", "Banfora"],
    "Guinée": ["Conakry", "Nzérékoré", "Kankan", "Kindia", "Labé"],
    "Cameroun": ["Douala", "Yaoundé", "Garoua", "Bamenda", "Maroua", "Bafoussam"],
    "Gabon": ["Libreville", "Port-Gentil", "Franceville", "Oyem"],
    "France": ["Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Montpellier", "Strasbourg", "Bordeaux", "Lille"],
    "Belgique": ["Bruxelles", "Anvers", "Gand", "Charleroi", "Liège"],
    "Suisse": ["Zurich", "Genève", "Bâle", "Lausanne", "Berne"],
    "Canada": ["Montréal", "Québec", "Toronto", "Vancouver", "Ottawa"],
    "États-Unis": ["New York", "Los Angeles", "Chicago", "Houston", "Washington"],
    "Maroc": ["Casablanca", "Rabat", "Marrakech", "Tanger", "Fès", "Agadir"],
    "Algérie": ["Alger", "Oran", "Constantine", "Annaba"],
    "Tunisie": ["Tunis", "Sfax", "Sousse", "Bizerte"]
};

const POPULAR_COMMUNES = {
    "Abidjan": ["Abobo", "Adjamé", "Attécoubé", "Anyama", "Bingerville", "Cocody", "Koumassi", "Marcory", "Plateau", "Port-Bouët", "Songon", "Treichville", "Yopougon"],
    "Kinshasa": ["Bandalungwa", "Barumbu", "Bumbu", "Gombe", "Kalamu", "Kasa-Vubu", "Kimbanseke", "Kinshasa", "Kintambo", "Kisenso", "Lemba", "Limete", "Lingwala", "Makala", "Maluku", "Masina", "Matete", "Mont-Ngafula", "Ndjili", "Ngaba", "Ngaliema", "Ngiri-Ngiri", "Nsele", "Selembao"],
    "Lomé": ["Golfe 1 (Bè-Afédomé)", "Golfe 2 (Tokoin)", "Golfe 3 (Résidence)", "Golfe 4 (Amoutivé)", "Golfe 5 (Aflao-Gakli)", "Golfe 6 (Bè-Kpota)", "Golfe 7 (Aflao-Sagbado)"],
    "Dakar": ["Dakar Plateau", "Fann-Point E-Amitié", "Gorée", "Gueule Tapée-Fass-Colobane", "Médina", "Grand Dakar", "Hann Bel-Air", "HLM", "Mermoz-Sacré-Cœur", "Ouakam", "Yoff", "Ngor", "Parcelles Assainies"],
    "Paris": ["1er Arrondissement", "2e Arrondissement", "3e Arrondissement", "4e Arrondissement", "5e Arrondissement", "6e Arrondissement", "7e Arrondissement", "8e Arrondissement", "9e Arrondissement", "10e Arrondissement", "11e Arrondissement", "12e Arrondissement", "13e Arrondissement", "14e Arrondissement", "15e Arrondissement", "16e Arrondissement", "17e Arrondissement", "18e Arrondissement", "19e Arrondissement", "20e Arrondissement"]
};

class CustomerFormAutocompletion {
    constructor() {
        this.countryToNationalityMap = { ...FALLBACK_COUNTRY_NATIONALITY };
        this.countries = Object.keys(FALLBACK_COUNTRY_NATIONALITY);
        this.nationalities = Object.values(FALLBACK_COUNTRY_NATIONALITY);
        this.debounceTimers = {};
        this.activeDropdown = null;
        this.selectedIndex = -1;
    }

    async init() {
        const formContainer = document.querySelector('[data-customer-form]');
        if (!formContainer) return;

        this.addressInput = formContainer.querySelector('#address') || document.querySelector('textarea[name="address"]');
        this.communeInput = formContainer.querySelector('#commune') || document.querySelector('input[name="commune"]');
        this.cityInput = formContainer.querySelector('#city') || document.querySelector('input[name="city"]');
        this.countryInput = formContainer.querySelector('#country') || document.querySelector('input[name="country"]');
        this.nationalityInput = formContainer.querySelector('#nationality') || document.querySelector('input[name="nationality"]');

        this.setupDatalists();
        this.loadCountriesData();
        this.bindEvents();
    }

    setupDatalists() {
        this.createDatalist('country-list', this.countryInput);
        this.createDatalist('nationality-list', this.nationalityInput);
        this.createDatalist('city-list', this.cityInput);
        this.createDatalist('commune-list', this.communeInput);

        this.updateCountryNationalityDatalists();
        this.updateCityDatalist();
        this.updateCommuneDatalist();
    }

    createDatalist(id, inputElement) {
        if (!inputElement) return;
        let datalist = document.getElementById(id);
        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = id;
            document.body.appendChild(datalist);
        }
        inputElement.setAttribute('list', id);
        this[id] = datalist;
    }

    updateCountryNationalityDatalists() {
        if (this['country-list']) {
            this['country-list'].innerHTML = this.countries
                .map(country => `<option value="${this.escapeHtml(country)}"></option>`)
                .join('');
        }

        if (this['nationality-list']) {
            const uniqueNationalities = [...new Set(this.nationalities)];
            this['nationality-list'].innerHTML = uniqueNationalities
                .map(nat => `<option value="${this.escapeHtml(nat)}"></option>`)
                .join('');
        }
    }

    updateCityDatalist() {
        if (!this['city-list']) return;

        const selectedCountry = this.countryInput?.value?.trim();
        let cities = [];

        if (selectedCountry && POPULAR_CITIES[selectedCountry]) {
            cities = POPULAR_CITIES[selectedCountry];
        } else {
            // Flatten all popular cities
            cities = [...new Set(Object.values(POPULAR_CITIES).flat())];
        }

        this['city-list'].innerHTML = cities
            .map(city => `<option value="${this.escapeHtml(city)}"></option>`)
            .join('');
    }

    updateCommuneDatalist() {
        if (!this['commune-list']) return;

        const selectedCity = this.cityInput?.value?.trim();
        let communes = [];

        if (selectedCity && POPULAR_COMMUNES[selectedCity]) {
            communes = POPULAR_COMMUNES[selectedCity];
        } else {
            communes = [...new Set(Object.values(POPULAR_COMMUNES).flat())];
        }

        this['commune-list'].innerHTML = communes
            .map(commune => `<option value="${this.escapeHtml(commune)}"></option>`)
            .join('');
    }

    async loadCountriesData() {
        try {
            const response = await fetch('https://restcountries.com/v3.1/all?fields=name,translations,demonyms');
            if (!response.ok) return;
            const data = await response.json();

            data.forEach(item => {
                const frenchName = item.translations?.fra?.common || item.name?.common;
                const frenchDemonym = item.demonyms?.fra?.f || item.demonyms?.fra?.m;

                if (frenchName) {
                    if (!this.countries.includes(frenchName)) {
                        this.countries.push(frenchName);
                    }
                    if (frenchDemonym) {
                        if (!this.nationalities.includes(frenchDemonym)) {
                            this.nationalities.push(frenchDemonym);
                        }
                        this.countryToNationalityMap[frenchName] = frenchDemonym;
                    }
                }
            });

            this.countries.sort((a, b) => a.localeCompare(b, 'fr'));
            this.nationalities.sort((a, b) => a.localeCompare(b, 'fr'));
            this.updateCountryNationalityDatalists();
        } catch (e) {
            // Silently fallback to built-in list
        }
    }

    bindEvents() {
        // Country change handler
        if (this.countryInput) {
            const handleCountry = () => {
                this.handleCountryChange();
                this.updateCityDatalist();
                this.fetchRemoteCitiesForCountry(this.countryInput.value.trim());
            };

            this.countryInput.addEventListener('change', handleCountry);
            this.countryInput.addEventListener('input', handleCountry);
        }

        // City change handler
        if (this.cityInput) {
            const handleCity = () => {
                this.updateCommuneDatalist();
            };

            this.cityInput.addEventListener('change', handleCity);
            this.cityInput.addEventListener('input', handleCity);
        }

        // Setup Autocomplete Dropdowns on Address, City, and Commune
        if (this.addressInput) {
            this.setupInputAutocompletion(this.addressInput, 'address');
        }

        if (this.cityInput) {
            this.setupInputAutocompletion(this.cityInput, 'city');
        }

        if (this.communeInput) {
            this.setupInputAutocompletion(this.communeInput, 'commune');
        }
    }

    handleCountryChange() {
        const countryVal = this.countryInput.value.trim();
        if (!countryVal || !this.nationalityInput) return;

        const foundKey = Object.keys(this.countryToNationalityMap).find(
            k => k.toLowerCase() === countryVal.toLowerCase()
        );

        if (foundKey && this.countryToNationalityMap[foundKey]) {
            if (!this.nationalityInput.value || this.nationalityInput.dataset.autoFilled === 'true') {
                this.nationalityInput.value = this.countryToNationalityMap[foundKey];
                this.nationalityInput.dataset.autoFilled = 'true';
            }
        }
    }

    async fetchRemoteCitiesForCountry(countryName) {
        if (!countryName) return;

        try {
            const response = await fetch('https://countriesnow.space/api/v0.1/countries/cities', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country: countryName })
            });

            if (!response.ok) return;
            const resData = await response.json();

            if (resData.data && Array.isArray(resData.data) && resData.data.length > 0) {
                POPULAR_CITIES[countryName] = resData.data;
                this.updateCityDatalist();
            }
        } catch (e) {
            // Ignore API failures silently
        }
    }

    setupInputAutocompletion(inputElement, fieldType) {
        const wrapper = document.createElement('div');
        wrapper.className = 'address-autocomplete-wrapper';
        inputElement.parentNode.insertBefore(wrapper, inputElement);
        wrapper.appendChild(inputElement);

        const badge = document.createElement('small');
        badge.className = 'address-autocomplete-badge';
        const badgeLabel = fieldType === 'city' ? 'Saisie assistée des villes' :
                           fieldType === 'commune' ? 'Saisie assistée des communes' :
                           'Saisie assistée d’adresse';

        badge.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i>${badgeLabel}`;
        wrapper.appendChild(badge);

        const dropdown = document.createElement('ul');
        dropdown.className = 'address-suggestions-dropdown hidden';
        dropdown.setAttribute('role', 'listbox');
        wrapper.appendChild(dropdown);

        inputElement.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(this.debounceTimers[fieldType]);

            if (query.length < 2) {
                this.hideDropdown(dropdown);
                return;
            }

            this.debounceTimers[fieldType] = setTimeout(() => {
                this.fetchLocationSuggestions(query, fieldType, dropdown);
            }, 300);
        });

        inputElement.addEventListener('keydown', (e) => {
            if (dropdown.classList.contains('hidden')) return;
            const items = dropdown.querySelectorAll('.address-suggestion-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex + 1) % items.length;
                this.updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex - 1 + items.length) % items.length;
                this.updateActiveItem(items);
            } else if (e.key === 'Enter' && this.selectedIndex >= 0) {
                e.preventDefault();
                items[this.selectedIndex].click();
            } else if (e.key === 'Escape') {
                this.hideDropdown(dropdown);
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                this.hideDropdown(dropdown);
            }
        });
    }

    updateActiveItem(items) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === this.selectedIndex);
            if (idx === this.selectedIndex) {
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    async fetchLocationSuggestions(query, fieldType, dropdown) {
        try {
            const countryFilter = this.countryInput?.value?.trim() || '';
            const cityFilter = this.cityInput?.value?.trim() || '';

            const searchUrl = new URL('https://nominatim.openstreetmap.org/search');
            let searchQ = query;

            if (fieldType === 'city') {
                searchQ = countryFilter ? `${query}, ${countryFilter}` : query;
                searchUrl.searchParams.set('featuretype', 'settlement');
            } else if (fieldType === 'commune') {
                const context = [cityFilter, countryFilter].filter(Boolean).join(', ');
                searchQ = context ? `${query}, ${context}` : query;
            } else if (fieldType === 'address') {
                searchQ = countryFilter ? `${query}, ${countryFilter}` : query;
            }

            searchUrl.searchParams.set('q', searchQ);
            searchUrl.searchParams.set('format', 'json');
            searchUrl.searchParams.set('addressdetails', '1');
            searchUrl.searchParams.set('limit', '6');
            searchUrl.searchParams.set('accept-language', 'fr');

            const response = await fetch(searchUrl.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                this.hideDropdown(dropdown);
                return;
            }

            const results = await response.json();
            this.renderSuggestions(results, fieldType, dropdown);
        } catch (e) {
            this.hideDropdown(dropdown);
        }
    }

    renderSuggestions(results, fieldType, dropdown) {
        if (!results || results.length === 0) {
            this.hideDropdown(dropdown);
            return;
        }

        this.selectedIndex = -1;
        dropdown.innerHTML = '';

        results.forEach(item => {
            const addr = item.address || {};
            const road = addr.road || addr.pedestrian || addr.house_number || addr.amenity || item.name || '';
            const suburb = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || addr.borough || addr.city_district || '';
            const city = addr.city || addr.town || addr.village || addr.municipality || addr.county || '';
            const country = addr.country || '';

            let primaryText = '';
            let secondaryText = '';

            if (fieldType === 'city') {
                primaryText = city || item.name || item.display_name.split(',')[0];
                secondaryText = country;
            } else if (fieldType === 'commune') {
                primaryText = suburb || item.name || item.display_name.split(',')[0];
                secondaryText = [city, country].filter(Boolean).join(', ');
            } else {
                primaryText = road ? (addr.house_number ? `${addr.house_number} ${road}` : road) : item.display_name.split(',')[0];
                secondaryText = [suburb, city, country].filter(Boolean).join(', ');
            }

            if (!primaryText) return;

            const li = document.createElement('li');
            li.className = 'address-suggestion-item';
            li.setAttribute('role', 'option');
            li.innerHTML = `
                <div class="suggestion-main"><i class="bi bi-geo-alt me-2 text-primary"></i><strong>${this.escapeHtml(primaryText)}</strong></div>
                ${secondaryText ? `<div class="suggestion-sub text-muted small ms-4">${this.escapeHtml(secondaryText)}</div>` : ''}
            `;

            li.addEventListener('click', () => {
                this.applySuggestion({
                    address: fieldType === 'address' ? primaryText : null,
                    commune: fieldType === 'commune' ? primaryText : suburb,
                    city: city || (fieldType === 'city' ? primaryText : null),
                    country: country
                });
                this.hideDropdown(dropdown);
            });

            dropdown.appendChild(li);
        });

        if (dropdown.children.length === 0) {
            this.hideDropdown(dropdown);
            return;
        }

        dropdown.classList.remove('hidden');
    }

    applySuggestion({ address, commune, city, country }) {
        if (this.addressInput && address) {
            this.addressInput.value = address;
            this.triggerEvent(this.addressInput);
        }

        if (this.communeInput && commune) {
            this.communeInput.value = commune;
            this.triggerEvent(this.communeInput);
        }

        if (this.cityInput && city) {
            this.cityInput.value = city;
            this.triggerEvent(this.cityInput);
            this.updateCommuneDatalist();
        }

        if (this.countryInput && country) {
            this.countryInput.value = country;
            this.triggerEvent(this.countryInput);
            this.handleCountryChange();
            this.updateCityDatalist();
        }
    }

    triggerEvent(element) {
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    hideDropdown(dropdown) {
        if (dropdown) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        }
        this.selectedIndex = -1;
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const autocompletion = new CustomerFormAutocompletion();
    autocompletion.init();
});

export default CustomerFormAutocompletion;
