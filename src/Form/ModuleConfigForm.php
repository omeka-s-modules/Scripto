<?php
namespace Scripto\Form;

use Scripto\Mediawiki\ApiClient;
use Laminas\Form\Form;
use Laminas\Http\Client as HttpClient;
use Laminas\Validator\Callback;

class ModuleConfigForm extends Form
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $timeZone;

    public function init()
    {
        $this->add([
            'type' => 'text',
            'name' => 'apiurl',
            'options' => [
                'label' => 'MediaWiki API URL', // @translate
                'info' => 'Enter the URL to your MediaWiki API endpoint.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'apiurl',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'apiurl',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'Uri',
                    'options' => [
                        'allowRelative' => false,
                    ],
                ],
                [
                    'name' => 'Callback',
                    'options' => [
                        'messages' => [
                            Callback::INVALID_VALUE => sprintf(
                                'Invalid MediaWiki API. The URL must resolve to a MediaWiki API endpoint and the MediaWiki version must be %s or greater.', // @translate
                                ApiClient::MINIMUM_VERSION
                            ),
                        ],
                        'callback' => [$this, 'apiIsValid'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Is the MediaWiki API valid?
     *
     * @param string $apiUrl
     * @param array $context
     * @return bool
     */
    public function apiIsValid($apiUrl, $context)
    {
        try {
            $client = new ApiClient($this->httpClient, $apiUrl, $this->timeZone);
            if (!is_array($client->querySiteInfo())) {
                // Not a MediaWiki API endpoint
                return false;
            }
            if (version_compare($client->getVersion(), ApiClient::MINIMUM_VERSION, '<')) {
                // The MediaWiki version is invalid
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Set the MediaWiki API client dependencies.
     *
     * @param HttpClient $httpClient
     * @param string $timeZone
     */
    public function setApiClientDependencies(HttpClient $httpClient, $timeZone)
    {
        $this->httpClient = $httpClient;
        $this->timeZone = $timeZone;
    }
}
