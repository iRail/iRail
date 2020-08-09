<?php


namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * This test compares the staging environment to the master environment, and ensures data is identical. This prevents bugs
 * introduced by refactorings. Should be ran manually, and output should be validated manually. Ignore changes that were made on purpose.
 */
class IrailStagingVsProdTest extends TestCase
{

    private static $PROD_BASE_URL = "https://api.irail.be/";
    private static $STAGING_BASE_URL = "https://staging.api.irail.be/";

    public function testStagingData(): void
    {
        $stationNames = [
            "Brussel-Zuid",
            "Brugge",
            "Namur",
            "Hasselt",
            "Mol",
            "Antwerpen-Berchem",
            "Charleroi-sud",
            "Haren",
            "Mortsel-Oude God",
            "Gent-Sint-Pieters"
        ];
        $trainIds = $this->verifyLiveboardsAndGetAListOfTrains($stationNames);
        $this->verifyTrains($trainIds);

        $connectionOrigins = ["Gent-Sint-Pieters", "Gent-Dampoort", "Essen", "Haren", "Haren-zuid", "Arlon"];
        $connectionDestinations = ["Kiewit", "Hasselt", "Brussel-zuid", "Erps-kwerps"];
        $this->verifyConnections($connectionOrigins, $connectionDestinations);
    }

    private function verifyLiveboardsAndGetAListOfTrains(array $stationNames)
    {
        $trainShortNames = [];

        foreach ($stationNames as $stationName) {
            $prodResult = $this->getProdData("liveboard.php?format=json&station=" . $stationName);
            $stagingResult = $this->getStagingData("liveboard.php?format=json&station=" . $stationName);
            unset($prodResult['timestamp']);
            unset($stagingResult['timestamp']);
            self::assertArraysAreEqual($prodResult, $stagingResult);

            foreach ($stagingResult['departures']['departure'] as $departure) {
                $trainShortNames[] = $departure["vehicleinfo"]["shortname"];
            }
        }
        array_unique($trainShortNames);
        return $trainShortNames;
    }

    private function verifyTrains($trainIds)
    {
        foreach ($trainIds as $trainId) {
            $prodResult = $this->getProdData("vehicle.php?format=json&id=" . $trainId);
            $stagingResult = $this->getStagingData("vehicle.php?format=json&id=" . $trainId);
            unset($prodResult['timestamp']);
            unset($stagingResult['timestamp']);
            self::assertArraysAreEqual($prodResult, $stagingResult);
        }
    }

    private function verifyConnections(array $connectionOrigins, array $connectionDestinations)
    {

    }

    private static function assertArraysAreEqual(array $expected, array $actual, string $parent = 'root')
    {
        foreach ($expected as $expectedKey => $expectedValue) {
            self::assertArrayHasKey($expectedKey, $actual, "$parent should have an array key $expectedKey");
            if (is_array($expected[$expectedKey])) {
                self::assertTrue(is_array($actual[$expectedKey]));
                self::assertArraysAreEqual($expected[$expectedKey], $actual[$expectedKey], $expectedKey);
            } else {
                self::assertFalse(is_array($actual[$expectedKey]));
                if ($expectedKey == "left" || $expectedKey == "arrived") {
                    continue;
                }
                self::assertEquals($expectedValue, $actual[$expectedKey], "Array key $expectedKey in $parent");
            }
        }
        foreach ($actual as $actualKey => $actualValue) {
            if (!key_exists($actualKey, $expected)) {
                echo "$parent has a newly added key $actualKey" . PHP_EOL;
            }
        }
    }

    public static function getClient()
    {
        return new Client([
            'http_errors' => false,
            'connect_timeout' => 2,
            'timeout' => 10,
            'user_agent' => "iRail Staging vs Prod test"
        ]);
    }

    /**
     * @param string $path
     * @return array Decoded json data
     * @throws GuzzleException
     */
    private function getProdData(string $path): array
    {
        usleep(1000);
        $url = self::$PROD_BASE_URL . $path;
        echo $url . PHP_EOL;
        $response = self::getClient()->request("GET", $url);
        return $this->verifyAndDecodeResponse($response);
    }

    /**
     * @param string $path
     * @return array Decoded json data
     * @throws GuzzleException
     */
    private function getStagingData(string $path): array
    {
        usleep(1000);
        $url = self::$STAGING_BASE_URL . $path;
        echo $url . PHP_EOL;
        $response = self::getClient()->request("GET", $url);
        return $this->verifyAndDecodeResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return array Decoded json data
     */
    private function verifyAndDecodeResponse(ResponseInterface $response): array
    {
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
        return json_decode($response->getBody(), true);
    }
}