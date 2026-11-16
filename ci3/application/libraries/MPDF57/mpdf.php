<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * mPDF Compatibility Adapter for PHP 8.5
 * Bridges legacy MPDF57 positional parameters & methods to \Mpdf\Mpdf (mPDF 8.x)
 */

if (!class_exists(\Mpdf\Mpdf::class)) {
    if (file_exists(FCPATH . 'vendor/autoload.php')) {
        require_once FCPATH . 'vendor/autoload.php';
    } elseif (file_exists(APPPATH . 'vendor/autoload.php')) {
        require_once APPPATH . 'vendor/autoload.php';
    }
}

if (!class_exists('mPDF', false)) {
    #[\AllowDynamicProperties]
    class mPDF extends \Mpdf\Mpdf
    {
        public function __construct(
            $mode = '',
            $format = 'A4',
            $default_font_size = 0,
            $default_font = '',
            $margin_left = 15,
            $margin_right = 15,
            $margin_top = 16,
            $margin_bottom = 16,
            $margin_header = 9,
            $margin_footer = 9,
            $orientation = 'P'
        ) {
            $config = [
                'mode'              => (!empty($mode)) ? $mode : 'utf-8',
                'format'            => (!empty($format)) ? $format : 'A4',
                'default_font_size' => (!empty($default_font_size)) ? (int)$default_font_size : 0,
                'default_font'      => (!empty($default_font)) ? $default_font : '',
                'margin_left'       => ($margin_left !== '' && $margin_left !== null) ? (float)$margin_left : 15,
                'margin_right'      => ($margin_right !== '' && $margin_right !== null) ? (float)$margin_right : 15,
                'margin_top'        => ($margin_top !== '' && $margin_top !== null) ? (float)$margin_top : 16,
                'margin_bottom'     => ($margin_bottom !== '' && $margin_bottom !== null) ? (float)$margin_bottom : 16,
                'margin_header'     => ($margin_header !== '' && $margin_header !== null) ? (float)$margin_header : 9,
                'margin_footer'     => ($margin_footer !== '' && $margin_footer !== null) ? (float)$margin_footer : 9,
                'orientation'       => (!empty($orientation)) ? $orientation : 'P',
                'tempDir'           => sys_get_temp_dir() . '/mpdf',
            ];

            if (!is_dir($config['tempDir'])) {
                @mkdir($config['tempDir'], 0777, true);
            }

            parent::__construct($config);
        }

        /**
         * Compatibility no-op for mPDF 5.x SetImportUse()
         */
        public function SetImportUse()
        {
            return $this;
        }

        /**
         * Compatibility no-op for mPDF 5.x useOnlyCoreFonts
         */
        public function useOnlyCoreFonts()
        {
            return $this;
        }
    }
}
