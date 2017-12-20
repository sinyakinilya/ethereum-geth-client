<?php
/**
 * SmartContract.php
 *
 * @author   Ilya Sinyakin <sinyakin.ilya@gmail.com>
 */

namespace SinyakinIlya\Ethereum\Helper;

use SinyakinIlya\Ethereum\Client;

class SmartContract
{

    /** @var Client */
    private $client;

    protected $address;

    protected $interfaceData;

    private $fnSignature;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     *
     * @return $this;
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return SmartContract;
     */
    public function setContractInterface($data)
    {
        $this->interfaceData = json_decode($data, true);
        $this->setSignature();
        return $this;
    }

    private function setSignature()
    {
        $fnSignatures = [];
        foreach ($this->interfaceData as $row) {
            $params = [];
            foreach ($row['inputs'] as $paramInfo) {
                $params[] = $paramInfo['type'];
            }

            $fnName = (empty($row['name'])) ? 'constructor' : $row['name']; //TODO: необходимо добавить указание констнкутора

            $fnSignatures[$row['type']][] = [
                'fnSignature' => sprintf('%s(%s)', $fnName, join(',', $params)),
                'stateMutability' => empty($row['stateMutability']) ? null : $row['stateMutability'],
                'inputs' => $row['inputs'],
            ];
        }

        $this->fnSignature = $fnSignatures;
    }

    public function getFunctions()
    {
        return empty($this->fnSignature['function']) ? null : $this->fnSignature['function'];
    }

    public function getHexSignature($functionSignature, $fullCoincidence = false)
    {
        if (count($this->fnSignature) == 0) {
            return null;
        }
        if ($fullCoincidence) {
            $functionSignature .= '(';
        }
        $result = [];
        foreach ($this->fnSignature['function'] as $fnData) {
            if (strpos($fnData['fnSignature'], $functionSignature) === 0) {
                $sendHexData = '0x' . Hash::strToHex($fnData['fnSignature']);
                $hexData = $this->client->callMethod('web3_sha3', [$sendHexData]);
                $result[$fnData['fnSignature']] = [
                    'fnSign' => mb_strcut($hexData, 0, 10),
                    'inputs' => $fnData['inputs'],
                ];
            }
        }
        if (!count($result)) {
            throw new \Exception("Bad function signature");
        }

        return $result;
    }

    private function paramToHex($paramData, $type)
    {
        $hexParam = '';
        switch ($type) {
            case 'uint':
            case 'uint256':
                $hex      = Hash::largeDecHex($paramData);
                $hexParam = str_pad($hex, 64, '0', STR_PAD_LEFT);
                break;
            case 'address':
                Hash::isValidAddress($paramData, true);
                $address  = mb_strcut($paramData, 2, strlen($paramData));
                $hexParam = str_pad($address, 64, '0', STR_PAD_LEFT);
                break;
        }
        return $hexParam;
    }

    public function createSandRawData($functionSignature, $params = [])
    {
        $fnSign = $this->getHexSignature($functionSignature, false);
        $result = [];
        foreach ($fnSign as $fnSignature => $fn) {
            $prefix = [ 0 => 32 * count($fn['inputs']) ];
            $rawData = '';
            $isPrefix = false;
            foreach ($fn['inputs'] as $i => $param) {
                $prefix[] = $prefix[$i] + 32 * (count($params[$i]) + 1);
                if (strpos($param['type'], '[]') !== false) {
                    $paramType = mb_strcut($param['type'], 0, strlen($param['type']) - 2);
                    $isPrefix = true;
                    $rawData .= $this->paramToHex(count($params[$i]), 'uint');
                    foreach ($params[$i] as $raw) {
                        $rawData .= $this->paramToHex($raw, $paramType);
                    }
                } else {
                    $rawData .= $this->paramToHex($params[$i], $param['type']);
                }
            }
            if ($isPrefix) {
                $len = count($prefix);
                foreach ($prefix as $k => $data) {
                    if ($len-1 == $k) {
                        $prefix[$k] = null;
                        break;
                    }
                    $prefix[$k] = $this->paramToHex($data, 'uint');
                }
                $prefix = join('', $prefix);
            } else {
                $prefix = '';
            }
            $result[$fnSignature] = $fn['fnSign'] . $prefix . $rawData;
        }

        return $result;
    }
}
