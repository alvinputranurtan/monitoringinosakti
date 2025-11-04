<?php
date_default_timezone_set('Asia/Jakarta');
include __DIR__.'/../functions/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Ambil data terakhir milik user
$sql = '
SELECT m.data_monitor, m.created_at
FROM monitor m
JOIN devices d ON m.device_id = d.id
WHERE d.user_id = ?
ORDER BY m.id DESC
LIMIT 1
';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$status_perangkat = 'Offline';
$data = [];

if ($row && isset($row['data_monitor'])) {
    $data = json_decode($row['data_monitor'], true) ?? [];

    $last_time = strtotime($row['created_at']);
    if ($last_time !== false && (time() - $last_time) <= 15) {
        $status_perangkat = 'Online';
    }
}

function format_value($key, $value)
{
    if (is_numeric($value)) {
        $value = number_format($value, 1);
        if (stripos($key, 'suhu') !== false) {
            return $value.'°C';
        }
        if (stripos($key, 'ph') !== false) {
            return $value;
        }
        if (stripos($key, 'kelembaban') !== false) {
            return $value.'%';
        }
        if (stripos($key, 'arus') !== false) {
            return $value.' A';
        }
        if (stripos($key, 'tegangan') !== false) {
            return $value.' V';
        }

        return $value;
    }

    $v = strtolower((string) $value);
    if (in_array($v, ['1', 'on', 'true', 'aktif'])) {
        return '<span class="text-success fw-bold">Aktif</span>';
    }
    if (in_array($v, ['0', 'off', 'false', 'nonaktif'])) {
        return '<span class="text-danger fw-bold">Nonaktif</span>';
    }

    return htmlspecialchars((string) $value);
}
?>

<!-- ======== DASHBOARD MONITORING ======== -->
<div class="container my-4">
    <div class="row g-3 d-flex" id="pembungkus-card">

        <!-- STATUS PERANGKAT -->
        <?php if ($row && !empty($row['data_monitor'])) { ?>
            <div class="col-lg-3 col-sm-6 col-6">
                <div class="card-custom-monitoring border shadow-sm">
                    <h5>Status Perangkat</h5>
                    <h3 id="status_perangkat">
                        <?php echo ($status_perangkat === 'Online')
                            ? '<span class="text-success">Online</span>'
                            : '<span class="text-danger">Offline</span>'; ?>
                    </h3>
                    <small class="text-muted" id="last_update">
                        Terakhir update: <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?>
                    </small>
                </div>
            </div>
        <?php } else { ?>
            <div class="card-custom-monitoring col-10 text-center text-muted">
                <p>Belum ada perangkat terdaftar untuk akun ini.</p>
            </div>
        <?php } ?>

        <!-- CARD DINAMIS -->
        <?php if (!empty($data)) { ?>
            <?php foreach ($data as $key => $value) { ?>
                <div class="col-lg-3 col-sm-6 col-6">
                    <div class="card-custom-monitoring border shadow-sm">
                        <h5><?php echo ucwords(str_replace('_', ' ', $key)); ?></h5>
                        <h3 id="<?php echo $key; ?>"><?php echo format_value($key, $value); ?></h3>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card-custom-monitoring col-10 text-center text-muted">
                <p>Tidak ada data monitor ditemukan.</p>
            </div>
        <?php } ?>

    </div>
</div>

<style>
#pembungkus-card:has(.col-10),
#pembungkus-card:has(.col-11) {
    justify-content: center !important;
}
</style>

<script>
// === Fungsi bantu untuk format nilai (harus sama dgn PHP-nya)
function formatValue(key, value) {
  if (typeof value === 'number') {
    let v = value.toFixed(1);
    if (key.includes('suhu')) return v + '°C';
    if (key.includes('ph')) return v;
    if (key.includes('kelembaban')) return v + '%';
    if (key.includes('arus')) return v + ' A';
    if (key.includes('tegangan')) return v + ' V';
    return v;
  }
  const v = String(value).toLowerCase();
  if (['1','on','true','aktif'].includes(v)) return '<span class="text-success fw-bold">Aktif</span>';
  if (['0','off','false','nonaktif'].includes(v)) return '<span class="text-danger fw-bold">Nonaktif</span>';
  return value;
}

// === Update dashboard otomatis ===
async function updateDashboard() {
  try {
    const res = await fetch('functions/ajax_dashboard.php?_=' + Date.now(), { cache: 'no-store' });
    const data = await res.json();

    // Update status perangkat
    const statusEl = document.getElementById('status_perangkat');
    if (statusEl) {
      statusEl.innerHTML = data.status_perangkat === 'Online'
        ? '<span class="text-success">Online</span>'
        : '<span class="text-danger">Offline</span>';
    }

    // Update waktu terakhir
    const timeEl = document.getElementById('last_update');
    if (timeEl && data.created_at) {
      timeEl.textContent = 'Terakhir update: ' + data.created_at;
    }

    // Update semua sensor value
    if (data.data && Object.keys(data.data).length > 0) {
      Object.entries(data.data).forEach(([key, value]) => {
        const el = document.getElementById(key);
        if (el) {
          el.innerHTML = formatValue(key, value);
        }
      });
    }

  } catch (err) {
    console.error('Gagal memperbarui dashboard:', err);
  }
}

// Refresh tiap 5 detik
setInterval(updateDashboard, 5000);
</script>
