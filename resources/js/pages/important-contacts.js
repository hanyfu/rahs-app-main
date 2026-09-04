function contactsPage(props = {}) {
        return {
            filters: { search: '' },

            searchableContacts: props.searchableContacts || [],

            init() {},

            matches(value) {
                const query = this.filters.search.trim().toLocaleLowerCase();
                return !query || String(value).toLocaleLowerCase().includes(query);
            },

            hasMatches() {
                return this.searchableContacts.some((contact) => this.matches(contact));
            },

            openCreate() {
                window.location.href = '/important-contacts-admin';
            },
        };
    }

window.contactsPage = contactsPage;
