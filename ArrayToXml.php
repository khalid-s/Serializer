<?php
/**
 * Created by iKNSA.
 * Author: Khalid Sookia <khalidsookia@gmail.com>
 * Date: 27/12/16
 * Time: 23:01
 */

namespace Romenys\Serializer;


class ArrayToXml
{
    const DEFAULT_VERSION = '1.0';
    const DEFAULT_ENCODING = 'UTF-8';
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $header;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var \DOMDocument
     */
    private $xml = null;

    /**
     * ArrayToXml constructor.
     * @param string $node_name
     * @param array $data
     * @param string $header [optional]
     * @param string $encoding [optional]
     * @param string $version [optional]
     */
    public function __construct($node_name, array $data, $header = null, $encoding = null, $version = null)
    {
        $this->setData($data);
        $this->setHeader($header);
        $this->setEncoding($encoding);
        $this->setVersion($version);
        $this->setXml();
        $this->createXML($node_name);
    }

    /**
     * get XML
     * @return \DOMDocument
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $data - aray to be converterd
     *
     * @return \DomDocument
     */
    private function createXML($node_name, $data = array()) {
        $this->xml = $this->getXMLRoot();
        $this->xml->appendChild($this->convert($node_name, $data));

//        $this->xml = null;    // clear the xml node in the class for 2nd time use.

        return $this->xml;
    }

    /**
     * @return array
     */
    private function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return ArrayToXml
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     *
     * @return ArrayToXml
     */
    public function setHeader($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @param null $encoding
     *
     * @return ArrayToXml
     */
    private function setEncoding($encoding = null)
    {
        $this->encoding = is_null($encoding) ? self::DEFAULT_ENCODING : $encoding;

        return $this;
    }

    /**
     * @return string
     */
    private function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @param null $version
     *
     * @return ArrayToXml
     */
    private function setVersion($version = null)
    {
        $this->version = is_null($version) ? self::DEFAULT_VERSION : $version;

        return $this;
    }

    /**
     * @return string
     */
    private function getVersion()
    {
        return $this->version;
    }

    /**
     * Initialize the root XML node [optional]
     * @param $version string
     * @param $encoding string
     * @param $format_output bool
     *
     * @return ArrayToXml
     */
     private function setXml($version = null, $encoding = null, $format_output = true) {
        $version = is_null($version) ? $this->getVersion() : $version;
        $encoding = is_null($encoding) ? $this->getEncoding() : $encoding;

        $this->xml = new DomDocument($version, $encoding);
        $this->xml->formatOutput = $format_output;

        return $this;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $data - aray to be converterd
     * @return DOMNode
     */
    private function &convert($node_name, $data=array()) {

        //print_arr($node_name);
        $xml = $this->getXMLRoot();
        $node = $xml->createElement($node_name);

        if(is_array($data)){
            // get the attributes first.;
            if(isset($data['@attributes'])) {
                foreach($data['@attributes'] as $key => $value) {
                    if(!$this->isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, $this->bool2str($value));
                }
                unset($data['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if(isset($data['@value'])) {
                $node->appendChild($xml->createTextNode($this->bool2str($data['@value'])));
                unset($data['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if(isset($data['@cdata'])) {
                $node->appendChild($xml->createCDATASection($this->bool2str($data['@cdata'])));
                unset($data['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if(is_array($data)){
            // recurse to get the node for that key
            foreach($data as $key=>$value){
                if(!$this->isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if(is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach($value as $k=>$v){
                        $node->appendChild($this->convert($key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild($this->convert($key, $value));
                }
                unset($data[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if(!is_array($arr)) {
            $node->appendChild($xml->createTextNode($this->bool2str($arr)));
        }

        return $node;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     *
     * @return \DOMDocument
     */
    private function getXMLRoot()
    {
        if (empty($this->getXml())) {
            $this->setXml();
        }

        return $this->getXml();
    }

    /**
     * Get string representation of boolean value
     *
     * @return string (true|false)
     */
    private function bool2str($v){
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;

        return $v;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     *
     * @link http://www.w3.org/TR/xml/#sec-common-syn
     */
    private function isValidTagName($tag){
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}
