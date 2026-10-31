<?php
if(!defined('WP_UNINSTALL_PLUGIN')) { exit; }
// Preserve the native ALT text explicitly authored by the administrator.
delete_post_meta_by_key('_msta_decorative');
delete_transient('mesplugins_release_mes-textes-alternatifs');
