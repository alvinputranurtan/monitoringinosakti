<?php include 'data_mockup.php'; ?>

<div class="container-fluid p-3">
    <div class="row g-3 mb-3">
        <?php foreach ($devices as $key => $device): ?>
            <div class="col-md-6">
                <div class="card p-3 rounded-4 shadow-sm custom-bg-card">
                    <h4 class="fw-bold"><?= $device['name'] ?></h4>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <div class="rounded-circle text-white text-center <?= $device['status_class'] ?>"
                            style="width: 60px; height: 60px; line-height: 60px;">
                            <strong><?= $device['status'] ?></strong>
                        </div>
                        <div>
                            <div>Status: <?= $device['status_text'] ?></div>
                            <div>Terakhir Offline: <?= $device['last_offline'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <?php foreach ($devices as $key => $device): ?>
            <div class="col-md-6">
                <div class="card p-3 rounded-4 shadow-sm custom-bg-card">
                    <h5 class="fw-bold">Tabel Status <?= $device['name'] ?></h5>
                    <table class="table table-bordered mt-2 table-custom">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Keaktifan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($device['logs'] as $log): ?>
                                <tr>
                                    <td><?= $log['waktu'] ?></td>
                                    <td><?= $log['aktif'] ?></td>
                                    <td><?= $log['status'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>