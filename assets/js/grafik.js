// grafik.js — Multi-chart dinamis per field dari JSON
const ChartManager = (() => {
  const charts = {};
  const API_URL = window.API_CHART_URL || 'functions/get_chart_data.php';

  // --- Ambil data JSON ---
  async function fetchData(period) {
    const url = `${API_URL}?period=${encodeURIComponent(period)}`;
    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('Response bukan JSON:\n', text);
      throw new Error('Invalid JSON dari server');
    }
  }

  // --- Buat container chart ---
  function createChartContainer(container, key, label) {
    const col = document.createElement('div');
    col.className = 'col-md-6';
    col.innerHTML = `
      <div class="card-custom">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5>${label}</h5>
          <select class="period-select" data-key="${key}">
            <option value="hourly">24 Jam Terakhir</option>
            <option value="daily">7 Hari Terakhir</option>
          </select>
        </div>
        <canvas id="chart_${key}"></canvas>
      </div>
    `;
    container.appendChild(col);
  }

  // --- Render chart tunggal ---
  function renderChart(canvasId, label, labels, values, color) {
    if (charts[canvasId]) charts[canvasId].destroy();

    const ctx = document.getElementById(canvasId).getContext('2d');
    charts[canvasId] = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label,
          data: values,
          borderColor: color,
          backgroundColor: color.replace('0.8', '0.1'),
          fill: true,
          tension: 0.3,
          borderWidth: 2,
          pointRadius: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2,
        plugins: {
          legend: { display: false },
          title: { display: true, text: label, font: { size: 14 } }
        },
        scales: {
          y: { beginAtZero: false },
          x: { ticks: { maxRotation: 45, minRotation: 45 } }
        }
      }
    });
  }

  // --- Cek apakah dataset boolean / tidak valid ---
  function isBooleanDataset(values) {
    // Hilangkan undefined, tapi tetap deteksi kalau semua null
    const nonNull = values.filter(v => v !== undefined);
    // Semua null → dianggap boolean
    if (nonNull.length === 0 || nonNull.every(v => v === null)) return true;
    // Semua boolean-like → dianggap boolean
    return nonNull.every(v =>
      v === 0 || v === 1 ||
      v === true || v === false ||
      String(v).toLowerCase() === 'true' ||
      String(v).toLowerCase() === 'false'
    );
  }

  // --- Init utama ---
  async function initialize() {
    const container = document.querySelector('#chartContainer');
    container.innerHTML = '<p class="text-muted">Memuat grafik...</p>';

    try {
      const data = await fetchData('hourly');
      container.innerHTML = '';

      const { labels, datasets } = data;
      const colors = [
        'rgba(32,107,196,0.8)','rgba(220,53,69,0.8)',
        'rgba(40,167,69,0.8)','rgba(255,123,0,0.8)',
        'rgba(111,66,193,0.8)','rgba(255,193,7,0.8)',
        'rgba(23,162,184,0.8)','rgba(255,99,132,0.8)'
      ];
      let i = 0;

      for (const key in datasets) {
        const label = datasets[key].label;
        const values = datasets[key].values;

        // ✅ Cek dulu apakah boolean/null → lewati
        if (isBooleanDataset(values)) {
          console.log(`Lewati grafik untuk ${key} (boolean dataset)`);
          continue;
        }

        // ✅ Baru buat card dan chart kalau datanya numerik
        const color = colors[i++ % colors.length];
        createChartContainer(container, key, label);
        renderChart(`chart_${key}`, label, labels, values, color);
      }

      // Jika semua data boolean/null
      if (container.innerHTML.trim() === '') {
        container.innerHTML = `
          <p class="text-muted text-center">
            Tidak ada data numerik untuk ditampilkan.
          </p>`;
      }

      // Event listener untuk ubah periode
      container.querySelectorAll('.period-select').forEach(select => {
        select.addEventListener('change', async function() {
          const period = this.value;
          const key = this.dataset.key;
          const fresh = await fetchData(period);
          if (!fresh.datasets[key]) return;

          const dset = fresh.datasets[key];
          if (isBooleanDataset(dset.values)) {
            console.log(`Lewati update grafik untuk ${key} (boolean dataset)`);
            return;
          }

          const color = colors[i++ % colors.length];
          renderChart(`chart_${key}`, dset.label, fresh.labels, dset.values, color);
        });
      });
    } catch (e) {
      container.innerHTML = `<p class="text-danger">Gagal memuat grafik: ${e.message}</p>`;
    }
  }

  return { initialize };
})();

// Jalankan otomatis
document.addEventListener('DOMContentLoaded', ChartManager.initialize);
