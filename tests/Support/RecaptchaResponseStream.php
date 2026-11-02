<?php


namespace Tests\Support;

/**
 * Local replacement of the "https" stream wrapper, used to fake the answer of
 * the Google reCAPTCHA verification endpoint without sending a single request
 * over the network.
 */
class RecaptchaResponseStream
{
    /** @var resource|null */
    public $context;

    /**
     * The body returned for every https request.
     *
     * @var string
     */
    public static $body = '{"success":false,"error-codes":["invalid-input-response"]}';

    /** @var int */
    private $position = 0;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;

        return true;
    }

    public function stream_read($count)
    {
        $chunk = substr(self::$body, $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$body);
    }

    public function stream_stat()
    {
        return ['size' => strlen(self::$body)];
    }

    public function stream_tell()
    {
        return $this->position;
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen(self::$body) + $offset;
                break;
            default:
                $this->position = $offset;
        }

        return true;
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return true;
    }

    public function stream_close()
    {
        return true;
    }
}
