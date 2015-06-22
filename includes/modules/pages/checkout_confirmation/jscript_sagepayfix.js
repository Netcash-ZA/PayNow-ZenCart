if( document.forms.checkout_confirmation ) {
	// Remove the securityToken field
	var children = document.forms.checkout_confirmation.childNodes || null,
		attr = 'name',
		element;

	if( children ) {
		for(var i = 0; i < children.length; i++ ) {
	        element = children[i];

	        if(element.hasAttribute(attr) && element.getAttribute(attr) == 'securityToken') {
            	element.remove();
            	break;
            }
	    }
    }
}

/*
jQuery(document).ready(function(){
	// This field is not required for 3rd part forms.
	jQuery('form#checkout_confirmation input[name="securityToken"]').remove();
});*/
