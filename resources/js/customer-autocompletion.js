/**
 * Customer Form Address & Location Autocompletion Module
 * Integrates OpenStreetMap (Nominatim) for address suggestions and REST Countries for Country & Nationality mapping.
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

class CustomerFormAutocompletion {
    constructor() {
        this.countryToNationalityMap = { ...FALLBACK_COUNTRY_NATIONALITY };
        this.countries = Object.keys(FALLBACK_COUNTRY_NATIONALITY);
        this.nationalities = Object.values(FALLBACK_COUNTRY_NATIONALITY);
        this.debounceTimer = null;
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
        if (this.countryInput && !document.getElementById('country-list')) {
            const countryDatalist = document.createElement('datalist');
            countryDatalist.id = 'country-list';
            document.body.appendChild(countryDatalist);
            this.countryInput.setAttribute('list', 'country-list');
            this.countryDatalist = countryDatalist;
        }

        if (this.nationalityInput && !document.getElementById('nationality-list')) {
            const nationalityDatalist = document.createElement('datalist');
            nationalityDatalist.id = 'nationality-list';
            document.body.appendChild(nationalityDatalist);
            this.nationalityInput.setAttribute('list', 'nationality-list');
            this.nationalityDatalist = nationalityDatalist;
        }

        this.updateDatalists();
    }

    updateDatalists() {
        if (this.countryDatalist) {
            this.countryDatalist.innerHTML = this.countries
                .map(country => `<option value="${this.escapeHtml(country)}"></option>`)
                .join('');
        }

        if (this.nationalityDatalist) {
            const uniqueNationalities = [...new Set(this.nationalities)];
            this.nationalityDatalist.innerHTML = uniqueNationalities
                .map(nat => `<option value="${this.escapeHtml(nat)}"></option>`)
                .join('');
        }
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
            this.updateDatalists();
        } catch (e) {
            // Silently fallback to built-in dictionary if offline or blocked
        }
    }

    bindEvents() {
        // Auto-set nationality when country changes
        if (this.countryInput) {
            this.countryInput.addEventListener('change', () => this.handleCountryChange());
            this.countryInput.addEventListener('input', () => this.handleCountryChange());
        }

        // Setup Address Autocomplete UI
        if (this.addressInput) {
            this.setupAddressSuggestionsUI();
        }
    }

    handleCountryChange() {
        const countryVal = this.countryInput.value.trim();
        if (!countryVal || !this.nationalityInput) return;

        // Exact match or partial case-insensitive match
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

    setupAddressSuggestionsUI() {
        const wrapper = document.createElement('div');
        wrapper.className = 'address-autocomplete-wrapper';
        this.addressInput.parentNode.insertBefore(wrapper, this.addressInput);
        wrapper.appendChild(this.addressInput);

        const badge = document.createElement('small');
        badge.className = 'address-autocomplete-badge';
        badge.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>Saisie assistée (OpenStreetMap)';
        wrapper.appendChild(badge);

        const dropdown = document.createElement('ul');
        dropdown.className = 'address-suggestions-dropdown hidden';
        dropdown.setAttribute('role', 'listbox');
        wrapper.appendChild(dropdown);
        this.dropdown = dropdown;

        this.addressInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(this.debounceTimer);

            if (query.length < 3) {
                this.hideDropdown();
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.fetchAddressSuggestions(query);
            }, 350);
        });

        this.addressInput.addEventListener('keydown', (e) => {
            if (!this.dropdown || this.dropdown.classList.contains('hidden')) return;

            const items = this.dropdown.querySelectorAll('.address-suggestion-item');
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
                this.hideDropdown();
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                this.hideDropdown();
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

    async fetchAddressSuggestions(query) {
        try {
            // Append country if present for more targeted local search
            const countryFilter = this.countryInput?.value?.trim();
            const searchUrl = new URL('https://nominatim.openstreetmap.org/search');
            searchUrl.searchParams.set('q', countryFilter ? `${query}, ${countryFilter}` : query);
            searchUrl.searchParams.set('format', 'json');
            searchUrl.searchParams.set('addressdetails', '1');
            searchUrl.searchParams.set('limit', '5');
            searchUrl.searchParams.set('accept-language', 'fr');

            const response = await fetch(searchUrl.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                this.hideDropdown();
                return;
            }

            const results = await response.json();
            this.renderSuggestions(results);
        } catch (e) {
            this.hideDropdown();
        }
    }

    renderSuggestions(results) {
        if (!results || results.length === 0) {
            this.hideDropdown();
            return;
        }

        this.selectedIndex = -1;
        this.dropdown.innerHTML = '';

        results.forEach(item => {
            const addr = item.address || {};
            const road = addr.road || addr.pedestrian || addr.house_number || addr.amenity || item.name || '';
            const suburb = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || addr.borough || addr.city_district || '';
            const city = addr.city || addr.town || addr.village || addr.municipality || addr.county || '';
            const country = addr.country || '';

            const primaryText = road ? (addr.house_number ? `${addr.house_number} ${road}` : road) : item.display_name.split(',')[0];
            const secondaryText = [suburb, city, country].filter(Boolean).join(', ');

            const li = document.createElement('li');
            li.className = 'address-suggestion-item';
            li.setAttribute('role', 'option');
            li.innerHTML = `
                <div class="suggestion-main"><i class="bi bi-geo-alt me-2 text-primary"></i><strong>${this.escapeHtml(primaryText)}</strong></div>
                <div class="suggestion-sub text-muted small ms-4">${this.escapeHtml(secondaryText)}</div>
            `;

            li.addEventListener('click', () => {
                this.applySuggestion({
                    address: primaryText || item.display_name,
                    commune: suburb,
                    city: city,
                    country: country
                });
                this.hideDropdown();
            });

            this.dropdown.appendChild(li);
        });

        this.dropdown.classList.remove('hidden');
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
        }

        if (this.countryInput && country) {
            this.countryInput.value = country;
            this.triggerEvent(this.countryInput);
            this.handleCountryChange();
        }
    }

    triggerEvent(element) {
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    hideDropdown() {
        if (this.dropdown) {
            this.dropdown.classList.add('hidden');
            this.dropdown.innerHTML = '';
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
