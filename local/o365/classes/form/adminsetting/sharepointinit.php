<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_o365
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
 */

namespace local_o365\form\adminsetting;

/**
 * Admin setting to initialize sharepoint.
 */
class sharepointinit extends \admin_setting {
    /** @var mixed int means PARAM_XXX type, string is a allowed format in regex */
    public $paramtype;

    /** @var int default field size */
    public $size;

    /**
     * Config text constructor
     *
     * @param string $name unique ascii name.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string $defaultsetting
     * @param mixed $paramtype int means PARAM_XXX type, string is a allowed format in regex
     * @param int $size default field size
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $size=null) {
        $this->paramtype = $paramtype;
        if (!is_null($size)) {
            $this->size  = $size;
        } else {
            $this->size  = ($paramtype === PARAM_INT) ? 5 : 30;
        }
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Store new setting
     *
     * @param mixed $data string or array, must not be NULL
     * @return string empty string if ok, string error message otherwise
     */
    public function write_setting($data) {
        return ($this->config_write($this->name, '0'));
    }


    /**
     * Return an XHTML string for the setting
     * @return string Returns an XHTML string
     */
    public function output_html($data, $query='') {
        $tokens = get_config('local_o365', 'systemtokens');
        $setuser = '';
        if (!empty($tokens)) {
            $tokens = unserialize($tokens);
            if (isset($tokens['idtoken'])) {
                try {
                    $idtoken = \auth_oidc\jwt::instance_from_encoded($tokens['idtoken']);
                    $setuser = $idtoken->claim('upn');
                } catch (\Exception $e) {
                    // There is a check below for an empty $setuser.
                }
            }
        }

        $settinghtml = '<input type="hidden" id="'.$this->get_id().'" name="'.$this->get_full_name().'" value="0" />';
        if (!empty($setuser)) {
            $sitesinitialized = get_config('local_o365', 'sharepoint_initialized');
            $initurl = new \moodle_url('/local/o365/acp.php', ['mode' => 'sharepointinit']);
            if (!empty($sitesinitialized)) {
                $settinghtml .= get_string('settings_sharepointinit_initialized', 'local_o365');
                $settinghtml .= ' '.\html_writer::link($initurl, get_string('settings_sharepointinit_reinitialize', 'local_o365'));
            } else {
                $settinghtml .= \html_writer::link($initurl, get_string('settings_sharepointinit_initialize', 'local_o365'));
            }
        } else {
            $settinghtml .= get_string('settings_sharepointinit_setsystemapiuser', 'local_o365');
        }
        return format_admin_setting($this, $this->visiblename, $settinghtml, $this->description);
    }
}