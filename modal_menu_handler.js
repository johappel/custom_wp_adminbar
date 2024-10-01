function openModal(modalId) {
    console.log(elem = jQuery("#" + modalId));
    // closse all other modals and mega menus
    jQuery('.custom-mega-menu').hide();
    jQuery('.custom-modal').hide();
    jQuery("#" + modalId).show();
}
function closeModal(modalId) {
    jQuery("#" + modalId).hide();
}
function toggleMegaMenu(menuId) {
    console.info(jQuery("#" + menuId), jQuery("#" + menuId).is(":visible"));
    if(jQuery("#" + menuId).is(":visible")) {
        jQuery('.custom-modal').hide();
        jQuery('.custom-mega-menu').hide();

    }else {
        jQuery('.custom-modal').hide();
        jQuery('.custom-mega-menu').hide();
        jQuery("#" + menuId).show();

    }

}
function closeMegaMenu(menuId) {
    jQuery("#" + menuId).hide();
}
jQuery(document).click(function(event) {
    if (jQuery(event.target).hasClass("custom-modal-close") || jQuery(event.target).hasClass("custom-mega-menu-close")) {
        jQuery('.custom-mega-menu').hide();
        jQuery('.custom-modal').hide();
        //jQuery(event.target).hide();
    }
});
jQuery(document).ready(function($) {
    $("#wpadminbar").show();
});
