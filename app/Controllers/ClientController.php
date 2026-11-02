<?php

namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class ClientController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     */
    public function getShareXConfig(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);
    
        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');
    
            return redirect($response, referer_redirect_path($request, route('home')));
        }
    
        $fileBaseName = $user->username . '-ShareX'; // Base file name without extension
        $fileName = $fileBaseName . '.sxcu'; // Full file name with extension
    
        $json = [
            'Version' => '16.1.0',
            'Name' => $fileBaseName,
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestMethod' => 'POST',
            'RequestURL' => route('upload'),
            'Body' => 'MultipartFormData',
            'Arguments' => [
                'file' => '{filename}',
                'text' => '{input}',
                'token' => $user->token,
            ],
            'FileFormName' => 'upload',
            'URL' => '{json:url}',
            'ThumbnailURL' => '{json:url}/raw',
            'DeletionURL' => '{json:url}/delete/' . $user->token,
        ];
    
        return json($response, $json, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->withHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"');
    }
    

    /**
     * @param  Request  $request
     * @param  string|null  $token
     * @return Response
     * @throws \ZipStream\Exception\FileNotFoundException
     * @throws \ZipStream\Exception\FileNotReadableException
     * @throws \ZipStream\Exception\OverflowException
     * @throws HttpNotFoundException
     */
    public function getScreenCloudConfig(Request $request, string $token): Response
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', $token)->fetch();
        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        $config = [
            'token' => $token,
            'host' => route('root'),
        ];

        ob_end_clean();

        $options = new Archive();
        $options->setSendHttpHeaders(true);

        $zip = new ZipStream($user->username.'-screencloud.zip', $options);

        $zip->addFileFromPath('main.py', BASE_DIR.'resources/uploaders/screencloud/main.py');
        $zip->addFileFromPath('icon.png', BASE_DIR.'static/images/favicon-32x32.png');
        $zip->addFileFromPath('metadata.xml', BASE_DIR.'resources/uploaders/screencloud/metadata.xml');
        $zip->addFileFromPath('settings.ui', BASE_DIR.'resources/uploaders/screencloud/settings.ui');
        $zip->addFile('config.json', json_encode($config, JSON_UNESCAPED_SLASHES));

        $zip->finish();
        exit(0);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getBashScript(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, referer_redirect_path($request, route('home')));
        }

        return view()->render(
            $response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_uploader.sh.twig',
            [
                'username' => $user->username,
                'upload_url' => route('upload'),
                'token' => $user->token,
            ]
        );
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getKDEScript(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, referer_redirect_path($request, route('home')));
        }

        return view()->render(
            $response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_kde_uploader.sh.twig',
            [
                'username' => $user->username,
                'upload_url' => route('upload'),
                'token' => $user->token,
            ]
        );
    }
}
