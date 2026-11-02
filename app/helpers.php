<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\ServerRequestCreatorFactory;

if (!defined('HUMAN_RANDOM_CHARS')) {
    define('HUMAN_RANDOM_CHARS', 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZaeiouAEIOU');
}

if (!function_exists('humanFileSize')) {
    /**
     * Generate a human readable file size.
     *
     * @param $size
     * @param  int  $precision
     *
     * @param  bool  $iniMode
     * @return string
     */
    function humanFileSize($size, $precision = 2, $iniMode = false): string
    {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }

        if ($iniMode) {
            return round($size, $precision).['B', 'K', 'M', 'G', 'T'][$i];
        }

        return round($size, $precision).' '.['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }
}

if (!function_exists('humanRandomString')) {
    /**
     * @param  int  $length
     *
     * @return string
     */
    function humanRandomString(int $length = 10): string
    {
        $result = '';
        $numberOffset = round($length * 0.2);
        for ($x = 0; $x < $length - $numberOffset; $x++) {
            $result .= ($x % 2) ? HUMAN_RANDOM_CHARS[rand(42, 51)] : HUMAN_RANDOM_CHARS[rand(0, 41)];
        }
        for ($x = 0; $x < $numberOffset; $x++) {
            $result .= rand(0, 9);
        }

        return $result;
    }
}

if (!function_exists('isDisplayableImage')) {
    /**
     * @param  string  $mime
     *
     * @return bool
     */
    function isDisplayableImage(?string $mime): bool
    {
        return in_array($mime, [
            'image/apng',
            'image/bmp',
            'image/gif',
            'image/x-icon',
            'image/jpeg',
            'image/png',
            'image/tiff',
            'image/webp',
        ]);
    }
}

if (!function_exists('isEmbeddable')) {
    /**
     * @param  ?string  $mime
     *
     * @return bool
     */
    function isEmbeddable(?string $mime): bool
    {
        return in_array($mime, [
            'image/apng',
            'image/bmp',
            'image/gif',
            'image/x-icon',
            'image/jpeg',
            'image/png',
            'image/tiff',
            'image/webp',
            'video/mp4',
            'video/ogg',
            'video/webm',
        ]);
    }
}

if (!function_exists('stringToBytes')) {
    /**
     * @param $str
     *
     * @return float
     */
    function stringToBytes(string $str): float
    {
        $val = trim($str);
        if (is_numeric($val)) {
            return (float) $val;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $val = substr($val, 0, -1);

        $val = (float) $val;
        switch ($last) {
            case 't':
                $val *= 1024;
                // no break
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}

if (!function_exists('removeDirectory')) {
    /**
     * Remove a directory and it's content.
     *
     * @param $path
     */
    function removeDirectory($path)
    {
        cleanDirectory($path, true);
        rmdir($path);
    }
}

if (!function_exists('cleanDirectory')) {
    /**
     * Removes all directory contents.
     *
     * @param $path
     * @param  bool  $all
     */
    function cleanDirectory($path, $all = false)
    {
        $directoryIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iteratorIterator as $file) {
            if ($all || $file->getFilename() !== '.gitkeep') {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
        }
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolve a service from de DI container.
     *
     * @param  string  $service
     *
     * @return mixed
     */
    function resolve(string $service)
    {
        global $app;

        return $app->getContainer()->get($service);
    }
}

if (!function_exists('make')) {
    /**
     * Resolve a service from de DI container.
     *
     * @param  string  $class
     * @param  array  $params
     * @return mixed
     */
    function make(string $class, array $params = [])
    {
        global $app;

        return $app->getContainer()->make($class, $params);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view to the response body.
     *
     * @return \App\Web\View
     */
    function view()
    {
        return resolve('view');
    }
}

if (!function_exists('redirect')) {
    /**
     * Set the redirect response.
     *
     * @param  Response  $response
     * @param  string  $url
     * @param  int  $status
     *
     * @return Response
     */
    function redirect(Response $response, string $url, $status = 302)
    {
        return $response
            ->withHeader('Location', $url)
            ->withStatus($status);
    }
}

if (!function_exists('asset')) {
    /**
     * Get the asset link with timestamp.
     *
     * @param  string  $path
     *
     * @return string
     */
    function asset(string $path): string
    {
        return urlFor($path, '?'.filemtime(realpath(BASE_DIR.$path)));
    }
}

if (!function_exists('urlFor')) {
    /**
     * Generate the app url given a path.
     *
     * @param  string  $path
     * @param  string  $append
     *
     * @return string
     */
    function urlFor(string $path = '', string $append = ''): string
    {
        $baseUrl = resolve('config')['base_url'];

        return $baseUrl.$path.$append;
    }
}

if (!function_exists('route')) {
    /**
     * Generate the app url given a path.
     *
     * @param  string  $path
     * @param  array  $args
     * @param  string  $append
     *
     * @return string
     */
    function route(string $path, array $args = [], string $append = ''): string
    {
        global $app;
        $uri = $app->getRouteCollector()->getRouteParser()->relativeUrlFor($path, $args);

        return urlFor($uri, $append);
    }
}

if (!function_exists('param')) {
    /**
     * Get a parameter from the request.
     *
     * @param  Request  $request
     * @param  string  $name
     * @param  null  $default
     *
     * @return mixed
     */
    function param(Request $request, string $name, $default = null)
    {
        if ($request->getMethod() === 'GET') {
            $params = $request->getQueryParams();
        } else {
            $params = $request->getParsedBody();
        }

        return $params[$name] ?? $default;
    }
}

if (!function_exists('json')) {
    /**
     * Return a json response.
     *
     * @param  Response  $response
     * @param $data
     * @param  int  $status
     * @param  int  $options
     *
     * @return Response
     */
    function json(Response $response, $data, int $status = 200, $options = 0): Response
    {
        $response->getBody()->write(json_encode($data, $options));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}

if (!function_exists('lang')) {
    /**
     * @param  string  $key
     * @param  array  $args
     *
     * @return string
     */
    function lang(string $key, $args = []): string
    {
        return resolve('lang')->get($key, $args);
    }
}

if (!function_exists('mime2font')) {
    /**
     * Convert get the icon from the file mimetype.
     *
     * @param $mime
     *
     * @return mixed|string
     */
    function mime2font($mime)
    {
        $classes = [
            'image' => 'fa-file-image',
            'audio' => 'fa-file-audio',
            'video' => 'fa-file-video',
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.ms-word' => 'fa-file-word',
            'application/vnd.oasis.opendocument.text' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel',
            'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint',
            'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint',
            'text/plain' => 'fa-file-alt',
            'text/html' => 'fa-file-code',
            'text/x-php' => 'fa-file-code',
            'application/json' => 'fa-file-code',
            'application/gzip' => 'fa-file-archive',
            'application/zip' => 'fa-file-archive',
            'application/octet-stream' => 'fa-file-alt',
        ];

        foreach ($classes as $fullMime => $class) {
            if (strpos($mime, $fullMime) === 0) {
                return $class;
            }
        }

        return 'fa-file';
    }
}

if (!function_exists('dd')) {
    /**
     * Dumps all the given vars and halt the execution.
     */
    function dd()
    {
        array_map(function ($x) {
            echo '<pre>';
            print_r($x);
            echo '</pre>';
        }, func_get_args());
        die();
    }
}

if (!function_exists('queryParams')) {
    /**
     * Get the query parameters of the current request.
     *
     * @param  array  $replace
     *
     * @return string
     */
    function queryParams(array $replace = [])
    {
        $request = ServerRequestCreatorFactory::determineServerRequestCreator()->createServerRequestFromGlobals();

        $params = array_replace_recursive($request->getQueryParams(), $replace);

        return !empty($params) ? '?'.http_build_query($params) : '';
    }
}

if (!function_exists('inPath')) {
    /**
     * Check if uri start with a path.
     *
     * @param  string  $uri
     * @param  string  $path
     *
     * @return bool
     */
    function inPath(string $uri, string $path): bool
    {
        $path = parse_url(urlFor($path), PHP_URL_PATH);

        return substr($uri, 0, strlen($path)) === $path;
    }
}

if (!function_exists('glob_recursive')) {
    /**
     * Does not support flag GLOB_BRACE.
     *
     * @param $pattern
     * @param  int  $flags
     *
     * @return array|false
     */
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}

if (!function_exists('dsnFromConfig')) {
    /**
     * Return the database DSN from config.
     *
     * @param  array  $config
     *
     * @return string
     */
    function dsnFromConfig(array $config): string
    {
        $dsn = $config['db']['dsn'];
        if ($config['db']['connection'] === 'sqlite') {
            if (getcwd() !== BASE_DIR) { // if in installer, change the working dir to the app dir
                chdir(BASE_DIR);
            }
            if (file_exists($config['db']['dsn'])) {
                $dsn = realpath($config['db']['dsn']);
            }
        }

        return $config['db']['connection'].':'.$dsn;
    }
}

if (!function_exists('platform_mail')) {
    /**
     * Return the system no-reply mail.
     *
     * @param  string  $mailbox
     * @return string
     */
    function platform_mail($mailbox = 'no-reply'): string
    {
        $config = resolve('config');

        if (isset($config['app_email'])) {
            return $config['app_email'];
        }

        return $mailbox.'@'.str_ireplace('www.', '', parse_url(resolve('config')['base_url'], PHP_URL_HOST));
    }
}

if (!function_exists('must_be_escaped')) {
    /**
     * Return the system no-reply mail.
     *
     * @param $mime
     * @return bool
     */
    function must_be_escaped($mime): bool
    {
        $mimes = [
            'text/htm',
            'text/xml',
            'text/xsl',
            'application/xml',
            'application/xhtml',
            'image/svg',
        ];

        foreach ($mimes as $m) {
            if (stripos($mime, $m) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('isSecure')) {
    /**
     * @return bool
     */
    function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443);
    }
}

if (!function_exists('glue')) {
    /**
     * @param  mixed  ...$pieces
     * @return string
     */
    function glue(...$pieces): string
    {
        return '/'.implode('/', $pieces);
    }
}
