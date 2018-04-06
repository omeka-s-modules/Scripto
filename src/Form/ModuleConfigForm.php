<?php
namespace Scripto\Form;

use Scripto\Mediawiki\ApiClient;
use Zend\Form\Form;
use Zend\Http\Client as HttpClient;
use Zend\Validator\Callback;

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
            $client = new ApiClient($this->httpClient, $apiUrl, $this->timeZone);
        } catch (\Exception $e) {
            return false;
        }
        return is_array($client->getSiteInfo());
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
