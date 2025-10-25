document.addEventListener('DOMContentLoaded', () => {
  loadHRDSession();
  loadAll();
  setupMenu();
  markActiveMenu();
});

async function loadHRDSession() {
  try {
    const res = await fetch('api/get_hrd_session.php');
    const data = await res.json();
    if (data.success) {
      let jabatan = data.jabatan || 'Kepala HRD';
      const el = document.getElementById('hrd-name');
      if (el) {
        el.innerHTML = `
          <div style="text-align:right">
            <div class="font-semibold">${data.nama}</div>
            <div class="small text-gray-500">${jabatan}</div>
          </div>
          <div style="width:56px;">
            <img src="assets/user.png" style="width:56px;height:56px;border-radius:50%;object-fit:cover;" alt="user">
          </div>
        `;
      }
    } else {
      window.location.href = 'login.php';
    }
  } catch (err) {
    console.error(err);
  }
}

async function loadAll() {
  await loadStats();
  await loadHourlyChart();
  await loadConsistencyChart();
  await loadOvertimeChart();
  await loadOvertimeTrendChart(); 
}

async function loadStats() {
  try {
    const res = await fetch('api/hrd_get_stats.php');
    const j = await res.json();
    if (!j.success) return;
    const d = j.data;
    const top5 = d.top5 || [];
    const cards = document.getElementById('stat-cards');

    cards.innerHTML = `
      <!-- Jumlah Karyawan -->
      <div class="bg-white rounded-xl shadow p-5 stat-big text-center">
        <h3 class="text-sm text-gray-500">Jumlah Karyawan</h3>
        <div class="value text-[#69c7d9] font-bold text-2xl">${d.total_employees}</div>
      </div>

      <!-- Rata-rata Jam Masuk -->
      <div class="bg-white rounded-xl shadow p-5 stat-big text-center">
        <h3 class="text-sm text-gray-500">Rata-rata Jam Masuk</h3>
        <div class="value text-[#69c7d9] font-bold text-2xl">${d.avg_arrival_time || '-'}</div>
      </div>

      <!-- Top 5 Karyawan Paling Rajin -->
      <div class="bg-white rounded-xl shadow p-5 stat-big">
        <h3 class="text-sm text-gray-500 mb-2 text-center">Top 5 Karyawan Paling Rajin</h3>
        <div class="flex flex-col items-center text-[#69c7d9] font-semibold text-sm space-y-1">
          ${top5.length
        ? top5
          .map(
            t => `<div>${t.nama} <span class="font-normal text-[#69c7d9]">(${t.total_hadir}x)</span></div>`
          )
          .join('')
        : '<div class="text-gray-400">Tidak ada</div>'
      }
        </div>
      </div>

      <!-- Karyawan Sering Absen -->
      <div class="bg-white rounded-xl shadow p-5 stat-big text-center">
        <h3 class="text-sm text-gray-500">Sering Absen (≥3x)</h3>
        <div class="value text-[#69c7d9] font-bold text-lg leading-snug">
          ${(d.frequent_absent && d.frequent_absent.length)
        ? d.frequent_absent.map(nm => `<div class="text-base">${nm}</div>`).join('')
        : 'Tidak ada'
      }
        </div>
      </div>

      <!-- Karyawan Sering Lembur -->
      <div class="bg-white rounded-xl shadow p-5 stat-big text-center">
        <h3 class="text-sm text-gray-500">Sering Lembur</h3>
        <div class="value text-[#69c7d9] font-bold text-lg leading-snug">
          ${d.most_overtime
        ? `<div class="flex justify-center items-center gap-2">
            <span class="text-base">${d.most_overtime.nama}</span>
            <span class="font-semibold text-[#69c7d9] text-base">${d.most_overtime.total_hari}x</span>
            </div>`
        : 'Tidak ada'
      }
        </div>
      </div>

    `;
  } catch (err) {
    console.error(err);
  }
}


let hourlyChart = null, consistencyChart = null, overtimeChart = null, overtimeTrendChart = null;

async function loadHourlyChart() {
  try {
    const res = await fetch('api/hrd_get_trends.php');
    const j = await res.json();
    if (!j.success) return;

    const labels = j.data.map(r => r.jam_masuk);
    const values = j.data.map(r => parseInt(r.jumlah));
    const namesPer = j.data.map(r => r.names ? r.names.split('||') : []);

    const ctx = document.getElementById('chart-trend').getContext('2d');
    if (hourlyChart) hourlyChart.destroy();
    hourlyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Jumlah Karyawan Masuk per Jam (HH:MM)',
          data: values,
          backgroundColor: '#69c7d9'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              title: (it) => `Jam Masuk: ${it[0].label}`,
              label: () => null, 
              afterBody: (ctx) => {
                const idx = ctx[0].dataIndex;
                const names = namesPer[idx] || [];
                if (names.length === 0) return ['(tidak ada)'];

                const daftar = names.map(nm => `- ${nm}`);
                return [
                  'Daftar Karyawan Masuk:',
                  ...daftar
                ];
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });

  } catch (err) {
    console.error('Error loading hourly chart:', err);
  }
}


