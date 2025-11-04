<?php
require_once __DIR__.'/../functions/config.php';
?>

<style>
.card-custom canvas {
    max-height: 300px !important;
    width: 100% !important;
}
</style>

<div class="container my-4">
    <div class="row g-4" id="chartContainer"></div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Tentukan path absolut (dari root domain, bukan relatif file) -->
<script>
window.API_CHART_URL = '/aeroponik.inosakti.com/functions/get_chart_data.php';
</script>

<!-- Load grafik.js -->
<script src="/aeroponik.inosakti.com/assets/js/grafik.js"></script>
