jQuery(document).ready(function($) {
    // Handle opening the product chat modal
    $(document).on('click', '.product-chat-button', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        const modal = $('#product-chat-modal-' + productId);
        
        if (modal.length) {
            modal.addClass('active');
            // Trigger the polling start for the chat app inside this specific modal
            modal.find('.workcity-chat-container').trigger('start-polling');
        }
    });

    // Handle closing the product chat modal
    $(document).on('click', '.product-chat-close-btn', function(e) {
        e.preventDefault();
        const modal = $(this).closest('.product-chat-modal');
        
        if (modal.length) {
            modal.removeClass('active');
            // Trigger the polling stop for the chat app inside this specific modal
            modal.find('.workcity-chat-container').trigger('stop-polling');
        }
    });
});
