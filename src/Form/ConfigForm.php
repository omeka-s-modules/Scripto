<?php
namespace Scripto\Form;

use Scripto\Mediawiki\ApiClient;
use Zend\Form\Form;
use Zend\Http\Client as HttpClient;
use Zend\Validator\Callback;

class ConfigForm extends Form
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

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
                            Callback::INVALID_VALUE => 'The provided MediaWiki API URL is not valid.', // @translate
                        ],
                        'callback' => [$this, 'apiUrlIsValid']
                    ],
                ],
            ],
        ]);
    }

    /**
     * Is the MediaWiki API URL valid?
     *
     * @param string $apiUrl
     * @param array $context
     * @return bool
     */
    public function apiUrlIsValid($apiUrl, $context)
    {
        try {
            $client = new ApiClient($this->httpClient, $apiUrl);
        } catch (\Exception $e) {
            return false;
        }
        $siteInfo = $client->getSiteInfo();
        return is_array($siteInfo) && isset($siteInfo['query']);
    }

    /**
     * Set the HTTP client.
     *
     * @param HttpClient $httpClient
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }
}
