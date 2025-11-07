<?php
require_once __DIR__.'/../functions/config.php';

// Ambil konfigurasi aktif terbaru
$sql = 'SELECT id, data_configuration FROM configurations WHERE is_active = 1 ORDER BY id DESC LIMIT 1';
$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo "<div class='alert alert-warning text-center mt-4'>Belum ada konfigurasi aktif.</div>";
    exit;
}

$row = $res->fetch_assoc();
$config_id = (int) $row['id'];
$config = json_decode($row['data_configuration'], true) ?: [];

// Ambil struktur fleksibel
$kontrol = $config['kontrol'] ?? [];
$choose = $config['web_control']['choose'] ?? [];

// Map opsi dropdown per field (bisa diisi dari DB kalau mau)
$optionsMap = [
    'plant_type' => ['potato', 'tomato', 'lettuce', 'cabbage', 'spinach'],
    // tambahkan mapping lain bila ada field choose lain
];

// Helper aman untuk teks
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kontrol Otomatis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-custom-monitoring {
      background: rgba(255,255,255,0.85);
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      height: 100%;
    }
    .circle-button {
      width: 100px; height: 100px; border-radius: 50%; border: none;
      font-weight: 600; color: #fff; transition: all .3s ease;
    }
    .circle-button.active { background: #27ae60; }
    .circle-button.inactive { background: #e74c3c; }
    .section-title { border-bottom: 1px solid #eee; padding-bottom: .5rem; margin-top: 1.5rem; }
  </style>
</head>
<body>
<div class="container my-4">
  <h4 class="fw-bold mb-1 text-center">Kontrol Otomatis</h4>
  <p class="text-center text-muted mb-4">Semua kontrol dibaca langsung dari konfigurasi aktif</p>

  <div class="row g-4" id="kontrolContainer" data-config-id="<?php echo $config_id; ?>">

   <?php
// BUTTONS
if (isset($kontrol['button']) && is_array($kontrol['button']) && count($kontrol['button']) > 0) {
    echo '<div class="col-12"><h6 class="section-title">Tombol (ON/OFF)</h6></div>';
    foreach ($kontrol['button'] as $label => $val) {
        $labelText = ucwords(str_replace('_', ' ', $label));
        $isOn = (int) $val === 1;
        include __DIR__.'/../components/control/button_card.php';
    }
}

// MINUTES
if (isset($kontrol['minutes']) && is_array($kontrol['minutes']) && count($kontrol['minutes']) > 0) {
    echo '<div class="col-12"><h6 class="section-title">Durasi (menit/detik)</h6></div>';
    foreach ($kontrol['minutes'] as $label => $val) {
        $labelText = ucwords(str_replace('_', ' ', $label));
        include __DIR__.'/../components/control/minutes_card.php';
    }
}

// DATES
if (isset($kontrol['dates']) && is_array($kontrol['dates']) && count($kontrol['dates']) > 0) {
    echo '<div class="col-12"><h6 class="section-title">Jadwal (Tanggal & Waktu)</h6></div>';
    foreach ($kontrol['dates'] as $label => $val) {
        $labelText = ucwords(str_replace('_', ' ', $label));
        include __DIR__.'/../components/control/dates_card.php';
    }
}

// CHOOSE (Dropdown)
if (isset($choose) && is_array($choose) && count($choose) > 0) {
    echo '<div class="col-12"><h6 class="section-title">Pilihan (Dropdown)</h6></div>';
    foreach ($choose as $label => $selectedVal) {
        $labelText = ucwords(str_replace('_', ' ', $label));
        $options = $optionsMap[$label] ?? [$selectedVal];
        include __DIR__.'/../components/control/choose_card.php';
    }
}

// Jika benar-benar tidak ada kontrol
if (
    (!isset($kontrol) || empty($kontrol))
    && (!isset($choose) || empty($choose))
) {
    echo "<div class='col-12'><div class='alert alert-warning text-center'>Belum ada data kontrol pada konfigurasi ini.</div></div>";
}
?>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// State fleksibel
const kontrolState = { button:{}, minutes:{}, dates:{}, choose:{} };

// Ambil state terbaru dari UI
function refreshStateFromUI() {
  // Buttons
  $('#kontrolContainer .circle-button').each(function() {
    const key = $(this).data('key');
    const v = $(this).data('value') ? 1 : 0;
    if (key) kontrolState.button[key] = v;
  });

  // Minutes
  $('#kontrolContainer form.formDurasi').each(function() {
    const key = $(this).data('key');
    const v = $(this).find('input[type="number"]').val();
    if (key) kontrolState.minutes[key] = v;
  });

  // Dates
  $('#kontrolContainer form.formDate').each(function() {
    const key = $(this).data('key');
    const v = $(this).find('input[type="datetime-local"]').val();
    if (key) kontrolState.dates[key] = v;
  });

  // Choose
  $('#kontrolContainer form.formChoose').each(function() {
    const key = $(this).data('key');
    const v = $(this).find('select').val();
    if (key) kontrolState.choose[key] = v;
  });
}

// Simpan snapshot ke server
function saveSnapshot() {
  const configId = $('#kontrolContainer').data('config-id');
  $.ajax({
    url: 'functions/update_kontrol.php',
    method: 'POST',
    dataType: 'json',
    data: {
      config_id: configId,
      kontrol: JSON.stringify(kontrolState)
    },
    success: res => {
      if (!res.success) {
        alert('Gagal menyimpan: ' + (res.message || 'Unknown error'));
      } else {
        console.log('✅ Saved:', res.message);
      }
    },
    error: xhr => {
      alert('Server error (update_kontrol.php).');
    }
  });
}

// Delegated events
// Toggle button
$(document).on('click', '.circle-button', function(){
  const newVal = $(this).data('value') ? 0 : 1;
  $(this).data('value', newVal);
  $(this).toggleClass('active inactive').text(newVal ? 'ON' : 'OFF');
  refreshStateFromUI();
  saveSnapshot();
});

// Minutes & Dates submit
$(document).on('submit', 'form.formDurasi, form.formDate, form.formChoose', function(e){
  e.preventDefault();
  refreshStateFromUI();
  saveSnapshot();
});

// Init first snapshot from rendered UI
refreshStateFromUI();
</script>
</body>
</html>
