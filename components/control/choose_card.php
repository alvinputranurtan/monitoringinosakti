<?php // expects: $label, $labelText, $selectedVal, $options(array)?>
<div class="col-md-4 col-6">
  <div class="card-custom-monitoring text-center p-4">
    <h6 class="mb-3"><?php echo e($labelText); ?></h6>
    <form class="formChoose" data-type="choose" data-key="<?php echo e($label); ?>">
      <select class="form-control text-center mb-2 kontrol-input">
        <?php foreach ($options as $opt) {
            $sel = ((string) $opt === (string) $selectedVal) ? 'selected' : '';
            ?>
          <option value="<?php echo e($opt); ?>" <?php echo $sel; ?>>
            <?php echo ucwords(e($opt)); ?>
          </option>
        <?php } ?>
      </select>
      <button class="btn btn-success px-3" type="submit">Simpan</button>
    </form>
  </div>
</div>
