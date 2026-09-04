import { parseCsvRows } from '../lib/csv';

function hospitalsPage(props = {}) {
        return {
            contacts: props.contacts || [],
            atolls: props.atolls || [],
            islands: props.islands || [],
            role: props.role || "",
            editableIslandIds: props.editableIslandIds || [],
            coverage: props.coverage || { updated: 0, total: 0, missing: [] },
            canManageHospitals: !!props.canManageHospitals,
            canEditProfiles: !!props.canEditProfiles,

            filters: { search: '', atoll: 'all', island: 'all' },
            selected: new Set(),

            showForm: false,
            editing: false,
            editingContact: null,
            form: {},

            importOpen: false,
            importing: false,
            csvData: '',
            showPreview: false,
            parsedData: [],
            selectedRows: new Set(),

            profileOpen: false,
            profileLoading: false,
            profile: {},
            profileHospitalName: '',
            profileContact: {},
            profileEditing: false,
            profileSaving: false,
            profileSnapshot: null,
            profileCanEdit: false,
            profileStatusOptions: ['Adequate', 'Limited', 'Critical', 'Not Available'],
            profileMetricFields: [
                { field: 'grade', label: 'Facility grade', type: 'text' },
                { field: 'no_of_beds', label: 'Licensed beds' },
                { field: 'population', label: 'Population served' },
                { field: 'avg_outpatient_per_day', label: 'Outpatients per day' },
                { field: 'avg_inpatient_per_month', label: 'Inpatients per month' },
                { field: 'ambulance_total', label: 'Total ambulances' },
                { field: 'ambulance_running_condition', label: 'Operational ambulances' },
            ],
            staffGroups: [
                { id: 'medical', label: 'Medical staff', description: 'Doctors and clinical specialists', fields: [
                    { label: 'Physiotherapy', field: 'staff_physiotherapy' }, { label: 'Dermatology', field: 'staff_dermatology' },
                    { label: 'Orthopaedics', field: 'staff_ortho' }, { label: 'Medicine', field: 'staff_medicine' },
                    { label: 'Surgeon', field: 'staff_surgeon' }, { label: 'Gynaecology', field: 'staff_gynaecology' },
                    { label: 'Paediatrician', field: 'staff_paediatrician' }, { label: 'ENT', field: 'staff_ent' },
                    { label: 'Dental', field: 'staff_dental' }, { label: 'Ophthalmology', field: 'staff_ophthalmology' },
                    { label: 'Psychology', field: 'staff_psychology' }, { label: 'Radiology', field: 'staff_radiology' },
                    { label: 'Anesthesiologist', field: 'staff_anesthesiologist' }, { label: 'Medical Officer', field: 'staff_medical_officer' },
                    { label: 'Psychiatrist', field: 'staff_psychiatrist' },
                ]},
                { id: 'nursing', label: 'Nursing staff', description: 'Nursing workforce by level', fields: [
                    { label: 'Clinical Nurses', field: 'nurses_clinical' }, { label: 'Senior Registered Nurses', field: 'nurses_senior_registered' },
                    { label: 'Registered Nurses', field: 'nurses_registered' }, { label: 'Enrolled Nurses', field: 'nurses_enrolled' },
                ]},
                { id: 'support', label: 'Admin & support', description: 'Operations and support personnel', fields: [
                    { label: 'Senior Admin Officers', field: 'admin_officers_senior' }, { label: 'Admin Officers', field: 'admin_officers' },
                    { label: 'Customer Service', field: 'customer_service' }, { label: 'Drivers', field: 'drivers' },
                    { label: 'Lab Technicians', field: 'lab_tech' }, { label: 'Other Staff', field: 'other_staffs' },
                ]},
            ],
            statusFields: [
                { field: 'medical_consumables_status', label: 'Medical Consumables' },
                { field: 'laboratory_reagents_status', label: 'Lab Reagents' },
                { field: 'life_saving_drugs_status', label: 'Life Saving Drugs' },
                { field: 'sto_pharmacy_status', label: 'STO Pharmacy' },
                { field: 'staff_status', label: 'Staff Status' },
                { field: 'building_status', label: 'Building Status' },
            ],
            serviceFields: [
                { field: 'emergency_room_service', label: 'Emergency room' },
                { field: 'operation_theatre_service', label: 'Operation theatre' },
                { field: 'lab_service_available', label: 'Laboratory' },
                { field: 'poct_available', label: 'Point-of-care testing' },
                { field: 'radiology_service', label: 'Radiology' },
                { field: 'public_health_unit_service', label: 'Public health unit' },
                { field: 'sterilization_service', label: 'Sterilization' },
                { field: 'launch_boat_service', label: 'Launch / boat transport' },
            ],

            islandsForFilter() {
                if (this.filters.atoll === 'all') return this.islands;
                return this.islands.filter((i) => i.atoll_id === this.filters.atoll);
            },

            islandsByAtoll(atollId) {
                if (!atollId) return [];
                return this.islands.filter((i) => i.atoll_id === atollId);
            },

            hasActiveFilters() {
                return this.filters.search || this.filters.atoll !== 'all' || this.filters.island !== 'all';
            },

            filteredContacts() {
                const q = this.filters.search.toLowerCase();
                return this.contacts.filter((c) => {
                    if (this.filters.island !== 'all' && c.island_id !== this.filters.island) return false;
                    if (this.filters.atoll !== 'all' && c.island?.atoll_id !== this.filters.atoll) return false;
                    if (!q) return true;
                    return (c.hospital_name + ' ' + (c.manager_name || '') + ' ' + (c.contact_number || '') + ' ' + (c.contact_designation || '')).toLowerCase().includes(q);
                });
            },

            allSelected() {
                const list = this.filteredContacts();
                return list.length > 0 && list.every((c) => this.selected.has(c.id));
            },

            toggleSelectAll(checked) {
                this.filteredContacts().forEach((c) => {
                    if (c.island_facility) return;
                    if (checked) this.selected.add(c.id);
                    else this.selected.delete(c.id);
                });
            },

            toggleSelect(id, checked, isIslandFacility) {
                if (isIslandFacility) return;
                if (checked) this.selected.add(id);
                else this.selected.delete(id);
            },

            openCreate() {
                this.editing = false;
                this.form = { hospital_name: '', atoll_id: '', island_id: '', manager_name: '', contact_number: '', contact_designation: '' };
                this.showForm = true;
            },

            openEdit(contact) {
                this.editing = true;
                this.editingContact = contact;
                this.form = {
                    hospital_name: contact.hospital_name,
                    atoll_id: contact.island?.atoll_id || '',
                    island_id: contact.island_id || '',
                    manager_name: contact.manager_name,
                    contact_number: contact.contact_number,
                    contact_designation: contact.contact_designation || '',
                };
                this.showForm = true;
            },

            async save() {
                try {
                    const payload = {
                        hospital_name: this.form.hospital_name,
                        island_id: this.form.island_id || null,
                        manager_name: this.form.manager_name,
                        contact_number: this.form.contact_number,
                        contact_designation: this.form.contact_designation || null,
                    };
                    if (this.editing) {
                        const updated = await window.api.patch(`/api/hospital-contacts/${this.editingContact.id}`, payload);
                        const idx = this.contacts.findIndex((c) => c.id === updated.id);
                        if (idx >= 0) this.contacts[idx] = updated;
                        Alpine.store('toast').success('Contact updated');
                    } else {
                        const created = await window.api.post('/api/hospital-contacts', payload);
                        this.contacts.unshift(created);
                        Alpine.store('toast').success('Contact added');
                    }
                    this.showForm = false;
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async deactivate(contact) {
                if (!await window.confirmAction(`Deactivate "${contact.hospital_name}"?`, { title: 'Deactivate hospital contact', confirmLabel: 'Deactivate' })) return;
                try {
                    await window.api.post('/api/hospital-contacts/deactivate', { ids: [contact.id] });
                    this.contacts = this.contacts.filter((c) => c.id !== contact.id);
                    this.selected.delete(contact.id);
                    Alpine.store('toast').success('Contact deactivated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async deactivateSelected() {
                const count = this.selected.size;
                if (!await window.confirmAction(`Deactivate ${count} selected contact${count !== 1 ? 's' : ''}?`, { title: 'Deactivate selected contacts', confirmLabel: 'Deactivate' })) return;
                try {
                    await window.api.post('/api/hospital-contacts/deactivate', { ids: [...this.selected] });
                    this.contacts = this.contacts.filter((c) => !this.selected.has(c.id));
                    this.selected.clear();
                    Alpine.store('toast').success('Contacts deactivated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            exportCsv() {
                const rows = this.filteredContacts();
                if (rows.length === 0) {
                    Alpine.store('toast').error('Nothing to export');
                    return;
                }
                const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
                const header = ['Hospital Name', 'Atoll', 'Island', 'Manager Name', 'Contact Number', 'Contact Designation'];
                const lines = [header.join(',')];
                rows.forEach((c) => {
                    lines.push([
                        esc(c.hospital_name),
                        esc(c.island?.atoll?.name || ''),
                        esc(c.island?.name || ''),
                        esc(c.manager_name),
                        esc(c.contact_number),
                        esc(c.contact_designation || ''),
                    ].join(','));
                });
                const blob = new Blob([lines.join('\n')], { type: 'text/csv' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'hospital_contacts.csv';
                a.click();
                URL.revokeObjectURL(a.href);
            },

            downloadTemplate() {
                const template = 'Hospital Name,Island Name,Atoll Name,Manager Name,Contact Number,Contact Designation\nExample Hospital,MA. Male,Malé,John Doe,7771234,Hospital Manager';
                const blob = new Blob([template], { type: 'text/csv' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'hospital_contacts_template.csv';
                a.click();
                URL.revokeObjectURL(a.href);
            },

            handleFileUpload(e) {
                const file = e.target.files?.[0];
                if (!file) return;
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    Alpine.store('toast').error('Please upload a valid CSV file');
                    e.target.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    Alpine.store('toast').error('CSV file must be 5 MB or smaller');
                    e.target.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (event) => {
                    const text = String(event.target?.result || '');
                    const rows = parseCsvRows(text);
                    if (rows.length < 2) {
                        Alpine.store('toast').error('CSV file is empty or missing data rows');
                        this.parsedData = [];
                        this.showPreview = false;
                        return;
                    }
                    const headers = rows[0].map((header) => header.replace(/^\uFEFF/, '').trim().toLowerCase());
                    const getIndex = (...names) => headers.findIndex((h) => names.includes(h));
                    const hospitalIdx = getIndex('hospital name', 'hospital_name', 'hospital');
                    const islandIdx = getIndex('island name', 'island_name', 'island');
                    const atollIdx = getIndex('atoll name', 'atoll_name', 'atoll');
                    const managerIdx = getIndex('manager name', 'manager_name', 'manager');
                    const contactIdx = getIndex('contact number', 'contact_number', 'phone', 'phone number');
                    const designationIdx = getIndex('contact designation', 'contact_designation', 'designation');

                    if (hospitalIdx === -1 || managerIdx === -1 || contactIdx === -1) {
                        Alpine.store('toast').error('CSV is missing required columns: Hospital Name, Manager Name, Contact Number');
                        this.parsedData = [];
                        return;
                    }

                    const parsed = rows.slice(1).map((cols) => {
                        return {
                            hospital_name: cols[hospitalIdx] || '',
                            island_name: islandIdx >= 0 ? (cols[islandIdx] || '') : '',
                            atoll_name: atollIdx >= 0 ? (cols[atollIdx] || '') : '',
                            manager_name: cols[managerIdx] || '',
                            contact_number: cols[contactIdx] || '',
                            contact_designation: designationIdx >= 0 ? (cols[designationIdx] || '') : '',
                        };
                    }).filter((r) => r.hospital_name || r.manager_name || r.contact_number);

                    if (parsed.length === 0) {
                        Alpine.store('toast').error('No valid contact rows found in CSV');
                        this.parsedData = [];
                        return;
                    }

                    this.parsedData = parsed.map((r) => this.matchIsland(r));
                    this.selectedRows = new Set(this.parsedData.map((_, i) => i));
                    this.importOpen = false;
                    this.showPreview = true;
                };
                reader.readAsText(file);
            },

            matchIsland(row) {
                const islandName = (row.island_name || '').trim();
                const atollName = (row.atoll_name || '').trim();
                let matched = null;
                let matchType = 'none';

                const norm = (s) => s.toLowerCase().replace(/\s+/g, ' ').trim();
                const target = norm(islandName);
                const atollTarget = norm(atollName);

                if (islandName) {
                    const atollScoped = atollTarget ? this.atolls.filter((a) => norm(a.name) === atollTarget) : [];
                    const scopedIslands = atollScoped.length
                        ? this.islands.filter((i) => atollScoped.some((a) => a.id === i.atoll_id))
                        : [];

                    const exact = (list) => list.find((i) => norm(i.name) === target);
                    const partial = (list) => list.find((i) => norm(i.name).includes(target) || target.includes(norm(i.name)));

                    const prefixMatch = this.matchPrefixFormat(row);

                    matched = exact(scopedIslands.length ? scopedIslands : this.islands);
                    if (matched) {
                        matchType = 'exact';
                    } else {
                        matched = partial(scopedIslands.length ? scopedIslands : this.islands);
                        if (matched) matchType = 'partial';
                    }
                    if (!matched && prefixMatch) {
                        matched = prefixMatch;
                        matchType = 'partial';
                    }
                }

                return {
                    ...row,
                    matchType,
                    matchedIsland: matched ? this.islands.find((i) => i.id === matched.id) || null : null,
                    matchedAtoll: matched ? this.atolls.find((a) => a.id === matched.atoll_id) || null : null,
                };
            },

            matchPrefixFormat(row) {
                const m = (row.island_name || '').trim().match(/^([A-Za-z]+)\.\s*(.+)$/);
                if (!m) return null;
                const [, code, name] = m;
                const atoll = this.atolls.find((a) => {
                    const parts = (a.name || '').split('.');
                    return parts.length > 1 && parts[0].trim().toLowerCase() === code.toLowerCase();
                });
                if (!atoll) return null;
                const island = this.islands.find((i) =>
                    i.atoll_id === atoll.id && i.name.toLowerCase().trim() === name.toLowerCase().trim()
                );
                return island || null;
            },

            toggleRow(index) {
                if (this.selectedRows.has(index)) this.selectedRows.delete(index);
                else this.selectedRows.add(index);
            },

            selectAllRows() {
                this.selectedRows = new Set(this.parsedData.map((_, i) => i));
            },

            deselectAllRows() {
                this.selectedRows = new Set();
            },

            matchedCount() {
                return this.parsedData.filter((r) => r.matchType !== 'none').length;
            },

            unmatchedCount() {
                return this.parsedData.filter((r) => r.matchType === 'none').length;
            },

            validCount() {
                return this.selectedRows.size;
            },

            closePreview() {
                this.showPreview = false;
                this.parsedData = [];
                this.selectedRows = new Set();
                this.csvData = '';
            },

            async confirmImport() {
                this.importing = true;
                try {
                    const contacts = [...this.selectedRows].sort((a, b) => a - b).map((i) => {
                        const r = this.parsedData[i];
                        return {
                            hospital_name: r.hospital_name,
                            island_id: r.matchedIsland?.id || null,
                            manager_name: r.manager_name,
                            contact_number: r.contact_number,
                            contact_designation: r.contact_designation || null,
                        };
                    });
                    const res = await window.api.post('/api/hospital-contacts/import', { contacts });
                    Alpine.store('toast').success(`Imported ${res.imported} contacts`);
                    this.closePreview();
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                } finally {
                    this.importing = false;
                }
            },

            async openProfile(contact) {
                if (!contact.island_id) {
                    Alpine.store('toast').error('No island record associated with this hospital.');
                    return;
                }
                this.profileOpen = true;
                this.profileLoading = true;
                this.profile = {};
                this.profileEditing = false;
                this.profileSnapshot = null;
                this.profileHospitalName = contact.hospital_name;
                this.profileContact = contact;
                this.profileCanEdit = this.canEditProfile(contact);
                try {
                    const p = await window.api.get(`/api/hospital-profiles/${contact.id}`);
                    this.profile = p && typeof p === 'object' ? p : {};
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                } finally {
                    this.profileLoading = false;
                }
            },

            canEditProfile(contact) {
                if (!contact?.island_id) return false;
                return this.canEditProfiles && this.editableIslandIds.includes(contact.island_id);
            },

            totalMedicalStaff() {
                const p = this.profile || {};
                return ['staff_physiotherapy', 'staff_dermatology', 'staff_ortho', 'staff_medicine', 'staff_surgeon', 'staff_gynaecology', 'staff_paediatrician', 'staff_ent', 'staff_dental', 'staff_ophthalmology', 'staff_psychology', 'staff_radiology', 'staff_anesthesiologist', 'staff_medical_officer', 'staff_psychiatrist'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
            },

            totalNursingStaff() {
                const p = this.profile || {};
                return ['nurses_clinical', 'nurses_senior_registered', 'nurses_registered', 'nurses_enrolled'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
            },

            totalAdminStaff() {
                const p = this.profile || {};
                return ['admin_officers_senior', 'admin_officers', 'customer_service', 'drivers', 'lab_tech', 'other_staffs'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
            },

            totalStaff() {
                return this.totalMedicalStaff() + this.totalNursingStaff() + this.totalAdminStaff();
            },

            staffGroupTotal(group) {
                return group.fields.reduce((sum, row) => sum + (Number(this.profile[row.field]) || 0), 0);
            },

            beginProfileEdit() {
                this.profileSnapshot = JSON.parse(JSON.stringify(this.profile));
                this.profileEditing = true;
            },

            cancelProfileEdit() {
                if (this.profileSnapshot) this.profile = JSON.parse(JSON.stringify(this.profileSnapshot));
                this.profileEditing = false;
                this.profileSnapshot = null;
            },

            async closeProfile() {
                if (this.profileEditing) {
                    const discard = await window.confirmAction('Discard unsaved hospital profile changes?', { title: 'Unsaved profile changes', confirmLabel: 'Discard', tone: 'danger' });
                    if (!discard) return;
                    this.cancelProfileEdit();
                }
                this.profileOpen = false;
            },

            async saveProfile() {
                this.profileSaving = true;
                try {
                    const payload = { ...this.profile, hospital_contact_id: this.profileContact.id };
                    this.profile = await window.api.post('/api/hospital-profiles', payload);
                    this.profileEditing = false;
                    this.profileSnapshot = null;
                    Alpine.store('toast').success('Hospital profile saved');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                } finally {
                    this.profileSaving = false;
                }
            },

            activeServices() {
                const p = this.profile || {};
                return ['operation_theatre_service', 'emergency_room_service', 'radiology_service', 'public_health_unit_service', 'sterilization_service', 'lab_service_available', 'poct_available', 'launch_boat_service'].filter((f) => p[f]).length;
            },

            formatMetric(value) {
                if (value === null || value === undefined || value === '') return '—';
                return Number(value).toLocaleString();
            },

            profileLocation() {
                const island = this.profileContact?.island;
                if (!island?.name) return 'Location not recorded';
                return island.atoll?.name ? `${island.name} · ${island.atoll.name}` : island.name;
            },

            statusTone(value) {
                const v = String(value || '').trim().toLowerCase();
                if (['adequate', 'good', 'available', 'operational'].some((term) => v.includes(term))) {
                    return { dot: 'bg-emerald-500 ring-emerald-500/15', badge: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' };
                }
                if (['limited', 'low', 'partial', 'fair'].some((term) => v.includes(term))) {
                    return { dot: 'bg-amber-500 ring-amber-500/15', badge: 'bg-amber-500/10 text-amber-700 dark:text-amber-300' };
                }
                if (['critical', 'poor', 'unavailable', 'shortage'].some((term) => v.includes(term))) {
                    return { dot: 'bg-red-500 ring-red-500/15', badge: 'bg-red-500/10 text-red-700 dark:text-red-300' };
                }
                return { dot: 'bg-slate-400 ring-slate-400/15', badge: 'bg-muted text-muted-foreground' };
            },

            statusColor(value) {
                const v = String(value || '').toLowerCase();
                if (['adequate', 'good'].includes(v)) return { dot: 'bg-emerald-500' };
                if (['limited', 'low'].includes(v)) return { dot: 'bg-amber-500' };
                if (['critical', 'poor'].includes(v)) return { dot: 'bg-red-500' };
                return { dot: 'bg-muted' };
            },
        };
    }

window.hospitalsPage = hospitalsPage;
