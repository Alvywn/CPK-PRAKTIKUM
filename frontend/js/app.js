/**
 * ============================================================================
 * NusaChat: Pure Native JavaScript (Kriteria 1 & 2)
 * - Komunikasi Asinkron via fetch() dengan format JSON & FormData
 * - Manajemen Pengguna: Registrasi, Login JWT, Hapus Akun
 * - Manajemen Kontak Dasar: CRUD (Create, Read, Update, Delete)
 * ============================================================================
 */

(function () {
    'use strict';

    function createInitialsAvatarSvg(nameText) {
        const clean = (nameText || 'NC').trim();
        const parts = clean.split(/\s+/);
        let initials = parts[0] ? parts[0].charAt(0).toUpperCase() : 'N';
        if (parts.length > 1 && parts[parts.length - 1]) {
            initials += parts[parts.length - 1].charAt(0).toUpperCase();
        }
        const svg =
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">' +
            '<rect width="80" height="80" rx="10" fill="#e9e0d1"/>' +
            '<text x="50%" y="53%" dominant-baseline="middle" text-anchor="middle" fill="#1c2826" font-family="Georgia, serif" font-weight="bold" font-size="28">' +
            initials.replace(/[^A-Z]/g, 'N') +
            '</text>' +
            '</svg>';
        return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
    }

    const state = {
        theme: localStorage.getItem('pbp_theme') || 'light',
        token: localStorage.getItem('pbp_k2_token') || null,
        user: null,
        contacts: [],
        listStatus: 'idle',
        listErrorMsg: '',
        searchQuery: '',
    };

    // 1. PENGATURAN TEMA TERANG / GELAP
    const themeToggleBtn = document.getElementById('btn-theme-toggle');

    function applyTheme(themeName) {
        const validTheme = themeName === 'dark' ? 'dark' : 'light';
        state.theme = validTheme;
        document.documentElement.setAttribute('data-theme', validTheme);
        localStorage.setItem('pbp_theme', validTheme);

        if (themeToggleBtn) {
            const isDark = validTheme === 'dark';
            themeToggleBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            themeToggleBtn.textContent = isDark ? 'Mode Kertas Terang' : 'Mode Malam';
        }
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            applyTheme(state.theme === 'dark' ? 'light' : 'dark');
        });
    }

    // 2. KONFIGURASI ALAMAT API
    const apiBaseInput = document.getElementById('api-base-url');
    const savedApiBase = localStorage.getItem('pbp_api_base_url');
    if (savedApiBase && apiBaseInput) {
        apiBaseInput.value = savedApiBase;
    }

    function getApiBaseUrl() {
        const val = apiBaseInput ? apiBaseInput.value.trim().replace(/\/+$/, '') : 'http://localhost:8000/api';
        localStorage.setItem('pbp_api_base_url', val);
        return val;
    }

    // 3. KOMUNIKASI ASINKRON FETCH API (JSON / FormData)
    async function apiRequest(endpoint, options = {}) {
        const url = getApiBaseUrl() + endpoint;
        const headers = options.headers || {};

        if (state.token) {
            headers['Authorization'] = 'Bearer ' + state.token;
        }

        let body = options.body || null;
        if (body && !(body instanceof FormData) && typeof body === 'object') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }

        const response = await fetch(url, {
            method: options.method || 'GET',
            headers: headers,
            body: body,
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (_err) {
            throw new Error('Response server bukan JSON valid (HTTP ' + response.status + ').');
        }

        return {
            httpStatus: response.status,
            ok: response.ok && payload.success,
            payload: payload,
        };
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(function () {
            toast.remove();
        }, 4000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function setFieldError(inputEl, errorElId, msg) {
        const errEl = document.getElementById(errorElId);
        if (errEl) {
            errEl.textContent = msg || '';
        }
        if (inputEl) {
            if (msg) {
                inputEl.classList.add('input-invalid');
            } else {
                inputEl.classList.remove('input-invalid');
            }
        }
    }

    // 4. MANAJEMEN PENGGUNA (REGISTER, LOGIN, LOGOUT, DELETE ACCOUNT)
    const authView = document.getElementById('auth-view');
    const appView = document.getElementById('app-view');
    const topbarUserActions = document.getElementById('topbar-user-actions');
    const tabLogin = document.getElementById('tab-login');
    const tabRegister = document.getElementById('tab-register');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    function switchAuthTab(mode) {
        if (mode === 'login') {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        } else {
            tabRegister.classList.add('active');
            tabLogin.classList.remove('active');
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        }
    }

    tabLogin.addEventListener('click', function () {
        switchAuthTab('login');
    });

    tabRegister.addEventListener('click', function () {
        switchAuthTab('register');
    });

    // Registrasi (Mengirim FormData sebagai variasi format data standar)
    registerForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const nameInput = document.getElementById('reg-name');
        const phoneInput = document.getElementById('reg-phone');
        const passInput = document.getElementById('reg-password');

        const fullName = nameInput.value.trim();
        const phone = phoneInput.value.trim();
        const password = passInput.value;

        setFieldError(nameInput, 'err-reg-name', fullName ? '' : 'Nama wajib diisi.');
        setFieldError(phoneInput, 'err-reg-phone', phone ? '' : 'Nomor HP wajib diisi.');
        setFieldError(passInput, 'err-reg-password', password.length >= 6 ? '' : 'Kata sandi minimal 6 karakter.');

        if (!fullName || !phone || password.length < 6) return;

        const formData = new FormData();
        formData.append('full_name', fullName);
        formData.append('phone_number', phone);
        formData.append('password', password);

        try {
            const res = await apiRequest('/auth.php?action=register', {
                method: 'POST',
                body: formData,
            });

            if (!res.ok) {
                showToast(res.payload.message || 'Registrasi gagal.', 'error');
                return;
            }

            state.token = res.payload.data.token;
            state.user = res.payload.data.user;
            localStorage.setItem('pbp_k2_token', state.token);

            registerForm.reset();
            showToast('Akun berhasil dibuat!', 'success');
            enterAppView();
        } catch (err) {
            showToast('Gagal menghubungi server: ' + err.message, 'error');
        }
    });

    // Login (Mengirim format JSON standar)
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const phoneInput = document.getElementById('login-phone');
        const passInput = document.getElementById('login-password');

        const phone = phoneInput.value.trim();
        const password = passInput.value;

        setFieldError(phoneInput, 'err-login-phone', phone ? '' : 'Nomor HP wajib diisi.');
        setFieldError(passInput, 'err-login-password', password ? '' : 'Kata sandi wajib diisi.');

        if (!phone || !password) return;

        try {
            const res = await apiRequest('/auth.php?action=login', {
                method: 'POST',
                body: { phone_number: phone, password: password },
            });

            if (!res.ok) {
                showToast(res.payload.message || 'Login gagal.', 'error');
                return;
            }

            state.token = res.payload.data.token;
            state.user = res.payload.data.user;
            localStorage.setItem('pbp_k2_token', state.token);

            loginForm.reset();
            showToast('Halo, ' + state.user.full_name + '!', 'success');
            enterAppView();
        } catch (err) {
            showToast('Gagal menghubungi server: ' + err.message, 'error');
        }
    });

    function logoutUser() {
        state.token = null;
        state.user = null;
        state.contacts = [];
        localStorage.removeItem('pbp_k2_token');

        document.getElementById('account-modal').classList.add('hidden');
        document.getElementById('contact-modal').classList.add('hidden');
        topbarUserActions.classList.add('hidden');
        appView.classList.add('hidden');
        authView.classList.remove('hidden');
    }

    // 5. MANAJEMEN KONTAK CRUD
    async function enterAppView() {
        authView.classList.add('hidden');
        appView.classList.remove('hidden');
        topbarUserActions.classList.remove('hidden');

        document.getElementById('current-user-name').textContent = state.user.full_name;
        document.getElementById('current-user-phone').textContent = state.user.phone_number;
        document.getElementById('current-user-avatar').src = createInitialsAvatarSvg(state.user.full_name);

        await loadContacts();
    }

    async function loadContacts() {
        state.listStatus = 'loading';
        renderContacts();

        try {
            const res = await apiRequest('/contacts.php', { method: 'GET' });
            if (!res.ok) {
                if (res.httpStatus === 401) {
                    logoutUser();
                    return;
                }
                state.listStatus = 'error';
                state.listErrorMsg = res.payload.message || 'Gagal memuat kontak.';
                renderContacts();
                return;
            }

            state.contacts = res.payload.data.contacts || [];
            state.listStatus = 'ready';
            document.getElementById('contact-count').textContent = String(state.contacts.length);
            renderContacts();
        } catch (err) {
            state.listStatus = 'error';
            state.listErrorMsg = 'Server tidak merespons (' + err.message + ').';
            renderContacts();
        }
    }

    function renderContacts() {
        const container = document.getElementById('contacts-list-container');
        if (!container) return;

        if (state.listStatus === 'loading') {
            container.innerHTML = '<p class="state-notice">Lagi memuat daftar kontak...</p>';
            return;
        }

        if (state.listStatus === 'error') {
            container.innerHTML =
                '<div class="state-card">' +
                '<p><strong>Daftar kontak belum bisa dimuat.</strong></p>' +
                '<p>' +
                escapeHtml(state.listErrorMsg) +
                '</p>' +
                '<button type="button" id="btn-retry-contacts" class="btn btn-outline">Coba Muat Ulang</button>' +
                '</div>';
            const retryBtn = document.getElementById('btn-retry-contacts');
            if (retryBtn) {
                retryBtn.addEventListener('click', loadContacts);
            }
            return;
        }

        const q = state.searchQuery.toLowerCase();
        const filtered = state.contacts.filter(function (c) {
            return (
                (c.saved_name || '').toLowerCase().includes(q) ||
                (c.contact_phone || '').toLowerCase().includes(q)
            );
        });

        if (filtered.length === 0) {
            container.innerHTML =
                '<div class="state-card">' +
                '<p>Belum ada kontak tersimpan. Klik tombol <strong>+ Tambah Kontak</strong> di atas untuk mulai menyimpan nomor teman.</p>' +
                '</div>';
            return;
        }

        const cardsHtml = filtered.map(function (c) {
            return (
                '<li class="contact-card">' +
                '<div class="contact-card-top">' +
                '<img class="avatar" src="' +
                createInitialsAvatarSvg(c.saved_name) +
                '" width="48" height="48" alt="Avatar ' +
                escapeHtml(c.saved_name) +
                '">' +
                '<div>' +
                '<div class="contact-card-name">' +
                escapeHtml(c.saved_name) +
                '</div>' +
                '<div class="contact-card-phone">' +
                escapeHtml(c.contact_phone) +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="contact-card-actions">' +
                '<button type="button" class="btn btn-outline btn-edit-contact" data-id="' +
                escapeHtml(c.id) +
                '">Ubah</button>' +
                '<button type="button" class="btn btn-danger-outline btn-delete-contact" data-id="' +
                escapeHtml(c.id) +
                '">Hapus</button>' +
                '</div>' +
                '</li>'
            );
        });

        container.innerHTML = '<ul class="contacts-list">' + cardsHtml.join('') + '</ul>';

        container.querySelectorAll('.btn-edit-contact').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.getAttribute('data-id');
                const found = state.contacts.find(function (c) {
                    return String(c.id) === String(id);
                });
                if (found) openContactModal(found);
            });
        });

        container.querySelectorAll('.btn-delete-contact').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.getAttribute('data-id');
                handleDeleteContact(id);
            });
        });
    }

    document.getElementById('search-contact-input').addEventListener('input', function (e) {
        state.searchQuery = e.target.value || '';
        renderContacts();
    });

    document.getElementById('btn-refresh-contacts').addEventListener('click', loadContacts);

    // MODAL TAMBAH / UBAH KONTAK
    const contactModal = document.getElementById('contact-modal');
    const contactForm = document.getElementById('contact-form');
    const contactIdInput = document.getElementById('contact-id-input');
    const contactNameInput = document.getElementById('contact-name-input');
    const contactPhoneInput = document.getElementById('contact-phone-input');

    function openContactModal(contactToEdit = null) {
        setFieldError(contactNameInput, 'err-contact-name', '');
        setFieldError(contactPhoneInput, 'err-contact-phone', '');

        if (contactToEdit) {
            document.getElementById('contact-modal-title').textContent = 'Ubah Kontak';
            contactIdInput.value = contactToEdit.id;
            contactNameInput.value = contactToEdit.saved_name;
            contactPhoneInput.value = contactToEdit.contact_phone;
        } else {
            document.getElementById('contact-modal-title').textContent = 'Tambah Kontak Baru';
            contactForm.reset();
            contactIdInput.value = '';
        }

        contactModal.classList.remove('hidden');
        contactNameInput.focus();
    }

    function closeContactModal() {
        contactModal.classList.add('hidden');
    }

    document.getElementById('btn-open-add-contact').addEventListener('click', function () {
        openContactModal(null);
    });
    document.getElementById('btn-close-contact-modal').addEventListener('click', closeContactModal);
    document.getElementById('btn-cancel-contact-modal').addEventListener('click', closeContactModal);

    contactForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const id = contactIdInput.value.trim();
        const savedName = contactNameInput.value.trim();
        const contactPhone = contactPhoneInput.value.trim();

        setFieldError(contactNameInput, 'err-contact-name', savedName ? '' : 'Nama kontak wajib diisi.');
        setFieldError(contactPhoneInput, 'err-contact-phone', contactPhone ? '' : 'Nomor HP kontak wajib diisi.');

        if (!savedName || !contactPhone) return;

        try {
            const isEdit = id !== '';
            const res = await apiRequest('/contacts.php', {
                method: isEdit ? 'PUT' : 'POST',
                body: { id: id, saved_name: savedName, contact_phone: contactPhone },
            });

            if (!res.ok) {
                showToast(res.payload.message || 'Gagal menyimpan kontak.', 'error');
                return;
            }

            closeContactModal();
            showToast(res.payload.message, 'success');
            await loadContacts();
        } catch (err) {
            showToast('Kendala jaringan: ' + err.message, 'error');
        }
    });

    async function handleDeleteContact(contactId) {
        if (!window.confirm('Yakin ingin menghapus kontak ini?')) return;

        try {
            const res = await apiRequest('/contacts.php?id=' + encodeURIComponent(contactId), {
                method: 'DELETE',
            });

            if (!res.ok) {
                showToast(res.payload.message || 'Gagal menghapus kontak.', 'error');
                return;
            }

            showToast('Kontak berhasil dihapus.', 'success');
            await loadContacts();
        } catch (err) {
            showToast('Kendala jaringan: ' + err.message, 'error');
        }
    }

    // MODAL PENGATURAN AKUN & HAPUS AKUN
    const accountModal = document.getElementById('account-modal');

    function closeAccountModal() {
        accountModal.classList.add('hidden');
    }

    document.getElementById('btn-open-account-modal').addEventListener('click', function () {
        if (!state.user) return;
        document.getElementById('modal-user-name').textContent = state.user.full_name;
        document.getElementById('modal-user-phone').textContent = state.user.phone_number;
        accountModal.classList.remove('hidden');
    });

    document.getElementById('btn-close-account-modal').addEventListener('click', closeAccountModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (!contactModal.classList.contains('hidden')) closeContactModal();
            if (!accountModal.classList.contains('hidden')) closeAccountModal();
        }
    });

    document.getElementById('btn-logout').addEventListener('click', function () {
        logoutUser();
        showToast('Kamu sudah keluar dari akun.', 'info');
    });

    document.getElementById('btn-delete-account').addEventListener('click', async function () {
        if (!window.confirm('Hapus akun secara permanen beserta seluruh kontakmu?')) return;

        try {
            const res = await apiRequest('/auth.php?action=delete_account', {
                method: 'DELETE',
            });

            if (!res.ok) {
                showToast(res.payload.message || 'Gagal menghapus akun.', 'error');
                return;
            }

            logoutUser();
            showToast('Akun berhasil dihapus secara permanen.', 'success');
        } catch (err) {
            showToast('Gagal menghapus akun: ' + err.message, 'error');
        }
    });

    async function bootstrap() {
        applyTheme(state.theme);

        if (state.token) {
            try {
                const res = await apiRequest('/auth.php?action=me', { method: 'GET' });
                if (res.ok && res.payload.data && res.payload.data.user) {
                    state.user = res.payload.data.user;
                    enterAppView();
                    return;
                }
            } catch (_e) {
                // Token tidak valid
            }
            logoutUser();
        }
    }

    bootstrap();
})();
