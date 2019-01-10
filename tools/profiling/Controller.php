<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

abstract class Controller extends ControllerCore
{
    protected $total_filesize = 0;
    protected $total_query_time = 0;
    protected $total_global_var_size = 0;
    protected $total_modules_time = 0;
    protected $total_modules_memory = 0;
    protected $global_var_size = array();

    protected $modules_perfs = array();
    protected $hooks_perfs = array();

    protected $array_queries = array();

    protected $profiler = array();

    // Colors
    protected $color_green = '#639f74';
    protected $color_orange = '#cf8627';
    protected $color_red = '#c55e5c';
    protected $color_blue = '#6088c4';

    private function getMemoryColor($n)
    {
        $n /= 1048576;
        if ($n > 3) {
            return '<span style="color:'.$this->color_red.'">'.sprintf('%0.2f', $n).'</span>';
        } elseif ($n > 1) {
            return '<span style="color:'.$this->color_orange.'">'.sprintf('%0.2f', $n).'</span>';
        } elseif (round($n, 2) > 0) {
            return '<span style="color:'.$this->color_green.'">'.sprintf('%0.2f', $n).'</span>';
        }
        return '<span style="color:'.$this->color_green.'">0</span>';
    }

    private function getPeakMemoryColor($n)
    {
        $n /= 1048576;
        if ($n > 16) {
            return '<span style="color:'.$this->color_red.'">'.sprintf('%0.1f', $n).'</span>';
        }
        if ($n > 12) {
            return '<span style="color:'.$this->color_orange.'">'.sprintf('%0.1f', $n).'</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.sprintf('%0.1f', $n).'</span>';
    }

    private function displaySQLQueries($n)
    {
        if ($n > 150) {
            return '<span style="color:'.$this->color_red.'">'.$n.' queries</span>';
        }
        if ($n > 100) {
            return '<span style="color:'.$this->color_orange.'">'.$n.' queries</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$n.' quer'.($n == 1 ? 'y' : 'ies').'</span>';
    }

    private function displayRowsBrowsed($n)
    {
        if ($n > 400) {
            return '<span style="color:'.$this->color_red.'">'.$n.' rows browsed</span>';
        }
        if ($n > 100) {
            return '<span style="color:'.$this->color_orange.'">'.$n.'  rows browsed</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$n.' row'.($n == 1 ? '' : 's').' browsed</span>';
    }

    private function getPhpVersionColor($version)
    {
        if (version_compare($version, '5.3') < 0) {
            return '<span style="color:'.$this->color_red.'">'.$version.' (Upgrade strongly recommended)</span>';
        } elseif (version_compare($version, '5.4') < 0) {
            return '<span style="color:'.$this->color_orange.'">'.$version.' (Consider upgrading)</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$version.' (OK)</span>';
    }

    private function getMySQLVersionColor($version)
    {
        if (version_compare($version, '5.5') < 0) {
            return '<span style="color:'.$this->color_red.'">'.$version.' (Upgrade strongly recommended)</span>';
        } elseif (version_compare($version, '5.6') < 0) {
            return '<span style="color:'.$this->color_orange.'">'.$version.' (Consider upgrading)</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$version.' (OK)</span>';
    }

    private function getLoadTimeColor($n, $kikoo = false)
    {
        if ($n > 1.6) {
            return '<span style="color:'.$this->color_red.'">'.round($n * 1000).'</span>'.($kikoo ? ' ms - You\'d better run your shop on a toaster' : '');
        } elseif ($n > 0.8) {
            return '<span style="color:'.$this->color_orange.'">'.round($n * 1000).'</span>'.($kikoo ? ' ms - OK... for a shared hosting' : '');
        } elseif ($n > 0) {
            return '<span style="color:'.$this->color_green.'">'.round($n * 1000).'</span>'.($kikoo ? ' ms - Unicorn powered webserver!' : '');
        }
        return '<span style="color:'.$this->color_green.'">-</span>'.($kikoo ? ' ms - Faster than light' : '');
    }

    private function getTotalQueriyingTimeColor($n)
    {
        if ($n >= 100) {
            return '<span style="color:'.$this->color_red.'">'.$n.'</span>';
        } elseif ($n >= 50) {
            return '<span style="color:'.$this->color_orange.'">'.$n.'</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$n.'</span>';
    }

    private function getNbQueriesColor($n)
    {
        if ($n >= 100) {
            return '<span style="color:'.$this->color_red.'">'.$n.'</span>';
        } elseif ($n >= 50) {
            return '<span style="color:'.$this->color_orange.'">'.$n.'</span>';
        }
        return '<span style="color:'.$this->color_green.'">'.$n.'</span>';
    }

    private function getTimeColor($n)
    {
        if ($n > 4) {
            return 'style="color:'.$this->color_red.'"';
        }
        if ($n > 2) {
            return 'style="color:'.$this->color_orange.'"';
        }
        return 'style="color:'.$this->color_green.'"';
    }

    private function getQueryColor($n)
    {
        if ($n > 5) {
            return 'style="color:'.$this->color_red.'"';
        }
        if ($n > 2) {
            return 'style="color:'.$this->color_orange.'"';
        }
        return 'style="color:'.$this->color_green.'"';
    }

    private function getTableColor($n)
    {
        if ($n > 30) {
            return 'style="color:'.$this->color_red.'"';
        }
        if ($n > 20) {
            return 'style="color:'.$this->color_orange.'"';
        }
        return 'style="color:'.$this->color_green.'"';
    }

    private function getObjectModelColor($n)
    {
        if ($n > 50) {
            return 'style="color:'.$this->color_red.'"';
        }
        if ($n > 10) {
            return 'style="color:'.$this->color_orange.'"';
        }
        return 'style="color:'.$this->color_green.'"';
    }

    protected function stamp($block)
    {
        return array('block' => $block, 'memory_usage' => memory_get_usage(), 'peak_memory_usage' => memory_get_peak_usage(), 'time' => microtime(true));
    }

    public function __construct()
    {
        $this->profiler[] = $this->stamp('config');

        parent::__construct();
        $this->profiler[] = $this->stamp('__construct');
    }

    public function run()
    {
        $this->init();
        $this->profiler[] = $this->stamp('init');

        if ($this->checkAccess()) {
            $this->profiler[] = $this->stamp('checkAccess');

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->setMedia();
                $this->profiler[] = $this->stamp('setMedia');
            }

            $this->postProcess();
            $this->profiler[] = $this->stamp('postProcess');

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->initHeader();
                $this->profiler[] = $this->stamp('initHeader');
            }

            $this->initContent();
            $this->profiler[] = $this->stamp('initContent');

            if (!$this->content_only && ($this->display_footer || (isset($this->className) && $this->className))) {
                $this->initFooter();
                $this->profiler[] = $this->stamp('initFooter');
            }

            if ($this->ajax) {
                $action = Tools::toCamelCase(Tools::getValue('action'), true);
                if (!empty($action) && method_exists($this, 'displayAjax'.$action)) {
                    $this->{'displayAjax'.$action}();
                } elseif (method_exists($this, 'displayAjax')) {
                    $this->displayAjax();
                }
                return;
            }
        } else {
            $this->initCursedPage();
        }

        $this->displayProfiling();
    }

    private function getVarSize($var)
    {
        $start_memory = memory_get_usage();
        try {
            $tmp = json_decode(json_encode($var));
        } catch (Exception $e) {
            $tmp = $this->getVarData($var);
        }
        $size = memory_get_usage() - $start_memory;
        return $size;
    }

    private function getVarData($var)
    {
        if (is_object($var)) {
            return $var;
        }
        return (string)$var;
    }

    protected function processProfilingData()
    {
        global $start_time;

        // Including a lot of files uses memory
        foreach (get_included_files() as $file) {
            $this->total_filesize += filesize($file);
        }

        // Sum querying time
        foreach (Db::getInstance()->queries as $data) {
            $this->total_query_time += $data['time'];
        }

        foreach ($GLOBALS as $key => $value) {
            if ($key != 'GLOBALS') {
                $this->total_global_var_size += ($size = $this->getVarSize($value));
                if ($size > 1024) {
                    $this->global_var_size[$key] = round($size / 1024);
                }
            }
        }
        arsort($this->global_var_size);

        $cache = Cache::retrieveAll();
        $this->total_cache_size = $this->getVarSize($cache);

        // Retrieve module perfs
        $result = Db::getInstance()->ExecuteS('
    		SELECT *
    		FROM '._DB_PREFIX_.'modules_perfs
    		WHERE session = '.(int)Module::$_log_modules_perfs_session.'
    		AND time_start >= '.(float)$start_time.'
    		AND time_end <= '.(float)$this->profiler[count($this->profiler) - 1]['time']
        );

        foreach ($result as $row) {
            $tmp_time = $row['time_end'] - $row['time_start'];
            $tmp_memory = $row['memory_end'] - $row['memory_start'];
            $this->total_modules_time += $tmp_time;
            $this->total_modules_memory += $tmp_memory;

            if (!isset($this->modules_perfs[$row['module']])) {
                $this->modules_perfs[$row['module']] = array('time' => 0, 'memory' => 0, 'methods' => array());
            }
            $this->modules_perfs[$row['module']]['time'] += $tmp_time;
            $this->modules_perfs[$row['module']]['methods'][$row['method']]['time'] = $tmp_time;
            $this->modules_perfs[$row['module']]['memory'] += $tmp_memory;
            $this->modules_perfs[$row['module']]['methods'][$row['method']]['memory'] = $tmp_memory;

            if (!isset($this->hooks_perfs[$row['method']])) {
                $this->hooks_perfs[$row['method']] = array('time' => 0, 'memory' => 0, 'modules' => array());
            }
            $this->hooks_perfs[$row['method']]['time'] += $tmp_time;
            $this->hooks_perfs[$row['method']]['modules'][$row['module']]['time'] = $tmp_time;
            $this->hooks_perfs[$row['method']]['memory'] += $tmp_memory;
            $this->hooks_perfs[$row['method']]['modules'][$row['module']]['memory'] = $tmp_memory;
        }
        uasort($this->modules_perfs, 'prestashop_querytime_sort');
        uasort($this->hooks_perfs, 'prestashop_querytime_sort');

        $queries = Db::getInstance()->queries;
        uasort($queries, 'prestashop_querytime_sort');
        foreach ($queries as $data) {
            $query_row = array(
                'time' => $data['time'],
                'query' => $data['query'],
                'location' => str_replace(
                    '\\',
                    '/',
                    substr($data['stack'][0]['file'], strlen(_PS_ROOT_DIR_))
                ).':'.$data['stack'][0]['line'],
                'filesort' => false,
                'rows' => 1,
                'group_by' => false,
                'stack' => array(),
                'explain' => null,
            );
            // EXPLAIN works with SELECT, DELETE, INSERT, REPLACE and UPDATE.
            if (preg_match('/^\s*select\s+/i', $data['query'])
                || preg_match('/^\s*delete\s+/i', $data['query'])
                || preg_match('/^\s*insert\s+/i', $data['query'])
                || preg_match('/^\s*replace\s+/i', $data['query'])
                || preg_match('/^\s*update\s+/i', $data['query'])
            ) {
                $explain = Db::getInstance()->executeS('explain '.$data['query']);
                if (stristr($explain[0]['Extra'], 'filesort')) {
                    $query_row['filesort'] = true;
                }
                foreach ($explain as $row) {
                    $query_row['rows'] *= $row['rows'];
                }
                if (stristr($data['query'], 'group by') && !preg_match('/(avg|count|min|max|group_concat|sum)\s*\(/i', $data['query'])) {
                    $query_row['group_by'] = true;
                }
                $query_row['explain'] = $explain;
            }

            array_shift($data['stack']);
            foreach ($data['stack'] as $call) {
                $query_row['stack'][] = str_replace('\\', '/', substr($call['file'], strlen(_PS_ROOT_DIR_))).':'.$call['line'];
            }

            $this->array_queries[] = $query_row;
        }

        uasort(ObjectModel::$debug_list, function ($a, $b) {
            return (count($a) < count($b)) ? 1 : -1;
        });
        arsort(Db::getInstance()->tables);
        arsort(Db::getInstance()->uniqQueries);
    }

    protected function displayProfilingStyle()
    {
        // Heavily based on Symfony's toolbar code
        // Copyright (c) 2004-2018 Fabien Potencier
?>
        <style>
            .sf-minitoolbar {
                background-color: #1a1a1a;
                border-top-left-radius: 4px;
                bottom: 0;
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
                /* display: none; */
                height: 36px;
                padding: 6px;
                position: fixed;
                right: 0;
                z-index: 7999;
            }
            .sf-minitoolbar a {
                display: block;
            }
            .sf-minitoolbar svg,
            .sf-minitoolbar img {
                max-height: 24px;
                max-width: 24px;
                display: inline;
            }
            .sf-toolbar-clearer {
                clear: both;
                height: 36px;
            }
            .sf-display-none {
                display: none;
            }
            .sf-toolbarreset * {
                -webkit-box-sizing: content-box;
                -moz-box-sizing: content-box;
                box-sizing: content-box;
                vertical-align: baseline;
                letter-spacing: normal;
            }
            .sf-toolbarreset {
                background-color: #1a1a1a;
                bottom: 0;
                color: #EEE;
                font: 11px Arial, sans-serif;
                left: 0;
                margin: 0;
                padding: 0 36px 0 0;
                position: fixed;
                right: 0;
                text-align: left;
                text-transform: none;
                z-index: 7999;
                /* neutralize the aliasing defined by external CSS styles */
                -webkit-font-smoothing: subpixel-antialiased;
                -moz-osx-font-smoothing: auto;
            }
            .sf-toolbarreset abbr {
                border: dashed #777;
                border-width: 0 0 1px;
            }
            .sf-toolbarreset svg,
            .sf-toolbarreset img {
                width: auto !important;
                height: 20px;
                display: inline-block;
            }
            .sf-toolbarreset .hide-button {
                background: #333;
                display: block;
                position: absolute;
                top: 0;
                right: 0;
                width: 36px;
                height: 36px;
                cursor: pointer;
                text-align: center;
            }
            .sf-toolbarreset .hide-button:hover {
                background: #000;
            }
            .sf-toolbarreset .hide-button svg {
                max-height: 18px;
                margin-top: 10px;
            }
            .sf-toolbar-block {
                cursor: default;
                display: block;
                float: left;
                height: 36px;
                margin-right: 0;
                white-space: nowrap;
            }
            .sf-toolbar-block-right {
                float: right;
                margin-left: 0;
                margin-right: 0;
            }
            .sf-toolbar-block > a,
            .sf-toolbar-block > a:hover {
                display: block;
                text-decoration: none;
            }
            .sf-toolbar-block span {
                display: inline-block;
            }
            .sf-toolbar-block #database-queries-search {
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
                width: 100%;
                margin: 0 0 10px 0;
                border: 3px solid #1a1a1a;
                border-radius: 0;
                padding: .25em .75em;
                font-size: 14px;
                color: #333;
                background: #dfdfdf;
            }
            .sf-toolbar-block .sf-toolbar-value {
                color: #F5F5F5;
                font-size: 13px;
                line-height: 36px;
                padding: 0;
            }
            .sf-toolbar-block .sf-toolbar-label,
            .sf-toolbar-block .sf-toolbar-class-separator {
                color: #AAA;
                font-size: 12px;
            }
            .sf-toolbar-block .sf-toolbar-info {
                background-color: #333;
                bottom: 36px;
                color: #F5F5F5;
                display: none;
                padding: 9px 0;
                position: absolute;
                border-collapse: collapse;
                z-index: 7999;
                border-top: 3px solid #1a1a1a;
                box-shadow: 3px 0 #1a1a1a, -3px 0 #1a1a1a;
            }
            .sf-toolbar-block .sf-toolbar-info:empty {
                visibility: hidden;
            }
            .sf-toolbar-block *::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            .sf-toolbar-block *::-webkit-scrollbar-track {
                background: #555;
                border-radius: 3px;
            }
            .sf-toolbar-block *::-webkit-scrollbar-thumb {
                border-radius: 3px;
                background: #888;
            }
            .sf-toolbar-block.sf-toolbar-database:hover .sf-toolbar-info,
            .sf-toolbar-block.sf-toolbar-exception:hover .sf-toolbar-info {
                max-width: none;
            }
            /* Tables */
            .sf-toolbar-block .sf-toolbar-info table {
                max-width: 100% !important;
                margin: 0;
                border: 0;
                padding: 0;
                border-collapse: collapse;
                border-spacing: 0;
            }
            .sf-toolbar-block .sf-toolbar-info table.sortable thead th:not([data-defaultsort="disabled"]) {
                cursor: row-resize;
            }
            .sf-toolbar-block .sf-toolbar-info table.sortable thead th:not([data-defaultsort="disabled"]):hover {
                color: #99CDD8;
            }
            .sf-toolbar-block .sf-toolbar-info tr:hover {
                background-color: rgba(0,0,0,.1) !important;
            }
            .sf-toolbar-block .sf-toolbar-info tr[class$="_detail"],
            .sf-toolbar-block .sf-toolbar-info tr[class$="_details"] {
                border-left: 10px solid transparent !important;
                border-right: 10px solid transparent !important;
                background-color: rgba(0,0,0,.25) !important;
            }
            .sf-toolbar-block .sf-toolbar-info th, .sf-toolbar-block .sf-toolbar-info td {
                border: 0;
                padding: 2px 10px 2px 0;
                color: inherit;
                background: none;
            }
            .sf-toolbar-block .sf-toolbar-info th:last-child, .sf-toolbar-block .sf-toolbar-info td:last-child {
                padding-right: 0;
            }
            .sf-toolbar-block .sf-toolbar-info th.sorted {
                white-space: nowrap;
            }
            .sf-toolbar-block .sf-toolbar-info th.sorted div {
                display: inline;
            }
            .sf-toolbar-block .sf-toolbar-info th.sorted.up:before {
                content: '▼';
                display: inline-block;
            }
            .sf-toolbar-block .sf-toolbar-info th.sorted.down:before {
                content: '▲';
                display: inline-block;
            }
            .sf-toolbar-block .sf-toolbar-info tr {
                position: relative;
            }
            .sf-toolbar-block .sf-toolbar-info td {
                position: relative;
                vertical-align: middle;
            }
            .sf-toolbar-block .sf-toolbar-info td.center,
            .sf-toolbar-block .sf-toolbar-info th.center {
                text-align: center;
            }
            .sf-toolbar-block .sf-toolbar-info td.details svg {
                cursor: pointer;
                max-height: 16px;
                margin-top: 0;
                margin-right: 4px;
                vertical-align: middle;
                line-height: .8em;
            }
            .sf-toolbar-block .sf-toolbar-info td.details .query,
            .sf-toolbar-block .sf-toolbar-info td.details .explain {
                z-index: 7999;
            }
            .sf-toolbar-block .sf-toolbar-info td.details svg:hover path {
                fill: #99CDD8 !important;
            }
            .sf-toolbar-block .sf-toolbar-info td.nowrap {
                white-space: nowrap;
            }
            /* General DOM */
            .sf-toolbar-block .sf-toolbar-info a {
                color: #99CDD8 !important;
                text-decoration: none;
            }
            .sf-toolbar-block .sf-toolbar-info a:hover {
                color: #fff !important;
            }
            /* Tabs */
            .sf-toolbar-block .sf-toolbar-info .sf-toolbar-info-tabs {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .sf-toolbar-block .sf-toolbar-info .sf-toolbar-info-tabs li {
                display: inline-block;
            }
            .sf-toolbar-block .sf-toolbar-info .sf-toolbar-info-tabs li:after {
                content: '|';
                padding: 0 5px;
            }
            .sf-toolbar-block .sf-toolbar-info .sf-toolbar-info-tabs li:last-child:after {
                content: '';
            }
            .sf-toolbar-block .sf-toolbar-info-group {
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #1a1a1a;
            }
            .sf-toolbar-block .sf-toolbar-info-group:last-child {
                margin-bottom: 0;
                border-bottom: none;
            }
            /* Statuses */
            .sf-toolbar-block .sf-toolbar-status {
                display: inline-block;
                color: #FFF;
                background-color: #666;
                padding: 3px 6px;
                margin-bottom: 2px;
                vertical-align: middle;
                min-width: 15px;
                min-height: 13px;
                text-align: center;
            }
            .sf-toolbar-block .sf-toolbar-status-green {
                background-color: #4F805D;
            }
            .sf-toolbar-block .sf-toolbar-status-red {
                background-color: #B0413E;
            }
            .sf-toolbar-block .sf-toolbar-status-yellow {
                background-color: #A46A1F;
            }
            .sf-toolbar-block.sf-toolbar-status-green {
                background-color: #4F805D;
                color: #FFF;
            }
            .sf-toolbar-block.sf-toolbar-status-red {
                background-color: #B0413E;
                color: #FFF;
            }
            .sf-toolbar-block.sf-toolbar-status-yellow {
                background-color: #A46A1F;
                color: #FFF;
            }
            .sf-toolbar-status-green .sf-toolbar-label,
            .sf-toolbar-status-yellow .sf-toolbar-label,
            .sf-toolbar-status-red .sf-toolbar-label {
                color: #FFF;
            }
            .sf-toolbar-status-green svg path,
            .sf-toolbar-status-green svg .sf-svg-path,
            .sf-toolbar-status-red svg path,
            .sf-toolbar-status-red svg .sf-svg-path,
            .sf-toolbar-status-yellow svg path,
            .sf-toolbar-status-yellow svg .sf-svg-path {
                fill: #FFF;
            }
            .sf-toolbar-block-config svg path,
            .sf-toolbar-block-config svg .sf-svg-path {
                fill: #FFF;
            }
            .sf-toolbar-block .sf-toolbar-icon {
                display: block;
                height: 36px;
                padding: 0 7px;
            }
            .sf-toolbar-block .sf-toolbar-icon img,
            .sf-toolbar-block .sf-toolbar-icon svg {
                border-width: 0;
                position: relative;
                top: 8px;
            }
            .sf-toolbar-block .sf-toolbar-icon img + span,
            .sf-toolbar-block .sf-toolbar-icon svg + span {
                margin-left: 4px;
            }
            .sf-toolbar-block-config .sf-toolbar-icon .sf-toolbar-value {
                margin-left: 4px;
            }
            .sf-toolbar-block:hover,
            .sf-toolbar-block.hover {
                position: relative;
            }
            .sf-toolbar-block:hover .sf-toolbar-icon,
            .sf-toolbar-block.hover .sf-toolbar-icon {
                background-color: #333;
                position: relative;
                z-index: 7999;
            }
            .sf-toolbar-block:hover .sf-toolbar-info,
            .sf-toolbar-block.hover .sf-toolbar-info {
                display: block;
                padding: 10px;
                max-width: 480px;
                max-height: calc(100vh - 110px);
                word-wrap: break-word;
                overflow-x: visible;
                overflow-y: auto;
            }
            /* Stuff inside FancyBox */
            .fancybox-inner .profiling-button {
                text-align: center;
            }
            .fancybox-inner .profiling-button a {
                display: inline-block;
                margin-top: 1em;
                border-radius: 15px;
                padding: .25em .75em;
                color: #000;
                background-color: #F6D426;
            }
            .fancybox-inner .profiling-button a:hover {
                color: #fff;
                background-color: #000;
            }
            .fancybox-inner .explain {
                padding: 0 40px;
            }
            .fancybox-inner .explain h2 {
                margin: 1em 0 .5em 0;
                border-top: 1px solid #dfdfdf;
                padding-top: 1em;
                font-size: 13px;
                font-weight: bold;
            }
            .fancybox-inner .explain table th,
            .fancybox-inner .explain table td {
                font-size: 12px;
                padding: .166em .5em;
            }
            /* FancyBox nav arrows */
            .fancybox-nav {
                width: 40px;
            }
            .fancybox-prev span {
                left: 0;
            }
            .fancybox-next span {
                right: 0;
            }
            /* Responsive Design */
            .sf-toolbar-icon .sf-toolbar-label,
            .sf-toolbar-icon .sf-toolbar-value {
                display: none;
            }
            @media (min-width: 768px) {
                .sf-toolbar-icon .sf-toolbar-label,
                .sf-toolbar-icon .sf-toolbar-value {
                    display: inline;
                }
                .sf-toolbar-block .sf-toolbar-icon img,
                .sf-toolbar-block .sf-toolbar-icon svg {
                    top: 6px;
                }
                .sf-toolbar-block-time .sf-toolbar-icon svg,
                .sf-toolbar-block-memory .sf-toolbar-icon svg {
                    display: none;
                }
                .sf-toolbar-block-time .sf-toolbar-icon svg + span,
                .sf-toolbar-block-memory .sf-toolbar-icon svg + span {
                    margin-left: 0;
                }
                .sf-toolbar-block .sf-toolbar-icon {
                    padding: 0 10px;
                }
                .sf-toolbar-block-time .sf-toolbar-icon {
                    padding-right: 5px;
                }
                .sf-toolbar-block-memory .sf-toolbar-icon {
                    padding-left: 5px;
                }
            }
            /***** Error Toolbar *****/
            .sf-error-toolbar .sf-toolbarreset {
                background: #1a1a1a;
                color: #f5f5f5;
                font: 13px/36px Arial, sans-serif;
                height: 36px;
                padding: 0 15px;
                text-align: left;
            }
            .sf-error-toolbar .sf-toolbarreset svg {
                height: auto;
            }
            .sf-error-toolbar .sf-toolbarreset a {
                color: #99cdd8;
                margin-left: 5px;
                text-decoration: underline;
            }
            .sf-error-toolbar .sf-toolbarreset a:hover {
                text-decoration: none;
            }
            .sf-error-toolbar .sf-toolbarreset .sf-toolbar-icon {
                float: left;
                padding: 5px 0;
                margin-right: 10px;
            }
            /***** Media query print: Do not print the Toolbar. *****/
            @media print {
                .sf-toolbar {
                    display: none;
                }
            }
        </style>
        <!-- <script src="https://cdn.jsdelivr.net/gh/drvic10k/bootstrap-sortable@2.1.0/Scripts/bootstrap-sortable.js"></script> -->
<?php
    }

    protected function displayProfilingSummary()
    {
        global $start_time;

        $loadTime = $this->profiler[count($this->profiler) - 1]['time'] - $start_time;

        echo '
        <div class="sf-toolbar-block sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M15.1,4.3c-2.1-0.5-4.2-0.5-6.2,0C8.6,4.3,8.2,4.1,8.2,3.8V3.4c0-1.2,1-2.3,2.3-2.3h3c1.2,0,2.3,1,2.3,2.3v0.3C15.8,4.1,15.4,4.3,15.1,4.3z M20.9,14c0,4.9-4,8.9-8.9,8.9s-8.9-4-8.9-8.9s4-8.9,8.9-8.9S20.9,9.1,20.9,14z M16.7,15c0-0.6-0.4-1-1-1H13V8.4c0-0.6-0.4-1-1-1s-1,0.4-1,1v6.2c0,0.6,0.4,1.3,1,1.3h3.7C16.2,16,16.7,15.6,16.7,15z"/>
                    </svg>
                    <span class="sf-toolbar-value">'.$this->getLoadTimeColor($loadTime, false).'</span>
                    <span class="sf-toolbar-label">ms</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <td>Load time</td>
                        <td>'.$this->getLoadTimeColor($loadTime, true).'</td>
                    </tr>
                    <tr>
                        <td>Querying time</td>
                        <td>'.$this->getTotalQueriyingTimeColor(round(1000 * $this->total_query_time)).' ms</td>
                    </tr>
                    <tr>
                        <td>Queries</td>
                        <td>'.$this->getNbQueriesColor(count($this->array_queries)).'</td>
                    </tr>
                    <tr>
                        <td>Memory peak usage</td>
                        <td>'.$this->getPeakMemoryColor($this->profiler[count($this->profiler) - 1]['peak_memory_usage']).' MB</td>
                    </tr>
                    <tr>
                        <td>Included files</td>
                        <td>'.count(get_included_files()).' files - '.$this->getMemoryColor($this->total_filesize).' MB</td>
                    </tr>
                    <tr>
                        <td>thirty bees cache</td>
                        <td>'.$this->getMemoryColor($this->total_cache_size).' MB</td>
                    </tr>
                    <tr>
                        <td><a href="javascript:void(0);" onclick="$(\'.global_vars_detail\').toggle();">Global vars</a></td>
                        <td>'.$this->getMemoryColor($this->total_global_var_size).' MB</td>
                    </tr>';
        foreach ($this->global_var_size as $global => $size) {
            echo '
                <tr class="global_vars_detail" style="display:none">
                    <td>- global $'.$global.'</td>
                    <td>'.$size.'k</td>
                </tr>';
        }
                echo '
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingConfiguration()
    {
        echo '
        <div class="sf-toolbar-block sf-toolbar-block-config sf-toolbar-status-yellow sf-toolbar-block-right">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="90" height="90" viewBox="0 0 90 90" enable-background="new 0 0 90 90" xml:space="preserve">
                        <g fill="none" transform="translate(-123.59 -353.51)">
                        <g transform="translate(157.26 352.89)">
                        <path d="m24.276 10.644c11.732 0 21.276 9.4918 21.276 21.159 0 6.5764-3.034 12.46-7.7806 16.343l7.6445 7.491c6.64-5.833 10.839-14.352 10.839-23.835 0-17.536-14.345-31.802-31.979-31.802-9.704 0-18.406 4.3262-24.276 11.136l7.6525 7.4977c3.9015-4.862 9.8975-7.99 16.624-7.99" fill="#AAAAAA" transform="translate(.47340 .625)"/>
                        </g>
                        <g transform="translate(122.97 353.79)">
                        <path d="m90.08 71.912c-0.468-4.335-2.666-8.471-6.597-12.404l-18.247-18.254c-2.8085-2.8082-5.3657-4.3374-7.6744-4.5874-2.3061-0.2486-4.5539 0.2818-6.7357 1.5913 1.1226-2.4947 1.4955-4.8678 1.1213-7.1152-0.3742-2.246-1.8089-4.6165-4.304-7.1139l-16.564-16.569c-4.739-4.7426-9.091-7.2241-13.054-7.4437-3.96-0.21695-7.999 1.7328-12.118 5.8519-4.1164 4.1178-6.0813 8.1758-5.8949 12.17 0.18646 3.995 2.6208 8.331 7.2989 13.012l13.568 13.573c3.8769-3.8783 3.8769-10.164 0-14.043l-6.5491-6.5517c-2.121-2.1217-3.3229-4.008-3.6019-5.6628-0.2816-1.6534 0.5144-3.418 2.3854-5.2897 1.9331-1.9339 3.7274-2.7607 5.3816-2.4802 1.6528 0.2817 3.5397 1.4828 5.6606 3.6045l15.907 15.913c1.998 1.9987 3.2118 3.807 3.6508 5.43 0.4351 1.623-0.3133 3.4022-2.2465 5.3361l-4.0237 4.0252 6.6444 6.6456 3.6495-3.6508c2.1196-2.1205 4.0091-3.0094 5.662-2.6668 1.6528 0.3426 3.5093 1.5437 5.5667 3.6033l17.593 17.598c1.9344 1.9352 3.0412 3.8241 3.3215 5.6654 0.2816 1.8414-0.484 3.6669-2.2915 5.4764-1.748 1.7474-3.5239 2.4643-5.3354 2.1522-1.8088-0.3109-3.6481-1.4035-5.5204-3.2766l-7.9548-7.9579c-3.9787-3.9802-10.43-3.9802-14.41 0l15.16 15.167c3.9298 3.9313 8.0632 6.0993 12.398 6.5054 4.3371 0.4061 8.5644-1.4511 12.682-5.5702 4.1162-4.1191 5.9423-8.3454 5.4729-12.684" fill="#AAAAAA" transform="translate(.62461 .62447)"/>
                        </g>
                        <g transform="translate(124.72 402.84)">
                        <path d="m37.48 37.369h-37.48v-37.369c5.7891 0 10.481 4.7919 10.481 10.702v15.964h16.516c5.7891 0 10.483 4.7919 10.483 10.702" fill="#AAAAAA" transform="translate(.625 .625)"/>
                        </g>
                        </g>
                    </svg>
                    <span class="sf-toolbar-value">'._TB_VERSION_.'</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <td>thirty bees version</td>
                        <td>'._TB_VERSION_.'</td>
                    </tr>
                    <tr>
                        <td>PrestaShop (emulated) version</td>
                        <td>'._PS_VERSION_.'</td>
                    </tr>
                    <tr>
                        <td>PHP version</td>
                        <td>'.$this->getPhpVersionColor(phpversion()).'</td>
                    </tr>
                    <tr>
                        <td>MySQL version</td>
                        <td>'.$this->getMySQLVersionColor(Db::getInstance()->getVersion()).'</td>
                    </tr>
                    <tr>
                        <td>Memory limit</td>
                        <td>'.ini_get('memory_limit').'</td>
                    </tr>
                    <tr>
                        <td>Max execution time</td>
                        <td>'.ini_get('max_execution_time').'s</td>
                    </tr>
                    <tr>
                        <td>Smarty cache</td>
                        <td>
                            <span style="color:'.(
                                Configuration::get('PS_SMARTY_CACHE')
                                ? ($this->color_green.'">Enabled')
                                : ($this->color_red.'">Disabled')
                            ).'</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Smarty Compilation</td>
                        <td>
                            <span style="color:'.(
                               Configuration::get('PS_SMARTY_FORCE_COMPILE') == 0
                               ? ($this->color_green.'">Never recompile')
                               : (
                                   Configuration::get('PS_SMARTY_FORCE_COMPILE') == 1
                                   ? ($this->color_orange.'">Auto')
                                   : ($this->color_red.'">Force compile')
                                )
                            ).'</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingRun()
    {
        global $start_time;

        echo '
        <div class="sf-toolbar-block sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M13,3v18c0,1.1-0.9,2-2,2s-2-0.9-2-2V3c0-1.1,0.9-2,2-2S13,1.9,13,3z M23.2,4.6l-1.8-1.4C21.2,2.9,20.8,3,20.4,3h-1.3H14v2.1V8h5.1h1.3c0.4,0,0.8-0.3,1.1-0.5l1.8-1.6C23.6,5.6,23.6,4.9,23.2,4.6z M19.5,9.4C19.2,9.1,18.8,9,18.4,9h-0.3H14v2.6V14h4.1h0.3c0.4,0,0.8-0.1,1.1-0.3l1.8-1.5c0.4-0.3,0.4-0.9,0-1.3L19.5,9.4z M3.5,7C3.1,7,2.8,7,2.5,7.3L0.7,8.8c-0.4,0.3-0.4,0.9,0,1.3l1.8,1.6C2.8,11.9,3.1,12,3.5,12h0.3H8V9.4V7H3.9H3.5z"/>
                    </svg>
                    <span class="sf-toolbar-value">Controller</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <th>&nbsp;</th>
                        <th class="center">Time</th>
                        <th class="center">Cumulated Time</th>
                        <th class="center">Memory Usage</th>
                        <th class="center">Memory Peak Usage</th>
                    </tr>';
        $last = array('time' => $start_time, 'memory_usage' => 0);
        foreach ($this->profiler as $row) {
            if ($row['block'] == 'checkAccess' && $row['time'] == $last['time']) {
                continue;
            }
            echo '
                    <tr>
                        <td>'.$row['block'].'</td>
                        <td class="center">'.$this->getLoadTimeColor($row['time'] - $last['time']).' ms</td>
                        <td class="center">'.$this->getLoadTimeColor($row['time'] - $start_time).' ms</td>
                        <td class="center">'.$this->getMemoryColor($row['memory_usage'] - $last['memory_usage']).' MB</td>
                        <td class="center">'.$this->getMemoryColor($row['peak_memory_usage']).' MB</td>
                    </tr>';
            $last = $row;
        }
        echo '
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingHooks()
    {
        $count_hooks = count($this->hooks_perfs);
        $label = $count_hooks == 1 ? '1 hook' : (int)$count_hooks.' hooks';

        echo '
        <div class="sf-toolbar-block sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M20.1,1H3.9C2.3,1,1,2.3,1,3.9v16.3C1,21.7,2.3,23,3.9,23h16.3c1.6,0,2.9-1.3,2.9-2.9V3.9C23,2.3,21.7,1,20.1,1z M21,20.1c0,0.5-0.4,0.9-0.9,0.9H3.9C3.4,21,3,20.6,3,20.1V3.9C3,3.4,3.4,3,3.9,3h16.3C20.6,3,21,3.4,21,3.9V20.1z M5,5h14v3H5V5z M5,10h3v9H5V10z M10,10h9v9h-9V10z"/>
                    </svg>
                    <span class="sf-toolbar-value">'.$label.'</span>
                    <span class="sf-toolbar-label">in</span>
                    <span class="sf-toolbar-value">'.$this->getLoadTimeColor($this->total_modules_time).'</span>
                    <span class="sf-toolbar-label">ms</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <th>Hook</th>
                        <th class="center">Time</th>
                        <th class="center">Memory Usage</th>
                    </tr>';
        foreach ($this->hooks_perfs as $hook => $hooks_perfs) {
            echo '
                    <tr>
                        <td>
                            <a href="javascript:void(0);" onclick="$(\'.'.$hook.'_modules_details\').toggle();">'.$hook.'</a>
                        </td>
                        <td class="center">
                            '.$this->getLoadTimeColor($hooks_perfs['time']).' ms
                        </td>
                        <td class="center">
                            '.$this->getMemoryColor($hooks_perfs['memory']).' MB
                        </td>
                    </tr>';
            foreach ($hooks_perfs['modules'] as $module => $perfs) {
                echo '
                    <tr class="'.$hook.'_modules_details" style="background-color:#EFEFEF;display:none">
                        <td>
                            =&gt; '.$module.'
                        </td>
                        <td class="center">
                            '.$this->getLoadTimeColor($perfs['time']).' ms
                        </td>
                        <td class="center">
                            '.$this->getMemoryColor($perfs['memory']).' MB
                        </td>
                    </tr>';
            }
        }
        echo '
                    <tr>
                        <th><strong>'. $label .'</strong></th>
                        <th class="center">'.$this->getLoadTimeColor($this->total_modules_time).' ms</th>
                        <th class="center">'.$this->getMemoryColor($this->total_modules_memory).' MB</th>
                    </tr>
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingModules()
    {
        $count_modules = count($this->modules_perfs);
        $label = $count_modules == 1 ? '1 module' : (int)$count_modules.' modules';

        echo '
        <div class="sf-toolbar-block sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAA" d="M2.26 6.09l9.06-4.67a1.49 1.49 0 0 1 1.37 0l9.06 4.67a1.49 1.49 0 0 1 0 2.65l-9.06 4.67a1.49 1.49 0 0 1-1.37 0L2.26 8.74a1.49 1.49 0 0 1 0-2.65zM20.55 11L12 15.39 3.45 11a1.36 1.36 0 0 0-1.25 2.42l9.17 4.73a1.36 1.36 0 0 0 1.25 0l9.17-4.73A1.36 1.36 0 0 0 20.55 11zm0 4.47L12 19.86l-8.55-4.41a1.36 1.36 0 0 0-1.25 2.42l9.17 4.73a1.36 1.36 0 0 0 1.25 0l9.17-4.73a1.36 1.36 0 0 0-1.25-2.42z"/>
                    </svg>
                    <span class="sf-toolbar-value">'.$label.'</span>
                    <span class="sf-toolbar-label">in</span>
                    <span class="sf-toolbar-value">'.$this->getLoadTimeColor($this->total_modules_time).'</span>
                    <span class="sf-toolbar-label">ms</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <th>Module</th>
                        <th class="center">Time</th>
                        <th class="center">Memory Usage</th>
                    </tr>';
        foreach ($this->modules_perfs as $module => $modules_perfs) {
            echo '
                    <tr>
                        <td>
                            <a href="javascript:void(0);" onclick="$(\'.'.$module.'_hooks_details\').toggle();">'.$module.'</a>
                        </td>
                        <td class="center">
                            '.$this->getLoadTimeColor($modules_perfs['time']).' ms
                        </td>
                        <td class="center">
                            '.$this->getMemoryColor($modules_perfs['memory']).' MB
                        </td>
                    </tr>';
            foreach ($modules_perfs['methods'] as $hook => $perfs) {
                echo '
                    <tr class="'.$module.'_hooks_details" style="background-color:#EFEFEF;display:none">
                        <td>
                            =&gt; '.$hook.'
                        </td>
                        <td class="center">
                            '.$this->getLoadTimeColor($perfs['time']).' ms
                        </td>
                        <td class="center">
                            '.$this->getMemoryColor($perfs['memory']).' MB
                        </td>
                    </tr>';
            }
        }
        echo '
                    <tr>
                        <th><strong>'.$label.'</strong></th>
                        <th class="center">'.$this->getLoadTimeColor($this->total_modules_time).' ms</th>
                        <th class="center">'.$this->getMemoryColor($this->total_modules_memory).' MB</th>
                    </tr>
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingStopwatch()
    {
        echo '
        <div class="sf-toolbar-block sf-toolbar-database sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M5,8h14c1.7,0,3-1.3,3-3s-1.3-3-3-3H5C3.3,2,2,3.3,2,5S3.3,8,5,8z M18,3.6c0.8,0,1.5,0.7,1.5,1.5S18.8,6.6,18,6.6s-1.5-0.7-1.5-1.5S17.2,3.6,18,3.6z M19,9H5c-1.7,0-3,1.3-3,3s1.3,3,3,3h14c1.7,0,3-1.3,3-3S20.7,9,19,9z M18,13.6
                    c-0.8,0-1.5-0.7-1.5-1.5s0.7-1.5,1.5-1.5s1.5,0.7,1.5,1.5S18.8,13.6,18,13.6z M19,16H5c-1.7,0-3,1.3-3,3s1.3,3,3,3h14c1.7,0,3-1.3,3-3S20.7,16,19,16z M18,20.6c-0.8,0-1.5-0.7-1.5-1.5s0.7-1.5,1.5-1.5s1.5,0.7,1.5,1.5S18.8,20.6,18,20.6z"></path>
                    </svg>
                    <span class="sf-toolbar-value">'.$this->getNbQueriesColor(count($this->array_queries)).' queries</span>
                    <span class="sf-toolbar-label">in</span>
                    <span class="sf-toolbar-value">'.$this->getTotalQueriyingTimeColor(round(1000 * $this->total_query_time)).'</span>
                    <span class="sf-toolbar-label">ms</span>
                </div>
            </a>
            <div class="sf-toolbar-info"><div class="sf-toolbar-info-group">
                <div class="search">
                    <input
                        type="search"
                        name="database-queries-search"
                        id="database-queries-search"
                        placeholder="Search queries"
                        aria-label="Search queries"
                        />
                </div>
                <table class="profiling-table sortable">
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th data-defaultsort="disabled" class="center">Details</th>
                            <th data-defaultsort="desc">Time (ms)</th>
                            <th>Rows</th>
                            <th>Filesort</th>
                            <th>Group By</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($this->array_queries as $i => $data) {
            $callstack = implode('<br />', $data['stack']);
            $callstack_md5 = md5($callstack);
            $sane_time = $data['time'] * 1000;
            $rounded_sane_time = round($sane_time, 1);
            $popup_info = 'Time: '.$rounded_sane_time.' ms, Rows: '.(int)$data['rows'].', File: '.htmlspecialchars($data['location']);
            echo '
                        <tr>
                            <td>'.strtok($data['query'], ' ').'</td>
                            <td class="center details nowrap" data-value="">
                                <a
                                    href="#query'.$i.'"
                                    class="queryToggler"
                                    title="Query ('.$popup_info.')"
                                    rel="queries"
                                    >
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                                        <path fill="#FFFFFF" d="M11.61,0.357c-4.4,0-7.98,3.58-7.98,7.98c0,1.696,0.526,3.308,1.524,4.679l-4.374,4.477c-0.238,0.238-0.369,0.554-0.369,0.891c0,0.336,0.131,0.653,0.369,0.891c0.238,0.238,0.554,0.369,0.891,0.369c0.336,0,0.653-0.131,0.893-0.371l4.372-4.475c1.369,0.996,2.98,1.521,4.674,1.521c4.4,0,7.98-3.58,7.98-7.98S16.01,0.357,11.61,0.357z M17.07,8.337c0,3.011-2.449,5.46-5.46,5.46c-3.011,0-5.46-2.449-5.46-5.46s2.449-5.46,5.46-5.46C14.62,2.877,17.07,5.326,17.07,8.337z"/>
                                    </svg>
                                </a>
                                <div style="display: none">
                                    <div id="query'.$i.'">
                                        <div class="query ace-editor" style="width: 90vw; height: 300px;">'.
                                            preg_replace(
                                                "/(^[\s]*)/m",
                                                "",
                                                htmlspecialchars($data['query'], ENT_NOQUOTES, 'utf-8', false)
                                            )
                                        .'</div>';
            if (!empty($data['explain'])) {
                echo '
                                        <div class="explain">
                                            <h2>Explain:</h2>
                                            <table>
                                                <thead>
                                                    <th>Select type</th>
                                                    <th>Table</th>
                                                    <th>Type</th>
                                                    <th>Possible keys</th>
                                                    <th>Key</th>
                                                    <th>Rows</th>
                                                    <th>Filtered</th>
                                                    <th>Extra</th>
                                                </thead>
                                                <tbody>';
                foreach ($data['explain'] as $explain) {
                    echo '
                                                    <tr>
                                                        <td>'.$explain['select_type'].'</td>
                                                        <td>'.$explain['table'].'</td>
                                                        <td>'.$explain['type'].'</td>
                                                        <td>'.$explain['possible_keys'].'</td>
                                                        <td>'.$explain['key'].' ('.$explain['key_len'].')</td>
                                                        <td>'.$explain['rows'].'</td>
                                                        <td>'.$explain['filtered'].'</td>
                                                        <td>'.$explain['Extra'].'</td>
                                                    </tr>';
                }
                echo '
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>';
            }
            echo '
                            </td>
                            <td data-value="'.($sane_time).'" class="center">
                                <span '.$this->getTimeColor($sane_time).'>'.
                                    ($rounded_sane_time < 0.1 ? '< 1' : $rounded_sane_time)
                                .'</span>
                            </td>
                            <td class="center">'.(int)$data['rows'].'</td>
                            <td class="center">'.(
                                $data['filesort']
                                ? ('<span style="color:'.$this->color_red.'">Yes</span>')
                                : ('<span style="color:'.$this->color_green.'">No</span>')
                            ).'</td>
                            <td class="center">'.(
                                $data['group_by']
                                ? ('<span style="color:'.$this->color_red.'">Yes</span>')
                                : ('<span style="color:'.$this->color_green.'">No</span>')
                            ).'</td>
                            <td data-value="'.$data['location'].'">
                                <a href="javascript:void(0);" onclick="$(\'#callstack_'.$callstack_md5.'\').toggle();">'.$data['location'].'</a>
                                <div id="callstack_'.$callstack_md5.'" style="display:none">'.implode('<br />', $data['stack']).'</div>
                            </td>
                        </tr>';
        }
        echo '
                    </tbody>
                </table>
            </div></div>
        </div>';
    }

    protected function displayProfilingDoubles()
    {
        echo '<div class="row">
		<h2><a name="doubles">Doubles</a></h2>
			<table class="profiling-table">';
        foreach (Db::getInstance()->uniqQueries as $q => $nb) {
            if ($nb > 1) {
                echo '<tr><td><span '.$this->getQueryColor($nb).'>'.$nb.'</span></td><td class="pre"><pre>'.$q.'</pre></td></tr>';
            }
        }
        echo '</table>
		</div>';
    }

    protected function displayProfilingTableStress()
    {
        echo '<div class="row">
		<h2><a name="tables">Tables stress</a></h2>
		<table class="profiling-table">';
        foreach (Db::getInstance()->tables as $table => $nb) {
            echo '<tr><td><span '.$this->getTableColor($nb).'>'.$nb.'</span> '.$table.'</td></tr>';
        }
        echo '</table>
		</div>';
    }

    protected function displayProfilingObjectModel()
    {
        echo '
        <div class="sf-toolbar-block sf-toolbar-database sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M15.8,6.4h-1.1c0,0-0.1,0.1-0.1,0l0.8-0.7c0.5-0.5,0.5-1.3,0-1.9l-1.4-1.4c-0.5-0.5-1.4-0.5-1.9,0l-0.6,0.8c-0.1,0,0,0,0-0.1V2.1c0-0.8-1-1.4-1.8-1.4h-2c-0.8,0-1.9,0.6-1.9,1.4v1.1c0,0,0.1,0.1,0.1,0.1L5.1,2.5c-0.5-0.5-1.3-0.5-1.9,0L1.8,3.9c-0.5,0.5-0.5,1.4,0,1.9l0.8,0.6c0,0.1,0,0-0.1,0H1.4C0.7,6.4,0,7.5,0,8.2v2c0,0.8,0.7,1.8,1.4,1.8h1.2c0,0,0.1-0.1,0.1-0.1l-0.8,0.7c-0.5,0.5-0.5,1.3,0,1.9L3.3,16c0.5,0.5,1.4,0.5,1.9,0l0.6-0.8c0.1,0-0.1,0-0.1,0.1v1.2c0,0.8,1.1,1.4,1.9,1.4h2c0.8,0,1.8-0.6,1.8-1.4v-1.2c0,0-0.1-0.1,0-0.1l0.7,0.8c0.5,0.5,1.3,0.5,1.9,0l1.4-1.4c0.5-0.5,0.5-1.4,0-1.9L14.6,12c0-0.1,0,0.1,0.1,0.1h1.1c0.8,0,1.3-1.1,1.3-1.8v-2C17.1,7.5,16.5,6.4,15.8,6.4z M8.6,13c-2.1,0-3.8-1.7-3.8-3.8c0-2.1,1.7-3.8,3.8-3.8c2.1,0,3.8,1.7,3.8,3.8C12.3,11.3,10.6,13,8.6,13z"/>
                        <path fill="#AAAAAA" d="M22.3,15.6l-0.6,0.2c0,0,0,0.1,0,0l0.3-0.5c0.2-0.4,0-0.8-0.4-1l-1-0.4c-0.4-0.2-0.8,0-1,0.4l-0.1,0.5c0,0,0,0,0,0l-0.2-0.6c-0.2-0.4-0.8-0.5-1.2-0.3l-1.1,0.4c-0.4,0.2-0.8,0.7-0.7,1.1l0.2,0.6c0,0,0.1,0,0.1,0l-0.5-0.3c-0.4-0.2-0.8,0-1,0.4l-0.4,1c-0.2,0.4,0,0.8,0.4,1l0.5,0.1c0,0,0,0,0,0l-0.6,0.2c-0.4,0.2-0.5,0.8-0.4,1.2l0.4,1.1c0.2,0.4,0.7,0.8,1.1,0.7l0.6-0.2c0,0,0-0.1,0,0l-0.3,0.5c-0.2,0.4,0,0.8,0.4,1l1,0.4c0.4,0.2,0.8,0,1-0.4l0.1-0.5c0,0,0,0,0,0l0.2,0.6c0.2,0.4,0.9,0.5,1.2,0.3l1.1-0.4c0.4-0.2,0.8-0.7,0.6-1.1l-0.2-0.6c0,0-0.1,0,0,0l0.5,0.3c0.4,0.2,0.8,0,1-0.4l0.4-1c0.2-0.4,0-0.8-0.4-1l-0.5-0.1c0,0,0,0,0,0l0.6-0.2c0.4-0.2,0.5-0.8,0.3-1.2l-0.4-1.1C23.2,15.9,22.7,15.5,22.3,15.6z M19.9,20.5c-1.1,0.4-2.3-0.1-2.7-1.2c-0.4-1.1,0.1-2.3,1.2-2.7c1.1-0.4,2.3,0.1,2.7,1.2C21.5,18.9,21,20.1,19.9,20.5z"/>
                    </svg>
                    <span class="sf-toolbar-value">'.count(ObjectModel::$debug_list).'</span>
                    <span class="sf-toolbar-label">ObjectModel instances</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <th>Name</th>
                        <th class="center">Instances</th>
                        <th>Source</th>
                    </tr>';
        foreach (ObjectModel::$debug_list as $class => $info) {
            echo '
                    <tr>
                        <td>'.$class.'</td>
                        <td class="center">
                            <span '.$this->getObjectModelColor(count($info)).'>'.count($info).'</span>
                        </td>
                        <td>';
            foreach ($info as $trace) {
                echo str_replace(array(_PS_ROOT_DIR_, '\\'), array('', '/'), $trace['file']).' ['.$trace['line'].']<br />';
            }
            echo '
                        </td>
                    </tr>';
        }
        echo '
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingFiles()
    {
        $count = 0;
        $rows = '';
        foreach (get_included_files() as $file) {
            $file = str_replace('\\', '/', str_replace(_PS_ROOT_DIR_, '', $file));
            if (strpos($file, '/tools/profiling/') === 0) {
                continue;
            }
            $rows .= '
                    <tr>
                        <td>'.(++$count).'</td>
                        <td>'.$file.'</td>
                    </tr>';
        }

        echo '
        <div class="sf-toolbar-block sf-toolbar-database sf-toolbar-status-normal">
            <a href="#">
                <div class="sf-toolbar-icon">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                        <path fill="#AAAAAA" d="M20.5,4H18V2.5C18,1.7,17.3,1,16.5,1h-9C6.7,1,6,1.7,6,2.5V4H3.5C2.7,4,2,4.7,2,5.5v16C2,22.3,2.7,23,3.5,23h17c0.8,0,1.5-0.7,1.5-1.5v-16C22,4.7,21.3,4,20.5,4z M9,4h6v1H9V4z M19,20H5V7h1.1c0.2,0.6,0.8,1,1.4,1h9c0.7,0,1.2-0.4,1.4-1H19V20z M17,11c0,0.6-0.4,1-1,1H8c-0.6,0-1-0.4-1-1s0.4-1,1-1h8C16.6,10,17,10.4,17,11z M17,14c0,0.6-0.4,1-1,1H8c-0.6,0-1-0.4-1-1s0.4-1,1-1h8C16.6,13,17,13.4,17,14z M13,17c0,0.6-0.4,1-1,1H8c-0.6,0-1-0.4-1-1s0.4-1,1-1h4C12.6,16,13,16.4,13,17z"/>
                    </svg>
                    <span class="sf-toolbar-value">'.$count.'</span>
                    <span class="sf-toolbar-label">Included Files</span>
                </div>
            </a>
            <div class="sf-toolbar-info">
                <table class="profiling-table">
                    <tr>
                        <th>#</th>
                        <th>Filename</th>
                    </tr>
                    '.$rows.'
                </table>
            </div>
        </div>';
    }

    protected function displayProfilingJavascript()
    {
        // Heavily based on Symfony's toolbar code
        // Copyright (c) 2004-2018 Fabien Potencier
?>
        <script>
            $(document).ready(function() {
                // Handle toolbar-info position
                var toolbarBlocks = [].slice.call(
                    document.getElementById('sfToolbarMainContent').querySelectorAll('.sf-toolbar-block')
                );
                var i;
                for (i = 0; i < toolbarBlocks.length; ++i) {
                    toolbarBlocks[i].onmouseover = function () {
                        var toolbarInfo = this.querySelectorAll('.sf-toolbar-info')[0];
                        var pageWidth = document.body.clientWidth;
                        var elementWidth = toolbarInfo.offsetWidth;
                        var leftValue = (elementWidth + this.offsetLeft) - pageWidth;
                        var rightValue = (elementWidth + (pageWidth - this.offsetLeft)) - pageWidth;
                        /* Reset right and left value, useful on window resize */
                        toolbarInfo.style.right = '';
                        toolbarInfo.style.left = '';
                        if (elementWidth >= pageWidth) {
                            if (leftValue <= this.offsetLeft) {
                                toolbarInfo.style.left = "-" + leftValue.toString() + "px";
                            }
                            else {
                                toolbarInfo.style.left = "-" + this.offsetLeft.toString() + "px";
                            }
                            toolbarInfo.style.maxWidth = (
                                document.getElementById('sfToolbarMainContent').offsetWidth - 20
                            ).toString() + "px";
                            toolbarInfo.style.overflowX = "auto";
                        }
                        else if (leftValue > 0 && rightValue > 0) {
                            toolbarInfo.style.right = (rightValue * -1) + 'px';
                        } else if (leftValue < 0) {
                            toolbarInfo.style.left = 0;
                        } else {
                            toolbarInfo.style.right = '0px';
                        }
                    };
                }

                // If in the backoffice, detect when footer changes and make room if necessary
                if ($('body').hasClass('ps_back-office')) {
                    var $footer = document.getElementById('footer');
                    if ($footer !== null && window.MutationObserver) {
                        var mut = new MutationObserver(function(mutations, mut) {
                            if (!$('#footer').hasClass('hide')) {
                                $('#profiling-bar, #sfToolbarMainContent').css('bottom', '50px');
                            }
                            else {
                                $('#profiling-bar, #sfToolbarMainContent').css('bottom', '');
                            }
                        });
                        mut.observe($footer, {
                            'attributes': true
                        });
                    }
                }

                // Toolbar toggling
                $('#sfToolbarHideButton').on('click', function (event) {
                    event.preventDefault();
                    var p = this.parentNode;
                    p.style.display = 'none';
                    (p.previousElementSibling || p.previousSibling).style.display = 'none';
                    document.getElementById('profiling-bar').style.display = 'block';
                });
                $('#sfToolbarMiniToggler').on('click', function (event) {
                    event.preventDefault();
                    var elem = this.parentNode;
                    if (elem.style.display == 'none') {
                        document.getElementById('sfToolbarMainContent').style.display = 'none';
                        document.getElementById('sfToolbarClearer').style.display = 'none';
                        elem.style.display = 'block';
                    } else {
                        document.getElementById('sfToolbarMainContent').style.display = 'block';
                        document.getElementById('sfToolbarClearer').style.display = 'block';
                        elem.style.display = 'none'
                    }
                });

                // Database queries handling
                var detailsRows = $('.profiling-table td.details');
                var editorInstances = [];
                // Database queries syntax highlighting
                detailsRows.each(function(i) {
                    var query = $(this).find('.query');
                    if (query.length === 1) {
                        var editor = ace.edit(query[0]);
                        editor.setTheme('ace/theme/xcode');
                        editor.session.setMode('ace/mode/mysql');
                        editor.setReadOnly(true);
                        editor.setOptions({
                            minLines: 1,
                            maxLines: 500,
                            fontSize: '13px'
                        });
                        editorInstances[i] = editor;
                    }
                });
                // Database queries FancyBox
                detailsRows.find('.queryToggler').fancybox();
                // Database queries search
                var dbqs = $('#database-queries-search');
                dbqs.on('keyup', function() {
                    var val = dbqs.val();
                    console.log(val);
                    if (val.length > 1) {
                        detailsRows.each(function(i) {
                            var t = $(this);
                            var tr = t.parent('tr');
                            editorInstances[i].clearSelection();
                            editorInstances[i].gotoLine(1,1);
                            if (editorInstances[i].find(val, { start: 0 }) !== undefined) {
                                tr.css('opacity', '1');
                            } else {
                                tr.css('opacity', '0.2');
                            }
                        });
                    }
                    else {
                        detailsRows.each(function() {
                            $(this).parent('tr').css('opacity', '1');
                        });
                    }
                });
            });
        </script>
<?php
    }

    public function displayProfiling()
    {
        if (!empty($this->redirect_after)) {
            echo '
            <html>
                <head>
                    <meta charset="utf-8" />
                    <title>thirty bees - Caught redirection</title>
                    <script src="'._PS_JS_DIR_.'jquery/jquery-'._PS_JQUERY_VERSION_.'.min.js"></script>
                    <script src="'._PS_JS_DIR_.'ace/ace.js"></script>
                    <script src="'._PS_JS_DIR_.'ace/mode-mysql.js"></script>
                    <script src="'._PS_JS_DIR_.'jquery/plugins/fancybox/jquery.fancybox.js"></script>
                    <link href="'._PS_JS_DIR_.'jquery/plugins/fancybox/jquery.fancybox.css" rel="stylesheet" type="text/css"/>
                </head>
                <body>
                    <h2>
                        Caught redirection to
                        <a href="'.htmlspecialchars($this->redirect_after).'">
                            '.htmlspecialchars($this->redirect_after).'
                        </a>
                    </h2>';
        } else {
            // Add scripts
            $this->context->controller->addJquery();
            $this->context->controller->addJS(_PS_JS_DIR_.'ace/ace.js');
            $this->context->controller->addJS(_PS_JS_DIR_.'ace/mode-mysql.js');
            $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/fancybox/jquery.fancybox.js');
            $this->context->controller->addCSS(_PS_JS_DIR_.'jquery/plugins/fancybox/jquery.fancybox.css');
            // Call original display method
            $this->display();
            $this->profiler[] = $this->stamp('display');
        }

        // Process all profiling data
        $this->processProfilingData();

        // Add some specific style for profiling information
        $this->displayProfilingStyle();

        // Start of the toolbar's HTML code
        echo '
        <!-- START of thirty bees profiling toolbar -->
        <div id="profiling-bar" class="sf-minitoolbar" data-no-turbolink>
            <a href="#" title="Show thirty bees toolbar" tabindex="-1" id="sfToolbarMiniToggler" accesskey="D">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="90" height="90" viewBox="0 0 90 90" enable-background="new 0 0 90 90" xml:space="preserve">
                    <g fill="none" transform="translate(-123.59 -353.51)">
                    <g transform="translate(157.26 352.89)">
                    <path d="m24.276 10.644c11.732 0 21.276 9.4918 21.276 21.159 0 6.5764-3.034 12.46-7.7806 16.343l7.6445 7.491c6.64-5.833 10.839-14.352 10.839-23.835 0-17.536-14.345-31.802-31.979-31.802-9.704 0-18.406 4.3262-24.276 11.136l7.6525 7.4977c3.9015-4.862 9.8975-7.99 16.624-7.99" fill="#AAAAAA" transform="translate(.47340 .625)"/>
                    </g>
                    <g transform="translate(122.97 353.79)">
                    <path d="m90.08 71.912c-0.468-4.335-2.666-8.471-6.597-12.404l-18.247-18.254c-2.8085-2.8082-5.3657-4.3374-7.6744-4.5874-2.3061-0.2486-4.5539 0.2818-6.7357 1.5913 1.1226-2.4947 1.4955-4.8678 1.1213-7.1152-0.3742-2.246-1.8089-4.6165-4.304-7.1139l-16.564-16.569c-4.739-4.7426-9.091-7.2241-13.054-7.4437-3.96-0.21695-7.999 1.7328-12.118 5.8519-4.1164 4.1178-6.0813 8.1758-5.8949 12.17 0.18646 3.995 2.6208 8.331 7.2989 13.012l13.568 13.573c3.8769-3.8783 3.8769-10.164 0-14.043l-6.5491-6.5517c-2.121-2.1217-3.3229-4.008-3.6019-5.6628-0.2816-1.6534 0.5144-3.418 2.3854-5.2897 1.9331-1.9339 3.7274-2.7607 5.3816-2.4802 1.6528 0.2817 3.5397 1.4828 5.6606 3.6045l15.907 15.913c1.998 1.9987 3.2118 3.807 3.6508 5.43 0.4351 1.623-0.3133 3.4022-2.2465 5.3361l-4.0237 4.0252 6.6444 6.6456 3.6495-3.6508c2.1196-2.1205 4.0091-3.0094 5.662-2.6668 1.6528 0.3426 3.5093 1.5437 5.5667 3.6033l17.593 17.598c1.9344 1.9352 3.0412 3.8241 3.3215 5.6654 0.2816 1.8414-0.484 3.6669-2.2915 5.4764-1.748 1.7474-3.5239 2.4643-5.3354 2.1522-1.8088-0.3109-3.6481-1.4035-5.5204-3.2766l-7.9548-7.9579c-3.9787-3.9802-10.43-3.9802-14.41 0l15.16 15.167c3.9298 3.9313 8.0632 6.0993 12.398 6.5054 4.3371 0.4061 8.5644-1.4511 12.682-5.5702 4.1162-4.1191 5.9423-8.3454 5.4729-12.684" fill="#AAAAAA" transform="translate(.62461 .62447)"/>
                    </g>
                    <g transform="translate(124.72 402.84)">
                    <path d="m37.48 37.369h-37.48v-37.369c5.7891 0 10.481 4.7919 10.481 10.702v15.964h16.516c5.7891 0 10.483 4.7919 10.483 10.702" fill="#AAAAAA" transform="translate(.625 .625)"/>
                    </g>
                    </g>
                </svg>
            </a>
        </div>
        <div id="sfToolbarClearer" class="sf-toolbar-clearer"></div>
        <div id="sfToolbarMainContent" class="sf-toolbarreset clear-fix" data-no-turbolink>';

        $this->displayProfilingSummary();
        $this->displayProfilingConfiguration();
        $this->displayProfilingRun();
        $this->displayProfilingHooks();
        $this->displayProfilingModules();

        $this->displayProfilingStopwatch();
        // $this->displayProfilingDoubles();
        // $this->displayProfilingTableStress();
        if (isset(ObjectModel::$debug_list)) {
            $this->displayProfilingObjectModel();
        }
        $this->displayProfilingFiles();

        // Toolbar close button
        echo '
            <a class="hide-button" id="sfToolbarHideButton" title="Close Toolbar" tabindex="-1" accesskey="D">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                    <path fill="#AAAAAA" d="M21.1,18.3c0.8,0.8,0.8,2,0,2.8c-0.4,0.4-0.9,0.6-1.4,0.6s-1-0.2-1.4-0.6L12,14.8l-6.3,6.3c-0.4,0.4-0.9,0.6-1.4,0.6s-1-0.2-1.4-0.6c-0.8-0.8-0.8-2,0-2.8L9.2,12L2.9,5.7c-0.8-0.8-0.8-2,0-2.8c0.8-0.8,2-0.8,2.8,0L12,9.2l6.3-6.3c0.8-0.8,2-0.8,2.8,0c0.8,0.8,0.8,2,0,2.8L14.8,12L21.1,18.3z"></path>
                </svg>
            </a>';

        $this->displayProfilingJavascript();

        // End of the toolbar's HTML code
        echo '
        </div>
        <!-- END of thirty bees profiling toolbar -->';

        if (!empty($this->redirect_after)) {
            echo '</div></body></html>';
        }
    }
}

function prestashop_querytime_sort($a, $b)
{
    if ($a['time'] == $b['time']) {
        return 0;
    }
    return ($a['time'] > $b['time']) ? -1 : 1;
}
