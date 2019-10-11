<?php
/** © 2019 by Open Knowledge Belgium vzw/asbl
 * CompositionRequest Class.
 *
 * @author Bert Marcelis
 */
include_once 'Request.php';
include_once 'data/NMBS/stations.php';

class CompositionRequest extends Request
{
    protected $id;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('id', '');
        parent::processRequiredVars(['id']);
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
