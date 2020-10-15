import './core';
import './utils/TextEditor';

$(document).ready(function () {
    let registerInFolderCheckbox = $('#app_bundle_service_group_type_register_in_folder');
    if (registerInFolderCheckbox.prop('checked')) {
        $('#schema_keys_helper').show();
        $('#app_bundle_service_group_type_folder_object').closest('div').show();
    } else {
        $('#schema_keys_helper').hide();
        $('#app_bundle_service_group_type_folder_object').closest('div').hide();
    }

    registerInFolderCheckbox.change( function(){
        if (registerInFolderCheckbox.prop('checked')) {
            $('#schema_keys_helper').show();
            $('#app_bundle_service_group_type_folder_object').closest('div').show();
        } else {
            $('#schema_keys_helper').hide();
            $('#app_bundle_service_group_type_folder_object').closest('div').hide();
        }
    });
});