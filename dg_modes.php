<?php
// Global settings for all grids using this installation
$this->modes = array(
   'compact' => array(
                    'show_search' => false,
                    'show_paging' => false,
                    'show_title' => false,
                    'show_dev_tools' => false,
                    'show_admin_tools' => false,
                    'show_result_counts' => false,
                    'show_html_head' => true
                    ),
   'full' => array(
                    'show_search' => true,
                    'show_paging' => true,
                    'show_title' => true,
                    'show_dev_tools' => true,
                    'show_admin_tools' => true,
                    'show_result_counts' => true,
                    'show_html_head' => true,
                    'show_reports' => true
                    ),
   'embedded' => array(
                    'show_search' => true,
                    'show_paging' => true,
                    'show_title' => true,
                    'show_dev_tools' => false,
                    'show_admin_toole' => false,
                    'show_result_counts' => true,
                    'show_html_head' => true,
                    'redirect_file_exports' => true
                    ),
   'child' => array(
                    'show_search' => true,
                    'show_paging' => true,
                    'show_close_window_button' => true,
                    'show_title' => true,
                    'show_dev_tools' => false,
                    'show_admin_tools' => false,
                    'show_result_counts' => false,
                    'show_html_head' => true
                    ),
);

?>
