<?php
/** © 2019 by Open Knowledge Belgium vzw/asbl
 * CompositionRequest Class.
 *
 * @author Bert Marcelis
 */
namespace Irail\Http\Requests;

class CompositionRequest extends Request
{
    protected $id;
    protected $data;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('id', '');
        parent::setGetVar('data', '');
        parent::processRequiredVars(['id']);
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return boolean True if raw source data should be returned as well, for people who need more data.
     */
    public function getShouldReturnRawData()
    {
        return $this->data == 'all';
    }
}
