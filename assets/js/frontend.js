jQuery(document).ready(function($) {
   
    $(document).on('input', '.bulletproof-card-cvv', function() {
        
        var cvv = $(this).val().trim();
        
        var cvvError = $(this).parents('.card-expiry-cvv').siblings('#cvv-error');
        
        if (/^\d+$/.test(cvv)) {
            
            cvvError.hide();
        } else {
            
            cvvError.show();
        }
    });
});