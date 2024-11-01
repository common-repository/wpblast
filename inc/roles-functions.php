<?php

function wpblast_permission_check($request)
{
    return apply_filters('wpblast_permissions_rest_allowed', current_user_can('wpblast_admin'));
}

add_filter('wpblast_settings_allowed_roles', 'wpblast_default_roles');
function wpblast_default_roles($roles)
{
    $newRoles = [
        'administrator',
        'wpseo_manager',
    ];
    foreach ($newRoles as $newRole) {
        if (!in_array($newRole, $roles)) {
            array_push($roles, $newRole);
        }
    }
    return $roles;
}

function wpblast_add_capability()
{
    $allowedRoles = apply_filters('wpblast_settings_allowed_roles', []);

    foreach ($allowedRoles as $role) {
        // get the the role object
        $role_object = get_role($role);
        if (isset($role_object)) {
            // add $cap capability to this role object
            $role_object->add_cap('wpblast_admin');
        }
    }
}

function wpblast_remove_capability()
{
    $allowedRoles = apply_filters('wpblast_settings_allowed_roles', []);

    foreach ($allowedRoles as $role) {
        // get the the role object
        $role_object = get_role($role);
        if (isset($role_object)) {
            // add $cap capability to this role object
            $role_object->remove_cap('wpblast_admin');
        }
    }
}
