<?php

/**
 * Position the Plugins menu item in the 3rd position
 */
function getbowtied_position_plugins_menu() {
    global $submenu;
    
    // Exit if dashboard menu doesn't exist
    if (!isset($submenu['getbowtied-dashboard'])) {
        return;
    }
    
    // Find plugins menu item
    $plugins_key = null;
    foreach ($submenu['getbowtied-dashboard'] as $key => $item) {
        if ($item[2] === 'getbowtied-plugins') {
            $plugins_key = $key;
            break;
        }
    }
    
    // Exit if plugins menu item not found
    if (null === $plugins_key) {
        return;
    }
    
    // Store plugins menu item and remove from original position
    $plugins_item = $submenu['getbowtied-dashboard'][$plugins_key];
    unset($submenu['getbowtied-dashboard'][$plugins_key]);
    
    // Get first two items (Home and Customize)
    $first_items = array_slice($submenu['getbowtied-dashboard'], 0, 2, true);
    $remaining_items = array_slice($submenu['getbowtied-dashboard'], 2, null, true);
    
    // Rebuild menu with plugins in third position
    $submenu['getbowtied-dashboard'] = array_merge(
        $first_items,
        [999 => $plugins_item], // Use a high key to avoid conflicts
        $remaining_items
    );
    
    // Sort by keys to maintain proper order
    ksort($submenu['getbowtied-dashboard']);
}

add_action('admin_menu', 'getbowtied_position_plugins_menu', 999); 