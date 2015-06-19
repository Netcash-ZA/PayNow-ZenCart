jQuery(document).ready(function(){
	/**
	 * This field is not required for 3rd part forms.
	 */
	jQuery('form#checkout_confirmation input[name="securityToken"]').remove();

});