async function loadOvertimeTrendChart() {
  try {
    const res = await fetch('api/hrd_get_overtime_trend.php');
    const j = await res.json();
    if (!j.success) return;

    const labels = j.data.map(r => r.jam_keluar);
    const values = j.data.map(r => parseInt(r.jumlah));
    const namesPer = j.data.map(r => r.names ? r.names.split('||') : []);

    // Menghitung selisih jam keluar - 16:00
    function hitungLembur(jamKeluar) {
      const [h, m] = jamKeluar.split(':').map(Number);
      const jamKeluarMenit = h * 60 + m;
      const jamNormalMenit = 16 * 60; 
      const durasi = jamKeluarMenit - jamNormalMenit;
      if (durasi <= 0) return '0j 0m';
      const jam = Math.floor(durasi / 60);
      const menit = durasi % 60;
      return `${jam}j ${menit}m`;
    }

    const ctx = document.getElementById('chart-overtime-trend').getContext('2d');
    if (overtimeTrendChart) overtimeTrendChart.destroy();
    overtimeTrendChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Jumlah Karyawan (jam keluar > 16:00)',
          data: values,
          backgroundColor: '#f59e0b'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              title: (it) => `Jam Keluar: ${it[0].label}`,
              label: () => null, 
              afterBody: (ctx) => {
                const idx = ctx[0].dataIndex;
                const names = namesPer[idx] || [];
                if (names.length === 0) return ['(tidak ada)'];

                const daftar = names.map(nm => `- ${nm}`);
                return [
                  'Daftar:',
                  ...daftar
                ];
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });

  } catch (err) {
    console.error(err);
  }
}

async function loadConsistencyChart() {
  try {
    const res = await fetch('api/hrd_get_consistency.php');
    const j = await res.json();
    if (!j.success) return;

    const labels = j.data.labels;
    const tepat = j.data.tepat;
    const telat = j.data.telat;

    const tbody = document.getElementById('tbody-consistency');
    tbody.innerHTML = '';

    for (let i = 0; i < labels.length; i++) {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-gray-50';
      tr.innerHTML = `
        <td class="px-4 py-2 border-b">${i + 1}</td>
        <td class="px-4 py-2 border-b">${labels[i]}</td>
        <td class="px-4 py-2 border-b text-center text-green-600 font-medium">${tepat[i]}</td>
        <td class="px-4 py-2 border-b text-center text-red-500 font-medium">${telat[i]}</td>
      `;
      tbody.appendChild(tr);
    }

    // Fitur pencarian
    const searchInput = document.getElementById('search-consistency');
    searchInput.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      const rows = tbody.querySelectorAll('tr');
      rows.forEach(r => {
        const nama = r.children[1]?.textContent.toLowerCase() || '';
        r.style.display = nama.includes(q) ? '' : 'none';
      });
    });

  } catch (err) {
    console.error('Error loading consistency table:', err);
    const tbody = document.getElementById('tbody-consistency');
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Gagal memuat data</td></tr>`;
  }
}

async function loadOvertimeChart() {
  try {
    const res = await fetch('api/hrd_get_overtime_chart.php');
    const j = await res.json();
    if (!j.success) return;

    const limit = j.monthly_limit || 40;
    let underCount = 0, overCount = 0;
    j.data.forEach(k => { if (k.ot > limit) overCount++; else if (k.ot > 0) underCount++; });

    const namesUnder = j.data.filter(k => k.ot > 0 && k.ot <= limit).map(k => `${k.nama} (${k.ot}h)`);
    const namesOver = j.data.filter(k => k.ot > limit).map(k => `${k.nama} (${k.ot}h)`);

    const ctx = document.getElementById('chart-overtime').getContext('2d');
    if (overtimeChart) overtimeChart.destroy();
    overtimeChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Under Limit', 'Over Limit'],
        datasets: [{ label: `Karyawan (limit ${limit} jam/bulan)`, data: [underCount, overCount], backgroundColor: ['#69c7d9', '#f87171'] }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              afterBody: (ctx) => {
                const idx = ctx[0].dataIndex;
                return idx === 0 ? ['Daftar Under:'].concat(namesUnder.length ? namesUnder : ['(tidak ada)']) : ['Daftar Over:'].concat(namesOver.length ? namesOver : ['(tidak ada)']);
              }
            }
          },
          legend: { display: false }
        },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });

  } catch (err) { console.error(err); }
}

function setupMenu() {
  document.getElementById('menu-izin').addEventListener('click', () => window.location.href = 'izin.html');
  document.getElementById('menu-employees').addEventListener('click', () => window.location.href = 'employees.html');
  document.getElementById('menu-dashboard').addEventListener('click', () => window.location.href = 'dashboard.html');
  document.querySelectorAll('.menu-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
  document.getElementById('menu-logout').addEventListener('click', async () => {
    await fetch('api/hrd_logout.php');
    window.location.href = 'login.php';
  });
}

function markActiveMenu() {
  const path = location.pathname.split('/').pop();
  const mapping = {
    'dashboard.html': 'menu-dashboard',
    'employees.html': 'menu-employees',
    'izin.html': 'menu-izin',
    'absensi.html': 'menu-absensi'
  };
  Object.values(mapping).forEach(id => { document.getElementById(id)?.classList.remove('active'); });
  const id = mapping[path] || 'menu-dashboard';
  document.getElementById(id)?.classList.add('active');
}
