<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem;

/**
 * Holds data for generation of an HTTP Problem
 *
 * https://tools.ietf.org/html/draft-ietf-appsawg-http-problem
 */
class HTTPProblem
{
    /**
     * @type int
     */
    private $status;

    /**
     * @type string|null
     */
    private $detail;

    /**
     * @type string|null
     */
    private $title;

    /**
     * @type string|null
     */
    private $type;
    private $instance;

    /**
     * @type array
     */
    private $extensions;

    /**
     * HTTP response codes and messages.
     *
     * This list is copied from Slim\Http\Response
     *
     * @type array
     */
    protected static $statusPhrases = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @param int $status
     * @param string $detail
     * @param array $extensions
     */
    public function __construct($status, $detail, array $extensions = [])
    {
        $this->withStatus($status);
        $this->withDetail($detail);
        $this->withExtensions($extensions);
    }

    /**
     * The HTTP status code.
     *
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * A human readable explanation specific to this occurrence of the problem.
     *
     * @return string
     */
    public function detail()
    {
        return $this->detail;
    }

    /**
     * Get any additional extension to the HTTP Problem
     *
     * Additional contextual information that may prove useful to the consumer of this problem.
     *
     * @return array
     */
    public function extensions()
    {
        return $this->extensions;
    }

    /**
     * A short, human-readable summary of the problem type.
     *
     * When type "about:blank" is used, title should be the HTTP status phrase for that code.
     *
     * @return string|null
     */
    public function title()
    {
        if (in_array($this->type(), [null, 'about:blank'], true)) {
            return $this->determineStatusPhrase($this->status());
        }

        return $this->title;
    }

    /**
     * A URI reference that identifies the problem type.
     *
     * If not provided, the type is assumed to be "about:blank".
     *
     * @return string|null
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * A URI reference for this specific instance of the problem type.
     *
     * If not provided, the type is assumed to be "about:blank".
     *
     * @return string|null
     */
    public function instance()
    {
        return $this->instance;
    }

    /**
     * @param int $status
     *
     * @return self
     */
    public function withStatus($status)
    {
        if (is_int($status) && $status >= 100 && $status < 600) {
            $this->status = $status;
        } else {
            $this->status = 500;
        }

        return $this;
    }

    /**
     * @param string $detail
     *
     * @return self
     */
    public function withDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @param array $extensions
     *
     * @return self
     */
    public function withExtensions(array $extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function withTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $typeURI
     *
     * @return self
     */
    public function withType($typeURI)
    {
        if ($typeURI == 'about:blank' || false !== filter_var($typeURI, FILTER_VALIDATE_URL)) {
            $this->type = $typeURI;
        } else {
            $this->type = null;
        }

        return $this;
    }

    /**
     * @param string $instanceURI
     *
     * @return self
     */
    public function withInstance($instanceURI)
    {
        if (false !== filter_var($instanceURI, FILTER_VALIDATE_URL)) {
            $this->instance = $instanceURI;
        } else {
            $this->instance = null;
        }

        return $this;
    }

    /**
     * @param int $status
     *
     * @return string
     */
    protected function determineStatusPhrase($status)
    {
        if (isset(self::$statusPhrases[$status])) {
            return self::$statusPhrases[$status];
        }

        return 'Unknown';
    }
}
