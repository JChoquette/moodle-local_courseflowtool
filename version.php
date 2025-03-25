<?php
/**
 * CourseFlow Import Tool for Moodle
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_courseflowtool';
$plugin->version = 2025010700; // YYYYMMDDXX format.
$plugin->requires = 2022112800; // Requires Moodle 4.1 or later.
$plugin->maturity = MATURITY_ALPHA; // Change to MATURITY_STABLE when finalized.
$plugin->release = '1.0.6';