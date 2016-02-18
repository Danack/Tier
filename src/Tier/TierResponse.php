<?php


namespace Tier;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface as Request;
use Room11\HTTP\Body;
use Room11\HTTP\HeadersSet;
use Zend\Diactoros\Stream;

/**
 * Class TierResponse
 *
 * An implementation of
 *
 * The mutation methods are not implemented as the response is only created
 * momentarily before sending, and there are no 'hooks' to modify the response.
 * https://www.youtube.com/watch?v=ywkzQTGkPFg
 */
class TierResponse implements ResponseInterface
{
    /** @var Request */
    private $request;
    
    /** @var HeadersSet  */
    private $headersSet;
    
    /** @var Body  */
    private $body;

    private $overRidingProtocol = null;
    
    private $overridingReasonPhrase = '';
    
    private $overridingStatusCode = null;
    
    /** @var  StreamInterface */
    private $overridingStreamInterface = null;
    
    /**
     * Map of standard HTTP status code/reason phrases
     *
     * @var array
     */
    public static $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        420 => 'Hey look, an eagle!',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
    

    public function __construct(
        Request $request,
        HeadersSet $headersSet,
        Body $body
    ) {
        $this->request = $request;
        $this->body = $body;
        $this->headersSet = $headersSet->merge($this->body->getHeadersSet());

        if ($this->headersSet->hasHeaders("Date") === false) {
            $this->headersSet->addHeader("Date", gmdate("D, d M Y H:i:s", time())." UTC");
        }
    }
    
    public function __clone()
    {
        $this->request = clone $this->request;
        $this->headersSet = clone $this->headersSet;
        $this->body = clone $this->body;
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        if ($this->overRidingProtocol !== null) {
            return $this->overRidingProtocol;
        }

        return $this->request->getProtocolVersion();
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $instance = clone $this;
        $instance->overRidingProtocol = $version;

        return $instance;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->headersSet->getAllHeaders();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return $this->headersSet->hasHeaders($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name)
    {
        return $this->headersSet->getHeaders($name);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        if ($this->headersSet->hasHeaders($name) === false) {
            return '';
        }

        $values = $this->getHeader($name);

        return implode(',', $values);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $instance = clone $this;
        $allHeaders = $instance->headersSet->getAllHeaders();
        
        $newHeaders = [];
        
        foreach ($allHeaders as $existingName => $existingValues) {
            if (strcasecmp($existingName, $name) === 0) {
                continue;
            }
            else {
                $newHeaders[$existingName] = $existingValues;
            }
        }
        
        if (is_array($value) === true) {
            $newHeaders[$name] = $value;
        }
        else {
            $newHeaders[$name] = [(string)$value];
        }
        
        $instance->headersSet = HeadersSet::fromArray($newHeaders);

        return $instance;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $instance = clone $this;
        
        if (is_array($value) === true) {
            foreach ($value as $fieldValue) {
                $instance->headersSet->addHeader($name, (string)$fieldValue);
            }
        }
        else {
            $instance->headersSet->addHeader($name, (string)$value);
        }

        return $instance;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name)
    {
        $instance = clone $this;
        $allHeaders = $instance->headersSet->getAllHeaders();
        $newHeaders = [];
        foreach ($allHeaders as $existingName => $existingValues) {
            if (strcasecmp($existingName, $name) === 0) {
                continue;
            }
            else {
                $newHeaders[$existingName] = $existingValues;
            }
        }
        $instance->headersSet = HeadersSet::fromArray($newHeaders);

        return $instance;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if ($this->overridingStreamInterface !== null) {
            return $this->overridingStreamInterface;
        }
        
        $contents = $this->body->getData();
        $body = new Stream('php://temp', 'wb+');
        $body->write($contents);
        $body->rewind();

        return $body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $instance = clone $this;
        $instance->overridingStreamInterface = $body;
        
        return $instance;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        if ($this->overridingStatusCode !== null) {
            return $this->overridingStatusCode;
        }

        return $this->body->getStatusCode();
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $instance = clone $this;
        $instance->overridingStatusCode = $code;
        $instance->overridingReasonPhrase = $reasonPhrase;
        
        return $instance;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        if ($this->overridingReasonPhrase !== '') {
            return $this->overridingReasonPhrase;
        }
        
        $bodyReasonPhrase = $this->body->getReasonPhrase();
        if ($bodyReasonPhrase !== null) {
            return $bodyReasonPhrase;
        }
        
        $statusCode = $this->getStatusCode();
        
        if (isset(self::$phrases[$statusCode]) === true) {
            return self::$phrases[$statusCode];
        }

        return "";
    }
}
