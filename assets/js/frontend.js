jQuery(document).ready(function ($) {
  $(document).on("input", ".bulletproof-card-cvv", function () {
    let cvv = $(this).val().trim();

    var cvvError = $(this).parents(".card-expiry-cvv").siblings("#cvv-error");

    if (/^\d+$/.test(cvv)) {
      cvvError.hide();
    } else {
      cvvError.show();
    }
  });
});

function bulletproof_validate_ccnumber(unique_id) {
  if (unique_id != "") {
    let ccnumber = jQuery("#" + unique_id)
      .val()
      .trim();
if (ccnumber!=""){
    if (/^\d+$/.test(ccnumber)) {
      jQuery("#ccnumber-error").hide();
    } else {
      jQuery("#ccnumber-error").show();
    }
  }
}
}
