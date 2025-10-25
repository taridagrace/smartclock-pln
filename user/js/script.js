// Helper: waktu / tanggal indonesia (24h)
function timeNowFull() {
  const d = new Date();
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}
function timeNowHM() {
  const d = new Date();
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
}
function dateNowLong() {
  const d = new Date();
  return d.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
}

// DOM ready
document.addEventListener('DOMContentLoaded', () => {
  // --- LOGIN ---
  const loginBtn = document.getElementById('loginBtn');
  if (loginBtn) {
    loginBtn.addEventListener('click', async () => {
      const email = document.getElementById('email').value.trim();
      const pass = document.getElementById('password').value.trim();
      const loginError = document.getElementById('loginError');

      const formData = new FormData();
      formData.append('email', email);
      formData.append('password', pass);

      const res = await fetch('login.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        const user = data.data;
        localStorage.setItem('sc_user', JSON.stringify(user));
        localStorage.setItem('sc_name', user.nama);
        localStorage.setItem('sc_jabatan', user.jabatan);
        localStorage.setItem('sc_email', user.email);
        window.location.href = 'dashboard.html';
      } else {
        loginError.classList.remove('d-none');
        loginError.innerText = data.message;
      }
    });
  }

  // --- DASHBOARD HEADER TIME & DATE (realtime) ---
  const timeHeader = document.getElementById('timeHeader');
  const dateHeader = document.getElementById('dateHeader');
  if (timeHeader && dateHeader) {
    function updateHeader() {
      timeHeader.innerText = timeNowHM();
      dateHeader.innerText = dateNowLong();
    }
    updateHeader();
    setInterval(updateHeader, 1000);
  }

  // greeting on dashboard/profile
  const greeting = document.getElementById('greeting');
  if (greeting) {
    const name = localStorage.getItem('sc_name') || 'Karyawan';
    greeting.innerText = `Selamat Pagi, ${name} 👋`;
  }
  const userProfile = JSON.parse(localStorage.getItem('sc_user') || '{}');
  if (document.getElementById('profileName')) {
    document.getElementById('profileName').innerText = userProfile.nama || 'Tidak Dikenal';
    document.getElementById('profileRole').innerText = userProfile.jabatan || '-';
    document.getElementById('profileEmail').innerText = `Email: ${userProfile.email || '-'}`;
  }

  // location simulation (one-shot)
  if (!localStorage.getItem('sc_location_sim')) {
    const r = Math.random();
    localStorage.setItem('sc_location_sim', r < 0.85 ? 'inside' : 'outside');
  }
  const loc = localStorage.getItem('sc_location_sim');
  const locationStatusEls = document.querySelectorAll('#locationStatus, #locationTop, #clockLocation');
  locationStatusEls.forEach(el => {
    if (!el) return;
    el.innerText = (loc === 'inside') ? '📍 Anda berada di dalam kawasan kantor' : '❌ Di luar area kantor';
  });

  // today status (dashboard)
  const todayStatus = document.getElementById('todayStatus');
  const reminderBanner = document.getElementById('reminderBanner');
  if (todayStatus) {
    const t = localStorage.getItem('sc_clock_in');
    if (t) {
      todayStatus.innerText = `Sudah Clock In pukul ${t}`;
      if (reminderBanner) reminderBanner.classList.add('d-none');
    } else {
      todayStatus.innerText = 'Belum Clock In';
      if (reminderBanner) reminderBanner.classList.remove('d-none');
    }
  }

  // --- HOME clock buttons ---
  const homeClockIn = document.getElementById('homeClockIn');
  const homeClockOut = document.getElementById('homeClockOut');
  const user = JSON.parse(localStorage.getItem('sc_user') || '{}');
  if (homeClockIn) {
    homeClockIn.addEventListener('click', async () => {
      const formData = new FormData();
      formData.append('action', 'clock_in');
      formData.append('user_id', user.id);

      try {
        const res = await fetch('absensi.php', { method: 'POST', body: formData });
        const data = await res.json();

        alert(data.message);

        if (data.success) {
          const absenMasukEl = document.getElementById('absenMasuk');
          const now = new Date();
          const timeString = now.toLocaleTimeString('id-ID', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
          });

          if (absenMasukEl) {
            absenMasukEl.innerText = timeString;
            absenMasukEl.classList.remove('text-muted');
            absenMasukEl.classList.add('text-success');
          }
        }
      } catch (err) {
        console.error('Gagal clock in:', err);
        alert('Terjadi kesalahan saat Clock In.');
      }
    });
  }

  if (homeClockOut) {
    homeClockOut.addEventListener('click', async () => {
      const formData = new FormData();
      formData.append('action', 'clock_out');
      formData.append('user_id', user.id);

      const res = await fetch('absensi.php', { method: 'POST', body: formData });
      const data = await res.json();

      alert(data.message);

      if (data.success) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
          hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
        });
        document.getElementById('absenKeluar').innerText = timeString;
        document.getElementById('absenKeluar').classList.remove('text-muted');
        document.getElementById('absenKeluar').classList.add('text-success');
      }
    });
  }

  // --- CLOCK page large clock ---
  const clockLarge = document.getElementById('clockLarge');
  const dateLarge = document.getElementById('dateLarge');
  if (clockLarge) {
    function updateClockLarge() {
      clockLarge.innerText = timeNowFull();
      if (dateLarge) dateLarge.innerText = dateNowLong();
    }
    updateClockLarge();
    setInterval(updateClockLarge, 1000);
  }

  // take photo simulation (modal)
  const takePhotoBtn = document.getElementById('takePhotoBtn');
  if (takePhotoBtn) {
    takePhotoBtn.addEventListener('click', () => {
      const photoModalEl = document.getElementById('photoModal');
      if (photoModalEl) {
        const modal = new bootstrap.Modal(photoModalEl);
        modal.show();
      } else alert('Photo simulated');
    });
  }

  // --- RIWAYAT calendar generation (from DB) ---
  const calendarGrid = document.getElementById('calendarGrid');
  if (calendarGrid) {
    const now = new Date();
    const month = now.getMonth() + 1;
    const year = now.getFullYear();
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    document.getElementById('monthTitle').innerText = `${monthNames[month - 1]} ${year}`;

    const user = JSON.parse(localStorage.getItem('sc_user') || '{}');
    if (!user.id) {
      alert('User belum login');
      return;
    }

    fetch(`get_riwayat.php?user_id=${user.id}&month=${month}&year=${year}`)
      .then(res => res.json())
      .then(result => {
        const absensiData = result.data || {};
        const daysInMonth = new Date(year, month, 0).getDate();
        calendarGrid.innerHTML = '';

        for (let d = 1; d <= daysInMonth; d++) {
          const cell = document.createElement('div');
          cell.className = 'calendar-cell';
          const rec = absensiData[d];

          // --- Tambahan: tandai weekend secara otomatis ---
          const weekendDates = [4, 5, 11, 12, 18, 19, 25, 26];
          let isWeekend = weekendDates.includes(d);

          let status = isWeekend ? 'Weekend' : 'Absen';
          let badge = isWeekend ? 'bg-weekend' : 'bg-danger';
          let jamMasuk = '--:--:--', jamKeluar = '--:--:--';

          if (!isWeekend && rec) {
            status = rec.status || 'Hadir';
            jamMasuk = rec.jam_masuk || '--:--:--';
            jamKeluar = rec.jam_keluar || '--:--:--';

            if (status.toLowerCase() === 'hadir') badge = 'status-hadir';
            else if (status.toLowerCase() === 'terlambat' || status.toLowerCase() === 'telat') badge = 'status-telat';
            else if (status.toLowerCase() === 'izin') badge = 'status-izin';
            else if (status.toLowerCase() === 'absen' || status.toLowerCase() === 'alfa') badge = 'status-absen';
          }

          // tambahkan class weekend
          if (isWeekend) cell.classList.add('weekend');

          cell.innerHTML = `
            <div class="small text-muted">${d}</div>
            <div><span class="badge ${badge}">&nbsp;&nbsp;&nbsp;</span></div>
            <div class="small text-muted mt-2">${status}</div>
          `;

          if (!isWeekend) {
            cell.style.cursor = 'pointer';
            cell.addEventListener('click', () => {
              document.getElementById('detailDate').innerText = `${d} ${monthNames[month - 1]} ${year}`;
              document.getElementById('detailStatus').innerHTML = `
                <strong>Status:</strong> ${status}<br>
                <strong>Jam Masuk:</strong> ${jamMasuk}<br>
                <strong>Jam Keluar:</strong> ${jamKeluar}
              `;
              document.getElementById('detailNote').innerText = rec ? '' : 'Tidak ada catatan absensi untuk tanggal ini.';
              const detModal = new bootstrap.Modal(document.getElementById('detailModal'));
              detModal.show();
            });
          }

          calendarGrid.appendChild(cell);
        }
      })
      .catch(err => console.error('Gagal ambil data riwayat:', err));
  }

  // --- IZIN submit ---
  const submitIzin = document.getElementById('submitIzin');
  if (submitIzin) {
    submitIzin.addEventListener('click', async () => {
      const user = JSON.parse(localStorage.getItem('sc_user') || '{}');
      const jenis = document.getElementById('jenisIzin').value;
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;
      const alasan = document.getElementById('alasan').value.trim();
      const statusEl = document.getElementById('izinStatus');

      if (!user.id || !jenis || !start || !end || !alasan) {
        statusEl.innerText = 'Mohon isi semua field.';
        return;
      }

      const formData = new FormData();
      formData.append('id', user.id);
      formData.append('type', jenis);
      formData.append('reason', alasan);
      formData.append('start_date', start);
      formData.append('end_date', end);

      const res = await fetch('izin_submit.php', { method: 'POST', body: formData });
      const data = await res.json();

      statusEl.innerText = data.message;
      if (data.success) {
        document.getElementById('alasan').value = '';
        document.getElementById('uploadFile').value = '';
      }
    });
  }

  // --- PROFILE info ---
  const attCount = document.getElementById('attCount');
  if (attCount) {
    const stats = JSON.parse(localStorage.getItem('sc_statuses') || '{}');
    let hadir = 0;
    for (let k in stats) if (stats[k] === 'hadir') hadir++;
    attCount.innerText = hadir;
  }

  const profileName = document.getElementById('profileName');
  if (profileName) {
    const user = JSON.parse(localStorage.getItem('sc_user') || '{}');
    if (!user.id) {
      alert('User belum login');
      window.location.href = 'index.html';
    } else {
      fetch(`get_profile.php?id=${user.id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('profileName').innerText = data.data.nama;
            document.getElementById('profileRole').innerText = data.data.jabatan;
            document.getElementById('profileEmail').innerText = data.data.email;
            document.getElementById('attCount').innerText = data.data.total_kehadiran;
            document.getElementById('attIzin').innerText = data.data.total_izin;
            document.getElementById('attAlpha').innerText = data.data.total_alpha;
          } else {
            alert(data.message);
          }
        })
        .catch(err => console.error('Gagal ambil profil:', err));
    }
  }

  // --- LOGOUT ---
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      localStorage.clear();
      window.location.href = 'index.html';
    });
  }
});
