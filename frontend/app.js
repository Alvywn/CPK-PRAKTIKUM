/**
 * NusaChat - JavaScript Murni Sederhana (Kriteria 1 & 2)
 * Menggunakan fetch() asinkron dengan format JSON & FormData.
 */
(function () {
    'use strict';

    const API_URL = 'http://localhost:8000/api';
    let token = localStorage.getItem('pbp_k2_token') || null;
    let currentUser = null;
    let contacts = [];

    const authSection = document.getElementById('auth-section');
    const appSection = document.getElementById('app-section');
    const alertBox = document.getElementById('alert-box');

    function showAlert(message, isError = false) {
        alertBox.textContent = message;
        alertBox.className = isError ? 'alert alert-error' : 'alert';
        alertBox.classList.remove('hidden');
        setTimeout(() => alertBox.classList.add('hidden'), 4000);
    }

    async function requestApi(endpoint, method = 'GET', body = null) {
        const headers = {};
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        let payload = body;
        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            payload = JSON.stringify(body);
        }

        const res = await fetch(API_URL + endpoint, {
            method: method,
            headers: headers,
            body: payload,
        });

        const json = await res.json();
        return { ok: res.ok && json.success, status: res.status, json: json };
    }

    // 1. LOGIN (Format JSON)
    document.getElementById('login-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const phone = document.getElementById('login-phone').value.trim();
        const password = document.getElementById('login-password').value;

        try {
            const res = await requestApi('/auth.php?action=login', 'POST', {
                phone_number: phone,
                password: password,
            });
            if (!res.ok) {
                showAlert(res.json.message || 'Login gagal.', true);
                return;
            }
            token = res.json.data.token;
            currentUser = res.json.data.user;
            localStorage.setItem('pbp_k2_token', token);
            e.target.reset();
            showApp();
        } catch (err) {
            showAlert('Gagal terhubung ke server: ' + err.message, true);
        }
    });

    // 2. REGISTRASI (Format FormData)
    document.getElementById('register-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('full_name', document.getElementById('reg-name').value.trim());
        formData.append('phone_number', document.getElementById('reg-phone').value.trim());
        formData.append('password', document.getElementById('reg-password').value);

        try {
            const res = await requestApi('/auth.php?action=register', 'POST', formData);
            if (!res.ok) {
                showAlert(res.json.message || 'Registrasi gagal.', true);
                return;
            }
            token = res.json.data.token;
            currentUser = res.json.data.user;
            localStorage.setItem('pbp_k2_token', token);
            e.target.reset();
            showAlert('Akun berhasil didaftarkan!');
            showApp();
        } catch (err) {
            showAlert('Gagal terhubung ke server: ' + err.message, true);
        }
    });

    // 3. LOGOUT & HAPUS AKUN
    function logout() {
        token = null;
        currentUser = null;
        contacts = [];
        localStorage.removeItem('pbp_k2_token');
        appSection.classList.add('hidden');
        authSection.classList.remove('hidden');
    }

    document.getElementById('btn-logout').addEventListener('click', function () {
        logout();
        showAlert('Anda telah keluar.');
    });

    document.getElementById('btn-delete-account').addEventListener('click', async function () {
        if (!confirm('Yakin ingin menghapus akun beserta semua kontak?')) return;
        const res = await requestApi('/auth.php?action=delete_account', 'DELETE');
        if (res.ok) {
            logout();
            showAlert('Akun berhasil dihapus.');
        } else {
            showAlert(res.json.message || 'Gagal menghapus akun.', true);
        }
    });

    // 4. CRUD KONTAK (CREATE, READ, UPDATE, DELETE)
    async function showApp() {
        authSection.classList.add('hidden');
        appSection.classList.remove('hidden');
        document.getElementById('user-name').textContent = currentUser.full_name;
        document.getElementById('user-phone').textContent = '(' + currentUser.phone_number + ')';
        await loadContacts();
    }

    async function loadContacts() {
        const container = document.getElementById('contacts-container');
        try {
            const res = await requestApi('/contacts.php', 'GET');
            if (!res.ok) {
                if (res.status === 401) logout();
                return;
            }
            contacts = res.json.data.contacts || [];
            document.getElementById('contact-count').textContent = contacts.length;

            if (contacts.length === 0) {
                container.innerHTML = '<p class="muted">Belum ada kontak tersimpan.</p>';
                return;
            }

            let rows = contacts.map(c => `
                <tr>
                    <td>${escapeHtml(c.saved_name)}</td>
                    <td>${escapeHtml(c.contact_phone)}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline btn-edit" data-id="${c.id}">Ubah</button>
                            <button type="button" class="btn btn-danger btn-delete" data-id="${c.id}">Hapus</button>
                        </div>
                    </td>
                </tr>
            `).join('');

            container.innerHTML = `
                <table class="contact-table">
                    <thead><tr><th>Nama</th><th>Nomor HP</th><th>Aksi</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            `;

            container.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', () => startEditContact(btn.dataset.id));
            });
            container.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', () => deleteContact(btn.dataset.id));
            });
        } catch (err) {
            container.innerHTML = '<p class="muted">Gagal memuat kontak.</p>';
        }
    }

    const contactForm = document.getElementById('contact-form');
    const contactIdInput = document.getElementById('contact-id');
    const contactNameInput = document.getElementById('contact-name');
    const contactPhoneInput = document.getElementById('contact-phone');
    const cancelEditBtn = document.getElementById('btn-cancel-edit');

    function startEditContact(id) {
        const item = contacts.find(c => String(c.id) === String(id));
        if (!item) return;
        document.getElementById('contact-form-title').textContent = 'Ubah Kontak';
        contactIdInput.value = item.id;
        contactNameInput.value = item.saved_name;
        contactPhoneInput.value = item.contact_phone;
        cancelEditBtn.classList.remove('hidden');
    }

    function resetContactForm() {
        document.getElementById('contact-form-title').textContent = 'Tambah Kontak Baru';
        contactForm.reset();
        contactIdInput.value = '';
        cancelEditBtn.classList.add('hidden');
    }

    cancelEditBtn.addEventListener('click', resetContactForm);

    contactForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = contactIdInput.value.trim();
        const body = {
            id: id,
            saved_name: contactNameInput.value.trim(),
            contact_phone: contactPhoneInput.value.trim(),
        };

        const res = await requestApi('/contacts.php', id ? 'PUT' : 'POST', body);
        if (!res.ok) {
            showAlert(res.json.message || 'Gagal menyimpan kontak.', true);
            return;
        }
        showAlert(res.json.message);
        resetContactForm();
        await loadContacts();
    });

    async function deleteContact(id) {
        if (!confirm('Hapus kontak ini?')) return;
        const res = await requestApi('/contacts.php?id=' + encodeURIComponent(id), 'DELETE');
        if (res.ok) {
            showAlert('Kontak berhasil dihapus.');
            await loadContacts();
        } else {
            showAlert(res.json.message || 'Gagal menghapus kontak.', true);
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    // Cek sesi saat halaman dibuka
    if (token) {
        requestApi('/auth.php?action=me', 'GET')
            .then(res => {
                if (res.ok && res.json.data && res.json.data.user) {
                    currentUser = res.json.data.user;
                    showApp();
                } else {
                    logout();
                }
            })
            .catch(() => logout());
    }
})();
