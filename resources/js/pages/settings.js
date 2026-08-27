function settingsPage(props = {}) {
        return {
            profileForm: {
                first_name: props.first_name || "",
                last_name: props.last_name || "",
                contact_no: props.contact_no || "",
                designation: props.designation || "",
                avatar_url: props.avatar_url || "",
            },
            passwordForm: {
                current_password: '',
                password: '',
                password_confirmation: '',
            },

            init() {},

            async saveProfile() {
                try {
                    await window.api.patch('/api/profile', this.profileForm);
                    Alpine.store('toast').success('Profile updated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async changePassword() {
                if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
                    Alpine.store('toast').error('Passwords do not match');
                    return;
                }
                try {
                    await window.api.post('/api/change-password', this.passwordForm);
                    this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
                    Alpine.store('toast').success('Password updated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.settingsPage = settingsPage;
