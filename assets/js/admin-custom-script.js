jQuery(document).ready(function($) {
    
    $(document).on('click', '.payment_capture_btn', function(e) {
        
        e.preventDefault();
        var order_id = $(this).data('order-id');
        var nonce = custom_script_vars.nonce;

        $.ajax({
            type: 'POST',
            url: custom_script_vars.ajax_url,
            data: {
                action: 'capture_order_payment',
                nonce: nonce,
                order_id: order_id
            },
            success: function(response) {
                if(response.success){
                    $('.payment_capture_btn[data-order-id="' + order_id + '"]').remove();
                    $('#post-' + order_id + ', #order-' + order_id).find('.order-status').html('<span>Completed</span>');
                    $('#post-' + order_id + ', #order-' + order_id).find('.order-status').removeClass('status-on-hold');
                    $('#post-' + order_id + ', #order-' + order_id).find('.order-status').addClass('status-completed');
                }else{
                    if(response.message){
                        alert(response.message);
                    }
                }
            }
        });
    });
});