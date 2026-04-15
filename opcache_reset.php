<?php
// Post-deploy opcache reset — appelé après git pull pour invalider le cache PHP
if (function_exists('opcache_reset')) {
    opcache_reset();
}
