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
            avatarUploading: false,

            init() {},

            async uploadAvatar(event) {
                const file = event.target.files?.[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    Alpine.store('toast').error('Please select an image file');
                    event.target.value = '';
                    return;
                }

                this.avatarUploading = true;
                try {
                    const base64 = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(String(reader.result).split(',')[1]);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                    const data = await window.api.post('/api/upload', { file: base64, filename: file.name });
                    this.profileForm.avatar_url = data.url;
                    Alpine.store('toast').success('Avatar uploaded. Save your profile to apply it.');
                } catch (e) {
                    Alpine.store('toast').error(e.message || 'Avatar upload failed');
                } finally {
                    this.avatarUploading = false;
                    event.target.value = '';
                }
            },

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
