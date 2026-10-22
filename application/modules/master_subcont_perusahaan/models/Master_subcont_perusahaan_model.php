<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Budget Rutin"
 */

class Master_subcont_perusahaan_model extends BF_Model
{
    protected $ENABLE_ADD;
    protected $ENABLE_MANAGE;
    protected $ENABLE_VIEW;
    protected $ENABLE_DELETE;

    protected $gl;

    public function __construct()
    {
        $this->ENABLE_ADD     = 'Master_Subcont_Perusahaan.Add';
        $this->ENABLE_MANAGE  = 'Master_Subcont_Perusahaan.Manage';
        $this->ENABLE_VIEW    = 'Master_Subcont_Perusahaan.View';
        $this->ENABLE_DELETE  = 'Master_Subcont_Perusahaan.Delete';

        $this->gl = $this->load->database('gl_sendigs', true);
    }

    public function get_coa_all()
    {
        $this->gl->select('a.no_perkiraan, a.nama as nm_coa');
        $this->gl->from('coa_master a');
        $get_coa = $this->gl->get();

        return $get_coa->result();
    }
}
