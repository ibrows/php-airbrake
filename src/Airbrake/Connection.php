<?php
namespace Airbrake;

/**
 * Airbrake connection class.
 *
 * @package    Airbrake
 * @author     Drew Butler <drew@dbtlr.com>
 * @copyright  (c) 2011-2013 Drew Butler
 * @license    http://www.opensource.org/licenses/mit-license.php
 */
class Connection implements Connection\ConnectionInterface
{
    protected $configuration = null;
    protected $headers = array();
    protected $lastResponse = null;

    /**
     * Build the object with the airbrake Configuration.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $this->addHeader(
            array(
                'Accept: text/xml, application/xml',
                'Content-Type: text/xml'
            )
        );
    }

    /**
     * Add a header to the connection.
     *
     * @param string|array $header
     */
    public function addHeader($header)
    {
        $this->headers += (array) $header;
    }

    /**
     * @param Notice $notice
     * @return bool
     **/
    public function send(Notice $notice)
    {
        $curl = curl_init();

        $xml = $notice->toXml($this->configuration);

        curl_setopt($curl, CURLOPT_URL, $this->configuration->get('apiEndPoint'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->configuration->get('timeout'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // HTTP proxy support
        $proxyHost = $this->configuration->get('proxyHost');
        $proxyUser = $this->configuration->get('proxyUser');

        if (null !== $proxyHost) {
            curl_setopt($curl, CURLOPT_PROXY, $proxyHost.':'.$this->configuration->get('proxyPort'));
            if (null !== $proxyUser) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyUser.':'.$this->configuration->get('proxyPass'));
            }
        }

        $exec = curl_exec($curl);
        curl_close($curl);

        return false !== stripos($exec, 'Status: 200 OK');
    }

    /**
     * @return string
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
