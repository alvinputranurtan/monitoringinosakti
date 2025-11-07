<?php // expects: $label, $labelText, $isOn?>
<div class="col-md-4 col-6">
  <div class="card-custom-monitoring text-center p-4">
    <h6 class="mb-3"><?php echo e($labelText); ?></h6>
    <button
      class="circle-button <?php echo $isOn ? 'active' : 'inactive'; ?>"
      data-type="button"
      data-key="<?php echo e($label); ?>"
      data-value="<?php echo $isOn ? 1 : 0; ?>"
    ><?php echo $isOn ? 'ON' : 'OFF'; ?></button>
  </div>
</div>
