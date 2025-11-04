<?php
require_once __DIR__.'/../functions/config.php';

$sql = 'SELECT id, data_configuration FROM configurations WHERE is_active = 1 ORDER BY id DESC LIMIT 1';
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo "<div class='alert alert-warning text-center mt-4'>Belum ada konfigurasi aktif.</div>";
    exit;
}
$row = $res->fetch_assoc();
$config_id = $row['id'];
$config = json_decode($row['data_configuration'], true);
$kontrol = $config['kontrol'] ?? [
    'button' => [
        'pompa_air' => 0,
        'pompa_nutrisi' => 0,
        'kipas_pendingin' => 0,
    ],
    'minutes' => [
        'durasi_pompa_on' => 1,
        'durasi_pompa_off' => 10,
    ],
    'dates' => [
        'penyiraman_terjadwal' => date('Y-m-d\TH:i'),
    ],
];
?>

<div class="container my-4">
  <h4 class="fw-bold mb-4 text-center">Kontrol Otomatis</h4>

  <div class="row g-4" id="kontrolContainer" data-config-id="<?php echo $config_id; ?>">

    <!-- BUTTON -->
    <?php foreach ($kontrol['button'] as $label => $val) { ?>
    <div class="col-md-4 col-6">
      <div class="card-custom-monitoring text-center p-4">
        <h6 class="mb-3"><?php echo ucwords(str_replace('_', ' ', $label)); ?></h6>
        <button class="circle-button <?php echo $val ? 'active' : 'inactive'; ?>" 
                data-type="button"
                data-key="<?php echo $label; ?>"
                data-value="<?php echo $val; ?>">
          <?php echo $val ? 'ON' : 'OFF'; ?>
        </button>
      </div>
    </div>
    <?php } ?>

    <!-- MINUTES -->
    <?php foreach ($kontrol['minutes'] as $label => $val) { ?>
    <div class="col-md-4 col-6">
      <div class="card-custom-monitoring text-center p-4">
        <h6 class="mb-3"><?php echo ucwords(str_replace('_', ' ', $label)); ?></h6>
        <form class="formDurasi" data-type="minutes" data-key="<?php echo $label; ?>">
          <input type="number" class="form-control text-center mb-2 kontrol-input"
                 value="<?php echo $val; ?>" min="0">
          <button class="btn btn-success px-3" type="submit">Simpan</button>
        </form>
      </div>
    </div>
    <?php } ?>

    <!-- DATES -->
    <?php foreach ($kontrol['dates'] as $label => $val) { ?>
    <div class="col-md-4 col-6">
      <div class="card-custom-monitoring text-center p-4">
        <h6 class="mb-3"><?php echo ucwords(str_replace('_', ' ', $label)); ?></h6>
        <form class="formDate" data-type="dates" data-key="<?php echo $label; ?>">
          <input type="datetime-local" class="form-control text-center mb-2 kontrol-input"
                 value="<?php echo $val; ?>">
          <button class="btn btn-success px-3" type="submit">Simpan</button>
        </form>
      </div>
    </div>
    <?php } ?>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const kontrolState = {
  button: {},
  minutes: {},
  dates: {}
};

// --- Inisialisasi state dari HTML ---
function refreshStateFromUI() {
  $('.circle-button').each(function() {
    kontrolState.button[$(this).data('key')] = $(this).data('value') ? 1 : 0;
  });
  $('.formDurasi').each(function() {
    kontrolState.minutes[$(this).data('key')] = $(this).find('input').val();
  });
  $('.formDate').each(function() {
    kontrolState.dates[$(this).data('key')] = $(this).find('input').val();
  });
}

// --- Fungsi untuk simpan snapshot penuh ---
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
      if (res.success) {
        console.log('✅', res.message);
      } else {
        alert('❌ ' + res.message);
      }
    },
    error: () => alert('Server error (update_kontrol.php tidak ditemukan)')
  });
}

// --- Klik tombol (auto save) ---
$('.circle-button').on('click', function() {
  const key = $(this).data('key');
  const newVal = $(this).data('value') ? 0 : 1;
  $(this).data('value', newVal);
  $(this).toggleClass('active inactive').text(newVal ? 'ON' : 'OFF');

  refreshStateFromUI();
  saveSnapshot(); // langsung commit snapshot lengkap
});

// --- Submit form minutes/dates (auto save snapshot juga) ---
$('.formDurasi, .formDate').on('submit', function(e) {
  e.preventDefault();
  refreshStateFromUI();
  saveSnapshot();
});
</script>

<style>
.card-custom-monitoring {
  background: rgba(255,255,255,0.85);
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.circle-button {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: none;
  font-weight: 600;
  color: #fff;
  transition: all .3s ease;
}
.circle-button.active { background: #27ae60; }
.circle-button.inactive { background: #e74c3c; }
</style>
