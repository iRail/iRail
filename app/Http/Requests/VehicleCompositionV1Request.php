<?php
/** © 2019 by Open Knowledge Belgium vzw/asbl
 * CompositionRequest Class.
 *
 * @author Bert Marcelis
 */

namespace Irail\Http\Requests;

use Irail\Exceptions\Request\InvalidRequestException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class VehicleCompositionV1Request extends IrailHttpRequest implements VehicleCompositionRequest, IrailV1Request
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
        $this->returnRawData = $this->_request->get('data') === 'all';
    }

    /**
     * @return boolean True if raw source data should be returned as well, for people who need more data.
     */
    public function getShouldReturnRawData(): bool
    {
        return $this->returnRawData;
    }

    /**
     * @return string
     */
    public function getVehicleId(): string
    {
        return $this->vehicleId;
    }
}
