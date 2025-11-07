<?php // expects: $label, $labelText, $val?>
<div class="col-md-4 col-6">
  <div class="card-custom-monitoring text-center p-4">
    <h6 class="mb-3"><?php echo e($labelText); ?></h6>
    <form class="formDate" data-type="dates" data-key="<?php echo e($label); ?>">
      <input type="datetime-local" class="form-control text-center mb-2 kontrol-input"
             value="<?php echo e($val); ?>">
      <button class="btn btn-success px-3" type="submit">Simpan</button>
    </form>
  </div>
</div>
