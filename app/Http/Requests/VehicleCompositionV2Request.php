<?php
/** © 2019 by Open Knowledge Belgium vzw/asbl
 * CompositionRequest Class.
 *
 * @author Bert Marcelis
 */

namespace Irail\Http\Requests;

use Carbon\Carbon;
use Irail\Exceptions\Request\InvalidRequestException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class VehicleCompositionV2Request extends IrailHttpRequest implements VehicleCompositionRequest
{
    private string $vehicleId;
    private bool $returnRawData;

    /**
     * @throws InvalidRequestException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        parent::__construct();
        $this->vehicleId = $this->_request->get('id');
    }

    /**
     * @return string
     */
    public function getVehicleId(): string
    {
        return $this->vehicleId;
    }

    public function getDateTime(): Carbon
    {
        // TODO: implement
        return Carbon::now();
    }
}
